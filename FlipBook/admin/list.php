<?php if (!defined('IN_GS')) { die('you cannot load this page directly.'); } ?>
<div class="fb-admin">
	<div class="fb-admin-header">
		<h1><svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;" width="26" height="26" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path fill="#c30" d="m19 1l-5 5v11l5-4.5zm2 4v13.5c-1.1-.35-2.3-.5-3.5-.5c-1.7 0-4.15.65-5.5 1.5V6c-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5c.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5c1.35-.85 3.8-1.5 5.5-1.5c1.65 0 3.35.3 4.75 1.05c.1.05.15.05.25.05c.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1M10 18.41C8.75 18.09 7.5 18 6.5 18c-1.06 0-2.32.19-3.5.5V7.13c.91-.4 2.14-.63 3.5-.63s2.59.23 3.5.63z"/></svg> <?php i18n(FlipBook_SLUG . '/lang_List_Title'); ?> </h1>
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
		saveError: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Save_Error')) ?>,
		pageSingular: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Page_Singular')) ?>,
		pagePlural: <?= json_encode(i18n_r(FlipBook_SLUG . '/lang_Page_Plural')) ?>
	}
};
</script>
<link rel="stylesheet" href="<?= FlipBook_PLUGIN_URL ?>css/admin.css">
<script src="<?= FlipBook_PLUGIN_URL ?>js/admin.js"></script>
