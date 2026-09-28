<?php if (!defined('IN_GS')) { die('you cannot load this page directly.'); }

$fb_id   = (int)($_GET['fb_id'] ?? 0);
$fb_book = $fb_id ? FlipBook_get_book($fb_id) : null;

if ($fb_id && !$fb_book) {
	?>
	<div class="fb-admin">
		<div class="fb-admin-header">
			<h1><?php i18n(FlipBook_SLUG . '/lang_New_Book'); ?></h1>
			<a href="?id=<?= FlipBook_SLUG ?>" class="fb-btn fb-btn-secondary">&laquo; <?php i18n(FlipBook_SLUG . '/lang_Back_To_List'); ?></a>
		</div>
		<p class="fb-err"><?php i18n(FlipBook_SLUG . '/lang_Not_Found'); ?></p>
	</div>
	<?php
	return;
}

$fb_settings = array_merge(FlipBook_default_settings(), $fb_book['settings'] ?? []);
$fb_pages    = $fb_book['pages']    ?? [];
?>
<div class="fb-admin">
	<div class="fb-admin-header">
		<h1 id="fb-book-title"><?= $fb_book ? htmlspecialchars($fb_book['name']) : i18n_r(FlipBook_SLUG . '/lang_New_Book') ?></h1>
		<a href="?id=<?= FlipBook_SLUG ?>" class="fb-btn fb-btn-secondary">&laquo; <?php i18n(FlipBook_SLUG . '/lang_Back_To_List'); ?></a>
	</div>

	<form id="fb-edit-form" class="fb-form">
		<input type="hidden" name="id" value="<?= $fb_id ?>">
		<input type="hidden" name="nonce" value="<?= get_nonce('flipbook', FlipBook_SLUG . '.php') ?>">
		<input type="hidden" name="pages" id="fb-pages-json" value="<?= htmlspecialchars(json_encode($fb_pages)) ?>">

		<div class="fb-field">
			<label><?php i18n(FlipBook_SLUG . '/lang_Name'); ?></label>
			<input type="text" name="name" value="<?= $fb_book ? htmlspecialchars($fb_book['name']) : '' ?>" required>
		</div>

		<div class="fb-field" id="fb-slug-row" <?= $fb_book ? '' : 'style="display:none"' ?>>
			<label><?php i18n(FlipBook_SLUG . '/lang_Slug'); ?></label>
			<input type="text" id="fb-slug-display" value="<?= $fb_book ? htmlspecialchars($fb_book['slug']) : '' ?>" disabled>
		</div>

		<fieldset class="fb-fieldset">
			<legend><?php i18n(FlipBook_SLUG . '/lang_Settings'); ?></legend>

			<div class="fb-field-inline">
				<label><?php i18n(FlipBook_SLUG . '/lang_Width'); ?></label>
				<input type="number" name="settings[width]" min="100" value="<?= (int)$fb_settings['width'] ?>">
				<label><?php i18n(FlipBook_SLUG . '/lang_Height'); ?></label>
				<input type="number" name="settings[height]" min="100" value="<?= (int)$fb_settings['height'] ?>">
			</div>

			<div class="fb-field">
				<label><?php i18n(FlipBook_SLUG . '/lang_Size_Mode'); ?></label>
				<select name="settings[size]">
					<option value="fixed"   <?= $fb_settings['size'] === 'fixed'   ? 'selected' : '' ?>><?php i18n(FlipBook_SLUG . '/lang_Size_Fixed'); ?></option>
					<option value="stretch" <?= $fb_settings['size'] === 'stretch' ? 'selected' : '' ?>><?php i18n(FlipBook_SLUG . '/lang_Size_Stretch'); ?></option>
				</select>
			</div>

			<label class="fb-checkbox">
				<input type="checkbox" name="settings[show_cover]" <?= !empty($fb_settings['show_cover']) ? 'checked' : '' ?>>
				<?php i18n(FlipBook_SLUG . '/lang_Show_Cover'); ?>
			</label>
			<label class="fb-checkbox">
				<input type="checkbox" name="settings[sound]" <?= !empty($fb_settings['sound']) ? 'checked' : '' ?>>
				<?php i18n(FlipBook_SLUG . '/lang_Sound'); ?>
			</label>
			<label class="fb-checkbox">
				<input type="checkbox" name="settings[single_page_mobile]" <?= !empty($fb_settings['single_page_mobile']) ? 'checked' : '' ?>>
				<?php i18n(FlipBook_SLUG . '/lang_Single_Page_Mobile'); ?>
			</label>
			<label class="fb-checkbox">
				<input type="checkbox" name="settings[show_page_numbers]" <?= !empty($fb_settings['show_page_numbers']) ? 'checked' : '' ?>>
				<?php i18n(FlipBook_SLUG . '/lang_Show_Page_Numbers'); ?>
			</label>
			<label class="fb-checkbox">
				<input type="checkbox" name="settings[show_nav_buttons]" <?= !empty($fb_settings['show_nav_buttons']) ? 'checked' : '' ?>>
				<?php i18n(FlipBook_SLUG . '/lang_Show_Nav_Buttons'); ?>
			</label>
		</fieldset>

		<fieldset class="fb-fieldset">
			<legend><?php i18n(FlipBook_SLUG . '/lang_Pages'); ?></legend>

			<div class="fb-add-page-row">
				<button type="button" class="fb-btn fb-btn-secondary" onclick="fbAddImagePage()">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
					<?php i18n(FlipBook_SLUG . '/lang_Add_Image_Page'); ?>
				</button>
				<button type="button" class="fb-btn fb-btn-secondary" onclick="fbAddPdfPage()">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
					<?php i18n(FlipBook_SLUG . '/lang_Add_Pdf_Page'); ?>
				</button>
				<button type="button" class="fb-btn fb-btn-secondary" onclick="fbOpenTextPageModal()">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/><line x1="3" y1="21" x2="21" y2="21"/></svg>
					<?php i18n(FlipBook_SLUG . '/lang_Add_Image_Text_Page'); ?>
				</button>
			</div>

			<p class="fb-muted fb-pages-empty-hint" id="fb-pages-empty-hint" <?= $fb_pages ? 'style="display:none"' : '' ?>>
				<?php i18n(FlipBook_SLUG . '/lang_No_Pages'); ?>
			</p>

			<div class="fb-pages-list" id="fb-pages-list"></div>
		</fieldset>

		<button type="submit" class="fb-btn fb-btn-primary"><?php i18n(FlipBook_SLUG . '/lang_Save'); ?></button>
		<span id="fb-save-status"></span>
	</form>
