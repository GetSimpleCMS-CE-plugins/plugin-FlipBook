<?php if (!defined('IN_GS')) { die('you cannot load this page directly.'); } ?>
<div class="fb-admin">
	<div class="fb-admin-header">
		<h1><?php i18n(FlipBook_SLUG . '/lang_List_Title'); ?></h1>
		<a href="?id=<?= FlipBook_SLUG ?>&amp;fb_view=edit" class="fb-btn fb-btn-primary">+ <?php i18n(FlipBook_SLUG . '/lang_New_Book'); ?></a>
	</div>

	<table class="fb-table" id="fb-book-list">
		<thead>
			<tr>
				<th><?php i18n(FlipBook_SLUG . '/lang_Name'); ?></th>
				<th><?php i18n(FlipBook_SLUG . '/lang_Slug'); ?></th>
				<th><?php i18n(FlipBook_SLUG . '/lang_Updated'); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<tr><td colspan="4" class="fb-muted"><?php i18n(FlipBook_SLUG . '/lang_Loading'); ?></td></tr>
		</tbody>
	</table>

	<details class="fb-help">
		<summary><?php i18n(FlipBook_SLUG . '/lang_Help_Title'); ?></summary>
		<div class="fb-help-body">
			<p><?php i18n(FlipBook_SLUG . '/lang_Help_Intro'); ?></p>
			<p><code>[flipbook id="1"]</code></p>
			<p><code>[flipbook slug="your-slug"]</code></p>
			<p><?php i18n(FlipBook_SLUG . '/lang_Help_Template_Intro'); ?></p>
			<p><code>&lt;?php get_FlipBook('your-slug'); ?&gt;</code></p>
			<p class="fb-muted"><?php i18n(FlipBook_SLUG . '/lang_Help_Note'); ?></p>
		</div>
	</details>
</div>

<script>
var FlipBookData = {
	ajaxUrl: '?id=<?= FlipBook_SLUG ?>',
	nonce: '<?= get_nonce('flipbook', FlipBook_SLUG . '.php') ?>',
	i18n: {
		noBooks: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_No_Books')) ?>,
		confirmDelete: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Confirm_Delete')) ?>,
		edit: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Edit')) ?>,
		delete: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Delete')) ?>,
		saveError: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Save_Error')) ?>
	}
};
</script>
<link rel="stylesheet" href="<?= FlipBook_PLUGIN_URL ?>css/admin.css">
<script src="<?= FlipBook_PLUGIN_URL ?>js/admin.js"></script>
