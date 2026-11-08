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
	
	<footer class="donate">
		<p>Support my plugin development: <a href="https://ko-fi.com/ericmontgomery" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" width="1.5em" height="1.5em" viewBox="0 0 36 36"><path d="M0 0h36v36H0z" fill="none"/><ellipse cx="18" cy="30.54" fill="#3b88c3" rx="18" ry="5.36"/><path fill="#88c9f9" d="M33.812 28.538c0 1.616-2.5 2.587-14.482 2.587c-10.925 0-13.612-.971-13.612-2.587s5.683-2.926 13.612-2.926s14.482 1.31 14.482 2.926"/><path fill="#f4900c" d="M7 28.25c0-1 1-5 11-5c12 0 15 4 15 5s0 2-14 2c-12 0-12-1-12-2"/><circle cx="7" cy="6.25" r="5" fill="#ffcc4d"/><circle cx="7" cy="6.25" r="4" fill="#ffac33"/><path fill="#c1694f" d="M26.949 14.467a21 21 0 0 0-.524-3.459a22 22 0 0 0-1.001-3.158c-.377-.929-.674-1.46-.674-1.46l-2.583.333s.298.622.621 1.841c.131.495.265 1.09.386 1.784a26 26 0 0 1 .343 2.948q.036.555.053 1.158c.004.146.011.287.013.437c.007.469-.026.946-.084 1.422q-.081.644-.223 1.279a19 19 0 0 1-.479 1.77c-.149.46-.308.901-.468 1.316a35 35 0 0 1-.707 1.687c-.358.798-.622 1.33-.622 1.441c0 .351.25 1.007 2 1.444c2.736.684 3-.444 3-.444s.174-.528.376-1.567c.1-.513.206-1.147.303-1.915q.088-.689.158-1.513a44 44 0 0 0 .157-3.478c.002-.176.006-.346.006-.527c0-.459-.021-.903-.051-1.339"/><path fill="#d99e82" d="M24.324 21.362c-1.003-.175-1.643-.467-1.994-.686a35 35 0 0 1-.707 1.687c.655.356 1.487.64 2.389.796a9 9 0 0 0 1.523.136c.296 0 .574-.023.841-.057c.1-.513.206-1.147.303-1.915c-.318.113-1.107.257-2.355.039m.758-3.342c-.782-.077-1.383-.249-1.805-.43a19 19 0 0 1-.479 1.77a8.4 8.4 0 0 0 2.106.476q.462.045.908.045c.359 0 .7-.028 1.026-.071q.074-.857.118-1.863c-.45.098-1.08.152-1.874.073m.221-1.524c.588 0 1.165-.059 1.692-.163c.001-.176.005-.346.005-.527c0-.458-.021-.902-.051-1.339c-.378.099-.919.194-1.682.205c-.775-.003-1.32-.111-1.698-.219c.004.146.011.287.013.437c.007.469-.026.946-.084 1.422a9 9 0 0 0 1.76.184zm-.178-3.273a8.7 8.7 0 0 0 1.648-.418a22 22 0 0 0-.347-1.797a6.4 6.4 0 0 1-1.56.409a6.5 6.5 0 0 1-1.522.055q.109.838.173 1.823c.109.004.207.021.319.021c.42 0 .857-.031 1.289-.093m.909-3.658a22 22 0 0 0-.61-1.715a6.7 6.7 0 0 1-1.242.471a6.6 6.6 0 0 1-1.395.243c.131.495.265 1.09.386 1.784a9 9 0 0 0 1.497-.269a9 9 0 0 0 1.364-.514"/><path fill="#3e721d" d="M34.549 2.943c-.032-.042-3.202-4.283-7.313-2.423c-1.847.835-3.038 2.2-3.469 3.963c-.804-1.125-2.026-1.839-3.644-2.127c-4.421-.788-7.092 4.123-7.118 4.172a.174.174 0 0 0 .256.224c.02-.015 2.037-1.427 5.396-.828a97 97 0 0 1 5.864 1.217a.18.18 0 0 0 .172-.051c.022-.024 2.303-2.475 4.4-3.423c3.104-1.404 5.221-.472 5.24-.462a.176.176 0 0 0 .216-.262"/><path fill="#5c913b" d="M31.129 5.401c-3.134-1.655-5.57-.831-7.083.174c-1.813-.351-5.201-.357-8.072 3.324c-3.525 4.518-.433 10.152-.402 10.209c.031.056.09.089.153.089l.021-.001a.18.18 0 0 0 .147-.128c.011-.039 1.097-3.967 3.843-7.27c1.501-1.805 3.293-3.474 4.554-4.573c1.27.211 3.136.678 4.969 1.702c3.573 1.998 5.212 5.379 5.228 5.413c.032.066.101.112.177.099a.17.17 0 0 0 .151-.134c.014-.063 1.392-6.222-3.686-8.904"/></svg> RisingIsland</a></p>
	</footer>

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