</div>

<!-- PDF page picker: shows rendered thumbnails of every page in the chosen PDF -->
<div class="fb-modal-bg" id="fb-pdfpicker-bg" style="display:none">
	<div class="fb-modal fb-modal-lg">
		<div class="fb-modal-head">
			<span><?php i18n(FlipBook_SLUG . '/lang_Select_Pages'); ?></span>
			<button type="button" class="fb-modal-x" onclick="fbClosePdfPicker()">&times;</button>
		</div>
		<div class="fb-modal-body">
			<p class="fb-muted" id="fb-pdfpicker-status"></p>
			<div class="fb-pdfpicker-grid" id="fb-pdfpicker-grid"></div>
			<div class="fb-modal-footer">
				<button type="button" class="fb-btn fb-btn-secondary" onclick="fbSelectAllPdfPages()"><?php i18n(FlipBook_SLUG . '/lang_Select_All'); ?></button>
				<button type="button" class="fb-btn fb-btn-secondary" onclick="fbClosePdfPicker()"><?php i18n(FlipBook_SLUG . '/lang_Cancel'); ?></button>
				<button type="button" class="fb-btn fb-btn-primary" onclick="fbInsertPdfPages()"><?php i18n(FlipBook_SLUG . '/lang_Insert_Selected_Pages'); ?></button>
			</div>
		</div>
	</div>
</div>

<!-- File browser modal (shared by Image, Image+Text, and PDF page types) -->
<div class="fb-modal-bg" id="fb-filebrowser-bg" style="display:none">
	<div class="fb-modal fb-modal-lg">
		<div class="fb-modal-head">
			<span><?php i18n(FlipBook_SLUG . '/lang_Select_Upload'); ?></span>
			<button type="button" class="fb-modal-x" onclick="fbFbClose()">&times;</button>
		</div>
		<div class="fb-modal-body">
			<div class="fb-fb-tabs">
				<button type="button" class="fb-fb-tab active" onclick="fbFbTab('browse', this)"><?php i18n(FlipBook_SLUG . '/lang_Browse_Uploads'); ?></button>
				<button type="button" class="fb-fb-tab" onclick="fbFbTab('upload', this)"><?php i18n(FlipBook_SLUG . '/lang_Upload_New'); ?></button>
			</div>

			<div id="fb-fb-browse">
				<div class="fb-fb-crumb" id="fb-fb-crumb"></div>
				<input type="text" id="fb-fb-search" class="fb-fb-search" placeholder="<?php i18n(FlipBook_SLUG . '/lang_Filter_Files'); ?>" oninput="fbFbRenderGrid()">
				<div class="fb-fb-grid" id="fb-fb-grid">
					<div class="fb-muted"><?php i18n(FlipBook_SLUG . '/lang_Loading'); ?></div>
				</div>
			</div>

			<div id="fb-fb-upload" style="display:none">
				<div class="fb-fb-dropzone">
					<p><?php i18n(FlipBook_SLUG . '/lang_Choose_File'); ?></p>
					<label class="fb-btn fb-btn-secondary" for="fb-fb-file-input"><?php i18n(FlipBook_SLUG . '/lang_Choose_File'); ?></label>
					<input type="file" id="fb-fb-file-input" style="display:none" onchange="fbFbUpload(this.files)">
				</div>
				<div id="fb-fb-upload-progress" class="fb-muted" style="display:none"></div>
			</div>
		</div>
	</div>
