<?php if (!defined('IN_GS')) { die('You cannot load this page directly.'); }

/*
Plugin Name: Flip Book
Description: Flip-book builder using StPageFlip and PDF.js — mix PDF pages, images, and image+text pages in one reorderable flip-book.
Version: 1.0
Author: risingisland
Author URI: https://ko-fi.com/ericmontgomery
*/

// ─── Constants ───────────────────────────────────────────────────────────────

// $SITEURL can report http:// even when the site is actually being served over
// https (a documented GS-CE quirk on some live server configs). Getting this
// wrong means URLs we build (uploads, thumbs, the plugin's own asset paths)
// point at http:// on an https page, which browsers silently block as mixed
// content for fetch()/XHR-driven loads (PDF.js) even though a plain <img> tag
// often tolerates it - which is exactly the "images work, PDF.js doesn't" split.
function FlipBook_site_url() {
	global $SITEURL;
	$is_https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
		|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
		|| (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
	if ($is_https && strpos($SITEURL, 'http://') === 0) {
		return 'https://' . substr($SITEURL, 7);
	}
	return $SITEURL;
}

define('FlipBook_SLUG',       basename(__FILE__, '.php'));
define('FlipBook_PLUGIN_DIR', GSPLUGINPATH . FlipBook_SLUG . '/');
define('FlipBook_PLUGIN_URL', FlipBook_site_url() . 'plugins/' . FlipBook_SLUG . '/');
define('FlipBook_DATA_PATH',  GSDATAOTHERPATH . 'flipbook/');
// Uploads live in the standard GS uploads folder, same as Items Manager
define('FlipBook_UPLOAD_DIR', GSDATAUPLOADPATH);
define('FlipBook_UPLOAD_URL', FlipBook_site_url() . 'data/uploads/');
define('FlipBook_THUMB_DIR',  GSTHUMBNAILPATH);
define('FlipBook_THUMB_URL',  FlipBook_site_url() . 'data/thumbs/');

// i18n namespace built from the constant so a future rename doesn't silently
// break every i18n_r() lookup.
i18n_merge(FlipBook_SLUG) || i18n_merge(FlipBook_SLUG, 'en_US');

// ─── Bootstrap ───────────────────────────────────────────────────────────────

register_plugin(
	FlipBook_SLUG,
	'Flip Book',
	'1.0',
	'risingisland',
	'https://ko-fi.com/ericmontgomery',
	'Flip-book builder using StPageFlip and PDF.js',
	'theme',
	'FlipBook_admin_page'
);

add_action('theme-sidebar', 'createSideMenu', array(
	FlipBook_SLUG,
	i18n_r(FlipBook_SLUG . '/lang_Menu_Title') . ' <svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6c-2.5-1.5-5.5-2-8-1.5v13c2.5-.5 5.5 0 8 1.5c2.5-1.5 5.5-2 8-1.5v-13c-2.5-.5-5.5 0-8 1.5Z"/><path d="M12 6v13"/></svg>',
	'?id=' . FlipBook_SLUG
));

// Front-end: [flipbook id="1"] / [flipbook slug="..."] shortcode, and the matching
// get_FlipBook() theme template tag.
add_filter('content', 'FlipBook_shortcode_filter');

// Front-end asset loading (StPageFlip, PDF.js, frontend.js/css) has to go through
// theme-footer, not the content filter above: get_page_content() runs
// strip_content() AFTER content filters, which strips <script>/<link> tags out of
// whatever the shortcode returns. theme-footer runs separately and isn't stripped.
add_action('theme-footer', 'FlipBook_frontend_assets');

FlipBook_init_storage();

// ── Early AJAX intercept ──────────────────────────────────────────────────────
if (
	!empty($_REQUEST['fb_action']) &&
	isset($_GET['id']) && $_GET['id'] === FlipBook_SLUG
) {
	FlipBook_handle_ajax($_REQUEST['fb_action']);
	// FlipBook_handle_ajax() always exit()s - this is a safety net:
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// STORAGE  (JSON, one file per flip-book, under data/other/flipbook/)
// ─────────────────────────────────────────────────────────────────────────────

function FlipBook_init_storage() {
	$dir = FlipBook_DATA_PATH;
	if (!is_dir($dir)) {
		mkdir($dir, 0755, true);
	}
	$htaccess = $dir . '.htaccess';
	if (!file_exists($htaccess)) {
		file_put_contents($htaccess, "Require all denied\n");
	}
}

function FlipBook_default_settings() {
	return [
		'width'               => 600,
		'height'              => 800,
		'size'                => 'stretch', // 'fixed' | 'stretch'
		'show_cover'          => true,
		'sound'               => true,
		'single_page_mobile'  => true,
	];
}

function FlipBook_sanitize_settings($in) {
	$in = is_array($in) ? $in : [];
	$defaults = FlipBook_default_settings();
	return [
		'width'              => max(100, (int)($in['width']  ?? $defaults['width'])),
		'height'             => max(100, (int)($in['height'] ?? $defaults['height'])),
		'size'               => in_array(($in['size'] ?? ''), ['fixed', 'stretch'], true) ? $in['size'] : $defaults['size'],
		'show_cover'         => !empty($in['show_cover']),
		'sound'              => !empty($in['sound']),
		'single_page_mobile' => !empty($in['single_page_mobile']),
	];
}

// One page entry is one of:
//   {"type":"image", "file":"photo.jpg"}
//   {"type":"image_text", "file":"photo.jpg", "text":"<p>...</p>", "text_position":"above"|"below"}
//   {"type":"pdf", "file":"catalog.pdf", "page":3}   <- added in a later phase
function FlipBook_sanitize_pages($in) {
	if (!is_array($in)) return [];
	$out = [];
	foreach ($in as $p) {
		if (!is_array($p)) continue;
		$type = $p['type'] ?? '';
		if ($type === 'image') {
			$file = FlipBook_sanitize_upload_filename($p['file'] ?? '');
			if ($file === '') continue;
			$out[] = ['type' => 'image', 'file' => $file];
		} elseif ($type === 'image_text') {
			$file = FlipBook_sanitize_upload_filename($p['file'] ?? '');
			if ($file === '') continue;
			$pos = ($p['text_position'] ?? '') === 'above' ? 'above' : 'below';
			// Text is rich HTML from Autumnnote - not user-facing PHP-executed content,
			// stored as-is (GS-CE templates already echo similarly-sourced component HTML raw).
			$out[] = ['type' => 'image_text', 'file' => $file, 'text' => (string)($p['text'] ?? ''), 'text_position' => $pos];
		} elseif ($type === 'pdf') {
			$file = FlipBook_sanitize_upload_filename($p['file'] ?? '');
			$page = (int)($p['page'] ?? 0);
			if ($file === '' || $page < 1) continue;
			$out[] = ['type' => 'pdf', 'file' => $file, 'page' => $page];
		}
	}
	return $out;
}

// Normalise a submitted upload path: forward slashes, no traversal, no leading slash
function FlipBook_sanitize_upload_filename($filename) {
	$filename = str_replace('\\', '/', (string)$filename);
	$filename = preg_replace('/\.\.+/', '', $filename);
	return trim($filename, '/');
}

function FlipBook_get_books() {
	$out = [];
	foreach (glob(FlipBook_DATA_PATH . '*.json') as $file) {
		$data = json_decode(file_get_contents($file), true);
		if (is_array($data)) $out[] = $data;
	}
	usort($out, function ($a, $b) {
		return ($b['updated'] ?? 0) <=> ($a['updated'] ?? 0);
	});
	return $out;
}

function FlipBook_get_book($id) {
	$id = (int)$id;
	if ($id <= 0) return null;
	$file = FlipBook_DATA_PATH . $id . '.json';
	if (!file_exists($file)) return null;
	$data = json_decode(file_get_contents($file), true);
	return is_array($data) ? $data : null;
}

function FlipBook_get_book_by_slug($slug) {
	foreach (FlipBook_get_books() as $book) {
		if (($book['slug'] ?? null) === $slug) return $book;
	}
	return null;
}

function FlipBook_next_id() {
	$max = 0;
	foreach (glob(FlipBook_DATA_PATH . '*.json') as $file) {
		$id = (int) basename($file, '.json');
		if ($id > $max) $max = $id;
	}
	return $max + 1;
}

function FlipBook_slugify($str) {
	$str = strtolower(trim((string)$str));
	$str = preg_replace('/[^a-z0-9]+/', '-', $str);
	return trim($str, '-');
}

function FlipBook_unique_slug($name, $exclude_id = 0) {
	$base = FlipBook_slugify($name);
	if ($base === '') $base = 'flipbook';
	$slug = $base;
	$i = 1;
	$books = FlipBook_get_books();
	while (true) {
		$clash = false;
		foreach ($books as $book) {
			if (($book['slug'] ?? null) === $slug && (int)($book['id'] ?? 0) !== (int)$exclude_id) {
				$clash = true;
				break;
			}
		}
		if (!$clash) break;
		$slug = $base . '-' . $i++;
	}
	return $slug;
}

function FlipBook_save_book($id, $book) {
	$file = FlipBook_DATA_PATH . (int)$id . '.json';
	file_put_contents($file, json_encode($book, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function FlipBook_delete_book($id) {
	$file = FlipBook_DATA_PATH . (int)$id . '.json';
	if (file_exists($file)) unlink($file);
}

// ─────────────────────────────────────────────────────────────────────────────
// UPLOADS  (shared file browser: images now, PDFs added in a later phase)
// ─────────────────────────────────────────────────────────────────────────────

function FlipBook_allowed_ext($type) {
	if ($type === 'pdf') return ['pdf'];
	return ['jpg', 'jpeg', 'png', 'gif', 'webp'];
}

function FlipBook_thumb_url($filename) {
	$parts    = explode('/', $filename);
	$basename = array_pop($parts);
	$subdir   = count($parts) ? implode('/', $parts) . '/' : '';
	$thumb_file = FlipBook_THUMB_DIR . $subdir . 'thumbnail.' . $basename;
	if (file_exists($thumb_file)) {
		return FlipBook_THUMB_URL . implode('/', array_map('rawurlencode', explode('/', $subdir . 'thumbnail.' . $basename)));
	}
	return FlipBook_UPLOAD_URL . implode('/', array_map('rawurlencode', explode('/', $filename)));
}

// Recursively list uploaded files of the given type ('image' | 'pdf')
function FlipBook_get_uploads($type) {
	$files   = [];
	$allowed = FlipBook_allowed_ext($type);
	$base    = FlipBook_UPLOAD_DIR;

	$scan = function ($dir, $rel) use (&$scan, &$files, $allowed, $base, $type) {
		if (!is_dir($dir)) return;
		foreach (scandir($dir) as $file) {
			if ($file[0] === '.') continue;
			$full = $dir . $file;
			$path = $rel ? $rel . '/' . $file : $file;
			if (is_dir($full)) {
				$scan($full . '/', $path);
			} else {
				$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
				if (!in_array($ext, $allowed, true)) continue;
				$files[] = [
					'filename' => $path,
					'url'      => FlipBook_UPLOAD_URL . implode('/', array_map('rawurlencode', explode('/', $path))),
					'thumb'    => $type === 'image' ? FlipBook_thumb_url($path) : '',
					'folder'   => $rel ?: '',
					'basename' => $file,
				];
			}
		}
	};

	$scan($base, '');
	usort($files, fn($a, $b) => strcmp($a['filename'], $b['filename']));
	return $files;
}

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN PAGE  (entry point called by GS - routes to sub-pages)
// ─────────────────────────────────────────────────────────────────────────────

function FlipBook_admin_page() {
	$view = $_GET['fb_view'] ?? 'list';

	if ($view === 'edit') {
		require FlipBook_PLUGIN_DIR . 'admin/edit.php';
	} else {
		require FlipBook_PLUGIN_DIR . 'admin/list.php';
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN AJAX ENDPOINTS  (only reached from FlipBook_admin_page())
// ─────────────────────────────────────────────────────────────────────────────

function FlipBook_handle_ajax($action) {
	header('Content-Type: application/json');

	// When an uploaded file's request body exceeds php.ini's post_max_size,
	// PHP silently empties BOTH $_POST and $_FILES - no warning reaches this
	// code. Left unchecked, that looks identical to a bad nonce ($_POST['nonce']
	// is just as empty), which is a confusing error to debug from. Catch the
	// specific signature here and say what's actually wrong.
	if ($action === 'upload_file' && empty($_POST) && empty($_FILES) && !empty($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
		echo json_encode(['ok' => false, 'msg' => i18n_r(FlipBook_SLUG . '/lang_Upload_Too_Large_For_Server')]);
		exit;
	}

	$readonly = ['list_books', 'get_book', 'list_uploads'];
	if (!in_array($action, $readonly, true)) {
		$nonce = $_POST['nonce'] ?? '';
		if (!check_nonce($nonce, 'flipbook', FlipBook_SLUG . '.php')) {
			$response = ['ok' => false, 'msg' => i18n_r(FlipBook_SLUG . '/lang_Invalid_Nonce')];
			// Temporary diagnostics for the upload_file nonce mystery - safe to expose
			// here since this endpoint already requires an authenticated admin session,
			// same as every other action. Remove once this is solved.
			if ($action === 'upload_file') {
				$response['debug'] = [
					'nonce_received'     => $nonce,
					'nonce_expected_now' => get_nonce('flipbook', FlipBook_SLUG . '.php'),
					'post_keys'          => array_keys($_POST),
					'files_keys'         => array_keys($_FILES),
					'content_length'     => $_SERVER['CONTENT_LENGTH'] ?? null,
					'post_max_size'      => ini_get('post_max_size'),
					'upload_max_filesize' => ini_get('upload_max_filesize'),
				];
			}
			echo json_encode($response);
			exit;
		}
	}

	switch ($action) {

		case 'list_books': {
			echo json_encode(['ok' => true, 'rows' => FlipBook_get_books()]);
			exit;
		}

		case 'get_book': {
			$id   = (int)($_GET['id'] ?? 0);
			$book = FlipBook_get_book($id);
			echo $book
				? json_encode(['ok' => true, 'book' => $book])
				: json_encode(['ok' => false]);
			exit;
		}

		case 'save_book': {
			$id   = (int)($_POST['id'] ?? 0);
			$name = trim(strip_tags($_POST['name'] ?? ''));
			if ($name === '') {
				echo json_encode(['ok' => false, 'msg' => i18n_r(FlipBook_SLUG . '/lang_Name_Required')]);
				exit;
			}

			$settings = FlipBook_sanitize_settings($_POST['settings'] ?? []);
			$pages    = FlipBook_sanitize_pages(json_decode($_POST['pages'] ?? '[]', true));
			$now = time();

			if ($id) {
				$existing = FlipBook_get_book($id);
				if (!$existing) {
					echo json_encode(['ok' => false, 'msg' => i18n_r(FlipBook_SLUG . '/lang_Not_Found')]);
					exit;
				}
				$slug = FlipBook_unique_slug($name, $id);
				$book = [
					'id'       => $id,
					'name'     => $name,
					'slug'     => $slug,
					'settings' => $settings,
					'pages'    => $pages,
					'created'  => $existing['created'] ?? $now,
					'updated'  => $now,
				];
			} else {
				$id   = FlipBook_next_id();
				$slug = FlipBook_unique_slug($name);
				$book = [
					'id'       => $id,
					'name'     => $name,
					'slug'     => $slug,
					'settings' => $settings,
					'pages'    => $pages,
					'created'  => $now,
					'updated'  => $now,
				];
			}

			FlipBook_save_book($id, $book);
			echo json_encode(['ok' => true, 'id' => $id, 'name' => $name, 'slug' => $slug]);
			exit;
		}

		case 'delete_book': {
			$id = (int)($_POST['id'] ?? 0);
			FlipBook_delete_book($id);
			echo json_encode(['ok' => true]);
			exit;
		}

		case 'list_uploads': {
			$type = ($_GET['type'] ?? 'image') === 'pdf' ? 'pdf' : 'image';
			echo json_encode(['ok' => true, 'files' => FlipBook_get_uploads($type)]);
			exit;
		}

		case 'upload_file': {
			$type = ($_POST['type'] ?? 'image') === 'pdf' ? 'pdf' : 'image';
			if (empty($_FILES['file'])) {
				echo json_encode(['ok' => false, 'msg' => i18n_r(FlipBook_SLUG . '/lang_No_File')]);
				exit;
			}
			$file = $_FILES['file'];

			if ($type === 'image') {
				$allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
				$max_bytes    = 5 * 1024 * 1024;
			} else {
				$allowed_mime = ['application/pdf'];
				$max_bytes    = 25 * 1024 * 1024;
			}

			$mime = mime_content_type($file['tmp_name']);
			if (!in_array($mime, $allowed_mime, true)) {
				echo json_encode(['ok' => false, 'msg' => i18n_r(FlipBook_SLUG . '/lang_Invalid_File_Type')]);
				exit;
			}
			if ($file['size'] > $max_bytes) {
				echo json_encode(['ok' => false, 'msg' => i18n_r(FlipBook_SLUG . '/lang_File_Too_Large')]);
				exit;
			}

			$ext          = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$allowed_exts = FlipBook_allowed_ext($type);
			$safe_ext     = in_array($ext, $allowed_exts, true) ? $ext : $allowed_exts[0];

			$base = strtolower(pathinfo($file['name'], PATHINFO_FILENAME));
			$base = preg_replace('/[^a-z0-9._-]/', '-', $base);
			$base = preg_replace('/-+/', '-', trim($base, '-'));
			$base = $base ?: ($type === 'pdf' ? 'document' : 'image');

			$filename = $base . '.' . $safe_ext;
			if (file_exists(FlipBook_UPLOAD_DIR . $filename)) {
				$i = 1;
				while (file_exists(FlipBook_UPLOAD_DIR . $base . '-' . $i . '.' . $safe_ext)) { $i++; }
				$filename = $base . '-' . $i . '.' . $safe_ext;
			}

			$dest = FlipBook_UPLOAD_DIR . $filename;
			if (!move_uploaded_file($file['tmp_name'], $dest)) {
				echo json_encode(['ok' => false, 'msg' => i18n_r(FlipBook_SLUG . '/lang_Upload_Failed')]);
				exit;
			}

			if ($type === 'image') {
				// Same thumbnail generation route Items Manager uses - absolute require
				// since a relative one fails from plugin context.
				$imgmanip = GSADMININCPATH . 'imagemanipulation.php';
				if (file_exists($imgmanip)) {
					require_once($imgmanip);
					genStdThumb('', $filename);
				}
			}

			echo json_encode([
				'ok'       => true,
				'filename' => $filename,
				'url'      => FlipBook_UPLOAD_URL . implode('/', array_map('rawurlencode', explode('/', $filename))),
				'thumb'    => $type === 'image' ? FlipBook_thumb_url($filename) : '',
			]);
			exit;
		}

		default:
			echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
			exit;
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// FRONT-END RENDERING
// ─────────────────────────────────────────────────────────────────────────────

function FlipBook_shortcode_filter($content) {
	return preg_replace_callback(
		'/\[flipbook\s+([^\]]+)\]/i',
		function ($m) {
			preg_match_all('/(\w+)=["\']?([^"\')\s]+)["\']?/', $m[1], $attrs);
			$a = array_combine($attrs[1], $attrs[2]);
			$book = null;
			if (!empty($a['id']))       $book = FlipBook_get_book((int)$a['id']);
			elseif (!empty($a['slug'])) $book = FlipBook_get_book_by_slug($a['slug']);
			return $book ? FlipBook_render($book) : '';
		},
		$content
	);
}

// Template tag for direct use in theme files: get_FlipBook('my-slug');
function get_FlipBook($id_or_slug) {
	$book = is_numeric($id_or_slug) ? FlipBook_get_book((int)$id_or_slug) : FlipBook_get_book_by_slug($id_or_slug);
	if ($book) echo FlipBook_render($book);
}

// Renders the container div only - safe to return straight from the content
// filter since it's plain HTML with no <script>/<link> tags to be stripped.
// The actual book gets built client-side by frontend.js reading data-flipbook.
function FlipBook_render($book) {
	global $FlipBook_page_has_embed;
	$FlipBook_page_has_embed = true;

	static $instance = 0;
	$instance++;
	$container_id = 'flipbook-' . (int)$book['id'] . '-' . $instance;

	if (empty($book['pages'])) {
		return '<div class="flipbook-embed flipbook-empty" id="' . $container_id . '">This flip-book doesn\'t have any pages yet.</div>';
	}

	$payload = [
		'settings'  => $book['settings'],
		'pages'     => $book['pages'],
		'uploadUrl' => FlipBook_UPLOAD_URL,
		'soundUrl'  => FlipBook_PLUGIN_URL . 'audio/flip.mp3',
	];

	return '<div class="flipbook-embed" id="' . $container_id . '" data-flipbook="'
		. htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '"></div>';
}

// Loads StPageFlip, PDF.js, and frontend.js/css - only on pages that actually
// used a flip-book (the flag is set by FlipBook_render() above, which only runs
// when the shortcode/template tag matched something).
function FlipBook_frontend_assets() {
	global $FlipBook_page_has_embed;
	if (empty($FlipBook_page_has_embed)) return;
	?>
<link rel="stylesheet" href="<?= FlipBook_PLUGIN_URL ?>css/frontend.css">
<script src="<?= FlipBook_PLUGIN_URL ?>js/page-flip.browser.js"></script>
<script type="module">
	// Same reasoning as the admin side: pdf.min.mjs is an ES module, so it has
	// to be imported here and exposed as a normal global for the classic
	// frontend.js script below to use.
	import * as pdfjsLib from '<?= FlipBook_PLUGIN_URL ?>js/pdf.min.mjs';
	window.pdfjsLib = pdfjsLib;
</script>
<script>
	window.FlipBookFrontend = { pdfWorkerUrl: <?= json_encode(FlipBook_PLUGIN_URL . 'js/pdf.worker.min.mjs') ?> };
</script>
<script src="<?= FlipBook_PLUGIN_URL ?>js/frontend.js"></script>
	<?php
}