</div>

<!-- Image + Text compose modal -->
<div class="fb-modal-bg" id="fb-textpage-bg" style="display:none">
	<div class="fb-modal fb-modal-lg">
		<div class="fb-modal-head">
			<span id="fb-textpage-title"><?php i18n(FlipBook_SLUG . '/lang_Add_Image_Text_Page'); ?></span>
			<button type="button" class="fb-modal-x" onclick="fbCloseTextPageModal()">&times;</button>
		</div>
		<div class="fb-modal-body">
			<div class="fb-field">
				<label><?php i18n(FlipBook_SLUG . '/lang_Image'); ?></label>
				<div class="fb-textpage-image-picker">
					<img id="fb-textpage-preview" style="display:none">
					<button type="button" class="fb-btn fb-btn-secondary" onclick="fbFbOpen('image', 'textpage')"><?php i18n(FlipBook_SLUG . '/lang_Choose_Image'); ?></button>
				</div>
			</div>
			<div class="fb-field">
				<label><?php i18n(FlipBook_SLUG . '/lang_Text_Position'); ?></label>
				<div class="fb-radio-row">
					<label class="fb-radio-opt active"><input type="radio" name="fb_text_position" value="below" checked> <?php i18n(FlipBook_SLUG . '/lang_Below_Image'); ?></label>
					<label class="fb-radio-opt"><input type="radio" name="fb_text_position" value="above"> <?php i18n(FlipBook_SLUG . '/lang_Above_Image'); ?></label>
				</div>
			</div>
			<div class="fb-field">
				<label><?php i18n(FlipBook_SLUG . '/lang_Text'); ?></label>
				<div id="fb-textpage-editor-wrap">
					<textarea id="fb-textpage-editor" class="fb-autumnnote-editor"></textarea>
				</div>
			</div>
			<div class="fb-modal-footer">
				<button type="button" class="fb-btn fb-btn-secondary" onclick="fbCloseTextPageModal()"><?php i18n(FlipBook_SLUG . '/lang_Cancel'); ?></button>
				<button type="button" class="fb-btn fb-btn-primary" id="fb-textpage-save-btn" onclick="fbInsertTextPage()"><?php i18n(FlipBook_SLUG . '/lang_Insert_Page'); ?></button>
			</div>
		</div>
	</div>
</div>

<script>
var FlipBookData = {
	ajaxUrl: '?id=<?= FlipBook_SLUG ?>',
	nonce: '<?= get_nonce('flipbook', FlipBook_SLUG . '.php') ?>',
	uploadUrl: <?= json_encode(FlipBook_UPLOAD_URL) ?>,
	thumbUrl: <?= json_encode(FlipBook_THUMB_URL) ?>,
	pdfWorkerUrl: <?= json_encode(FlipBook_PLUGIN_URL . 'js/pdf.worker.min.mjs') ?>,
	isNew: <?= $fb_book ? 'false' : 'true' ?>,
	pages: <?= json_encode($fb_pages) ?>,
	i18n: {
		saved: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Saved')) ?>,
		saveError: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Save_Error')) ?>,
		loading: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Loading')) ?>,
		noFiles: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_No_Files')) ?>,
		uploading: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Uploading')) ?>,
		remove: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Remove')) ?>,
		edit: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Edit')) ?>,
		imagePage: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Image_Page')) ?>,
		imageTextPage: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Image_Text_Page')) ?>,
		pdfPage: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Pdf_Page')) ?>,
		addTitle: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Add_Image_Text_Page')) ?>,
		editTitle: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Edit_Image_Text_Page')) ?>,
		insertPage: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Insert_Page')) ?>,
		saveChanges: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Save_Changes')) ?>,
		loadingPdf: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Loading_Pdf')) ?>,
		renderingPage: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Rendering_Page')) ?>,
		pdfLoadError: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Pdf_Load_Error')) ?>,
		pdfjsNotLoaded: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Pdfjs_Not_Loaded')) ?>,
		noPagesSelected: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_No_Pages_Selected')) ?>
	}
};
</script>
<link rel="stylesheet" href="<?= FlipBook_PLUGIN_URL ?>css/admin.css">
<link rel="stylesheet" href="<?= FlipBook_PLUGIN_URL ?>css/autumnnote.css">
<script src="<?= FlipBook_PLUGIN_URL ?>js/autumnnote.umd.js"></script>
<script src="<?= FlipBook_PLUGIN_URL ?>js/Sortable.min.js"></script>
<script type="module">
	// pdf.min.mjs is an ES module (current PDF.js builds no longer ship a
	// plain global-exposing script) - import it here and hang it off window
	// so the classic admin.js script below can use it as a normal global.
	import * as pdfjsLib from '<?= FlipBook_PLUGIN_URL ?>js/pdf.min.mjs';
	window.pdfjsLib = pdfjsLib;
</script>
<script src="<?= FlipBook_PLUGIN_URL ?>js/admin.js"></script>
