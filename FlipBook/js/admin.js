(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') fn();
		else document.addEventListener('DOMContentLoaded', fn);
	}

	function esc(str) {
		return String(str == null ? '' : str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

	// ─────────────────────────────────────────────────────────────────────────
	// LIST VIEW
	// ─────────────────────────────────────────────────────────────────────────

	function initList() {
		var table = document.getElementById('fb-book-list');
		if (!table) return;
		var tbody = table.querySelector('tbody');

		fetch(FlipBookData.ajaxUrl + '&fb_action=list_books')
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res.ok) {
					tbody.innerHTML = '<tr><td colspan="4" class="fb-err">' + FlipBookData.i18n.saveError + '</td></tr>';
					return;
				}
				if (!res.rows.length) {
					tbody.innerHTML = '<tr><td colspan="4" class="fb-muted">' + FlipBookData.i18n.noBooks + '</td></tr>';
					return;
				}
				tbody.innerHTML = '';
				res.rows.forEach(function (book) { tbody.appendChild(buildRow(book)); });
			})
			.catch(function () {
				tbody.innerHTML = '<tr><td colspan="4" class="fb-err">' + FlipBookData.i18n.saveError + '</td></tr>';
			});
	}

	function editUrl(id) {
		return FlipBookData.ajaxUrl + '&fb_view=edit&fb_id=' + encodeURIComponent(id);
	}

	function buildRow(book) {
		var tr = document.createElement('tr');

		var tdName = document.createElement('td');
		var link = document.createElement('a');
		link.href = editUrl(book.id);
		link.textContent = book.name;
		tdName.appendChild(link);

		var tdSlug = document.createElement('td');
		tdSlug.textContent = book.slug;

		var tdUpdated = document.createElement('td');
		tdUpdated.textContent = book.updated ? new Date(book.updated * 1000).toLocaleString() : '';

		var tdActions = document.createElement('td');
		tdActions.className = 'fb-actions';

		var editBtn = document.createElement('a');
		editBtn.href = editUrl(book.id);
		editBtn.className = 'fb-btn fb-btn-secondary';
		editBtn.textContent = FlipBookData.i18n.edit;
		tdActions.appendChild(editBtn);

		var delBtn = document.createElement('button');
		delBtn.type = 'button';
		delBtn.className = 'fb-btn fb-btn-danger';
		delBtn.textContent = FlipBookData.i18n.delete;
		delBtn.addEventListener('click', function () { deleteBook(book.id, tr); });
		tdActions.appendChild(delBtn);

		tr.appendChild(tdName);
		tr.appendChild(tdSlug);
		tr.appendChild(tdUpdated);
		tr.appendChild(tdActions);
		return tr;
	}

	function deleteBook(id, row) {
		if (!window.confirm(FlipBookData.i18n.confirmDelete)) return;
		var body = new FormData();
		body.append('id', id);
		body.append('nonce', FlipBookData.nonce);
		fetch(FlipBookData.ajaxUrl + '&fb_action=delete_book', { method: 'POST', body: body })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res.ok) row.remove();
				else window.alert(FlipBookData.i18n.saveError);
			})
			.catch(function () { window.alert(FlipBookData.i18n.saveError); });
	}

	// ─────────────────────────────────────────────────────────────────────────
	// EDIT VIEW — book settings form
	// ─────────────────────────────────────────────────────────────────────────

	function initEdit() {
		var form = document.getElementById('fb-edit-form');
		if (!form) return;
		var status = document.getElementById('fb-save-status');

		initPagesUI();

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (form.dataset.submitting === '1') return;
			form.dataset.submitting = '1';
			form.classList.add('fb-busy');
			status.textContent = '';
			status.className = '';

			// Pages array lives in memory (_fbPages) while editing - sync it into
			// the hidden field right before the form is serialised.
			document.getElementById('fb-pages-json').value = JSON.stringify(_fbPages);

			var body = new FormData(form);

			fetch(FlipBookData.ajaxUrl + '&fb_action=save_book', { method: 'POST', body: body })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					form.dataset.submitting = '0';
					form.classList.remove('fb-busy');

					if (!res.ok) {
						status.textContent = res.msg || FlipBookData.i18n.saveError;
						status.className = 'fb-err';
						return;
					}

					status.textContent = FlipBookData.i18n.saved;
					status.className = 'fb-ok';

					if (FlipBookData.isNew) {
						window.location.href = editUrl(res.id);
						return;
					}

					var titleEl = document.getElementById('fb-book-title');
					var slugEl  = document.getElementById('fb-slug-display');
					if (titleEl && res.name) titleEl.textContent = res.name;
					if (slugEl  && res.slug) slugEl.value = res.slug;
				})
				.catch(function () {
					form.dataset.submitting = '0';
					form.classList.remove('fb-busy');
					status.textContent = FlipBookData.i18n.saveError;
					status.className = 'fb-err';
				});
		});
	}

	// ─────────────────────────────────────────────────────────────────────────
	// PAGE BUILDER — in-memory page list, synced to the hidden field on submit
	// ─────────────────────────────────────────────────────────────────────────

	var _fbPages = [];
	var _fbSortable = null;
	var _fbTextPageEditIndex = null;

	function initPagesUI() {
		var list = document.getElementById('fb-pages-list');
		if (!list) return;
		_fbPages = Array.isArray(FlipBookData.pages) ? FlipBookData.pages.slice() : [];
		renderPagesList();
	}

	function renderPagesList() {
		var list = document.getElementById('fb-pages-list');
		var hint = document.getElementById('fb-pages-empty-hint');
		if (!list) return;

		hint.style.display = _fbPages.length ? 'none' : '';
		list.innerHTML = '';

		_fbPages.forEach(function (page, i) {
			var card = document.createElement('div');
			card.className = 'fb-page-card';
			card.dataset.index = i;

			var thumb = document.createElement('div');
			thumb.className = 'fb-page-thumb';
			if (page.type === 'pdf') {
				thumb.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
			} else {
				var img = document.createElement('img');
				img.src = FlipBookData.uploadUrl + page.file.split('/').map(encodeURIComponent).join('/');
				img.alt = '';
				thumb.appendChild(img);
			}
			card.appendChild(thumb);

			var info = document.createElement('div');
			info.className = 'fb-page-info';
			var typeLabel = document.createElement('span');
			typeLabel.className = 'fb-page-type';
			typeLabel.textContent = page.type === 'image_text' ? FlipBookData.i18n.imageTextPage
				: page.type === 'pdf' ? FlipBookData.i18n.pdfPage
				: FlipBookData.i18n.imagePage;
			info.appendChild(typeLabel);
			var fileLabel = document.createElement('span');
			fileLabel.className = 'fb-page-file';
			fileLabel.textContent = page.type === 'pdf' ? (page.file + ' \u2014 ' + page.page) : page.file;
			info.appendChild(fileLabel);
			card.appendChild(info);

			if (page.type === 'image_text') {
				var editBtn = document.createElement('button');
				editBtn.type = 'button';
				editBtn.className = 'fb-btn fb-btn-secondary fb-page-edit';
				editBtn.textContent = FlipBookData.i18n.edit;
				editBtn.addEventListener('click', function () { fbEditTextPage(i); });
				card.appendChild(editBtn);
			}

			var remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'fb-page-remove';
			remove.textContent = '\u2715';
			remove.title = FlipBookData.i18n.remove;
			remove.addEventListener('click', function () {
				_fbPages.splice(i, 1);
				renderPagesList();
			});
			card.appendChild(remove);

			list.appendChild(card);
		});

		if (_fbSortable) { try { _fbSortable.destroy(); } catch (e) {} }
		if (typeof Sortable !== 'undefined') {
			_fbSortable = Sortable.create(list, {
				animation: 150,
				ghostClass: 'fb-drag-ghost',
				onEnd: function () {
					var order = Array.from(list.querySelectorAll('.fb-page-card')).map(function (el) {
						return parseInt(el.dataset.index, 10);
					});
					_fbPages = order.map(function (i) { return _fbPages[i]; });
					renderPagesList();
				}
			});
		} else if (_fbPages.length > 1) {
			console.warn('FlipBook: Sortable is not defined - drag-to-reorder is disabled. Check that js/Sortable.min.js was copied into the plugin folder and is loading without a 404.');
		}
	}

	// "Add Page -> Image": opens the file browser, adds the page as soon as one is picked
	window.fbAddImagePage = function () {
		fbFbOpen('image', 'imagepage');
	};

	// "Add Page -> PDF": opens the file browser (pdf mode); picking a PDF opens the page picker
	window.fbAddPdfPage = function () {
		fbFbOpen('pdf', 'pdfpage');
	};

	// "Add Page -> Image + Text": opens the compose modal in "add" mode
	window.fbOpenTextPageModal = function () {
		_fbTextPageEditIndex = null;
		document.getElementById('fb-textpage-title').textContent = FlipBookData.i18n.addTitle;
		document.getElementById('fb-textpage-save-btn').textContent = FlipBookData.i18n.insertPage;
		var preview = document.getElementById('fb-textpage-preview');
		preview.style.display = 'none';
		preview.removeAttribute('src');
		preview.removeAttribute('data-file');
		document.querySelector('input[name="fb_text_position"][value="below"]').checked = true;
		resetTextPageEditor('');
		document.getElementById('fb-textpage-bg').style.display = 'flex';
	};

	// Edit an existing image_text page card in place
	window.fbEditTextPage = function (index) {
		var page = _fbPages[index];
		if (!page) return;
		_fbTextPageEditIndex = index;
		document.getElementById('fb-textpage-title').textContent = FlipBookData.i18n.editTitle;
		document.getElementById('fb-textpage-save-btn').textContent = FlipBookData.i18n.saveChanges;

		var preview = document.getElementById('fb-textpage-preview');
		preview.src = FlipBookData.uploadUrl + page.file.split('/').map(encodeURIComponent).join('/');
		preview.style.display = '';
		preview.dataset.file = page.file;

		var posValue = page.text_position === 'above' ? 'above' : 'below';
		document.querySelector('input[name="fb_text_position"][value="' + posValue + '"]').checked = true;

		resetTextPageEditor(page.text || '');
		document.getElementById('fb-textpage-bg').style.display = 'flex';
	};

	window.fbCloseTextPageModal = function () {
		document.getElementById('fb-textpage-bg').style.display = 'none';
	};

	window.fbInsertTextPage = function () {
		var preview = document.getElementById('fb-textpage-preview');
		var file = preview.dataset.file || '';
		if (!file) { window.alert(FlipBookData.i18n.noFiles); return; }
		var posEl = document.querySelector('input[name="fb_text_position"]:checked');
		var text = fbGetEditorContent(document.getElementById('fb-textpage-editor'));
		var pageData = {
			type: 'image_text',
			file: file,
			text: text,
			text_position: posEl ? posEl.value : 'below'
		};
		if (_fbTextPageEditIndex !== null) {
			_fbPages[_fbTextPageEditIndex] = pageData;
		} else {
			_fbPages.push(pageData);
		}
		_fbTextPageEditIndex = null;
		renderPagesList();
		fbCloseTextPageModal();
	};

	// ─────────────────────────────────────────────────────────────────────────
	// AUTUMNNOTE — mounted the same defensive way as the Items Manager plugin,
	// since the library's real API surface isn't documented anywhere we can rely on.
	// ─────────────────────────────────────────────────────────────────────────

	function mountAutumnNote(targetEl) {
		var lib = window.AutumnNote || window.autumnnote || {};
		var Ctor = lib.default || lib;
		var B = lib.buttons || {};

		function cleanToolbar(groups) {
			return groups.map(function (g) { return g.filter(Boolean); }).filter(function (g) { return g.length; });
		}
		var toolbar = cleanToolbar([
			[B.undoBtn, B.redoBtn],
			[B.boldBtn, B.italicBtn, B.underlineBtn, B.strikeBtn],
			[B.alignLeftBtn, B.alignCenterBtn, B.alignRightBtn],
			[B.ulBtn, B.olBtn],
		]);

		if (!targetEl || !Ctor) return null;
		var opts = toolbar.length ? { toolbar: toolbar } : {};
		var inst = null;
		try {
			if (typeof Ctor.create === 'function') {
				inst = Ctor.create(targetEl, opts);
			} else if (typeof lib.create === 'function') {
				inst = lib.create(targetEl, opts);
			} else if (typeof Ctor === 'function') {
				try { inst = new Ctor(targetEl, opts); } catch (e) { inst = Ctor(targetEl, opts); }
			}
		} catch (err) {
			console.error('AutumnNote failed to mount:', err);
		}
		if (inst) targetEl.style.setProperty('display', 'none', 'important');
		return inst;
	}

	// Rebuilds the underlying textarea from scratch and mounts a fresh AutumnNote
	// instance on it. Since we don't know whether AutumnNote exposes a reliable
	// destroy()/reset() method, the safest way to reuse the same modal for both
	// "add" and "edit" is to throw away whatever DOM AutumnNote built last time
	// and start from a brand-new <textarea> each time the modal opens.
	function resetTextPageEditor(initialValue) {
		var wrap = document.getElementById('fb-textpage-editor-wrap');
		if (!wrap) return;
		wrap.innerHTML = '';
		var el = document.createElement('textarea');
		el.id = 'fb-textpage-editor';
		el.className = 'fb-autumnnote-editor';
		el.value = initialValue || '';
		wrap.appendChild(el);
		el._autumnNoteInstance = mountAutumnNote(el);
	}

	function fbGetEditorContent(el) {
		if (!el) return '';
		var inst = el._autumnNoteInstance;
		if (inst) {
			if (typeof inst.getContent === 'function') return inst.getContent();
			if (typeof inst.getHTML === 'function') return inst.getHTML();
			if (typeof inst.value !== 'undefined') return inst.value;
		}
		var parent = el.closest('.fb-field') || el.parentElement;
		if (parent) {
			var editable = parent.querySelector('[contenteditable="true"]');
			if (editable) return editable.innerHTML;
		}
		return el.value || el.innerHTML || '';
	}

	// ─────────────────────────────────────────────────────────────────────────
	// FILE BROWSER — generic picker used by both "Add Image Page" and the
	// Image+Text modal's image picker. `context` says what to do on selection.
	// ─────────────────────────────────────────────────────────────────────────

	var _fbFiles = [];
	var _fbFolder = '';
	var _fbType = 'image';
	var _fbContext = 'imagepage';

	window.fbFbOpen = function (type, context) {
		_fbType = type || 'image';
		_fbContext = context || 'imagepage';
		_fbFolder = '';
		_fbFiles = [];
		document.getElementById('fb-filebrowser-bg').style.display = 'flex';
		document.querySelectorAll('.fb-fb-tab').forEach(function (b, i) { b.classList.toggle('active', i === 0); });
		document.getElementById('fb-fb-browse').style.display = '';
		document.getElementById('fb-fb-upload').style.display = 'none';
		var s = document.getElementById('fb-fb-search');
		if (s) s.value = '';
		fbFbLoadFiles();
	};

	window.fbFbClose = function () {
		document.getElementById('fb-filebrowser-bg').style.display = 'none';
	};

	window.fbFbTab = function (tab, btn) {
		document.querySelectorAll('.fb-fb-tab').forEach(function (b) { b.classList.remove('active'); });
		btn.classList.add('active');
		document.getElementById('fb-fb-browse').style.display = tab === 'browse' ? '' : 'none';
		document.getElementById('fb-fb-upload').style.display = tab === 'upload' ? '' : 'none';
	};

	function fbFbLoadFiles() {
		var grid = document.getElementById('fb-fb-grid');
		grid.innerHTML = '<div class="fb-muted">' + FlipBookData.i18n.loading + '</div>';
		fetch(FlipBookData.ajaxUrl + '&fb_action=list_uploads&type=' + encodeURIComponent(_fbType))
			.then(function (r) { return r.json(); })
			.then(function (res) {
				_fbFiles = (res.ok && res.files) || [];
				fbFbRenderGrid();
			})
			.catch(function () {
				grid.innerHTML = '<div class="fb-err">' + FlipBookData.i18n.saveError + '</div>';
			});
	}

	window.fbFbRenderGrid = function () {
		var grid = document.getElementById('fb-fb-grid');
		var crumb = document.getElementById('fb-fb-crumb');
		var filterEl = document.getElementById('fb-fb-search');
		var filter = filterEl ? filterEl.value.toLowerCase().trim() : '';

		var files, folders;
		if (filter) {
			files = _fbFiles.filter(function (f) { return f.filename.toLowerCase().indexOf(filter) !== -1; });
			folders = [];
		} else {
			var seen = {};
			folders = [];
			files = [];
			_fbFiles.forEach(function (f) {
				if (_fbFolder && f.filename.indexOf(_fbFolder + '/') !== 0) return;
				var rel = _fbFolder ? f.filename.slice(_fbFolder.length + 1) : f.filename;
				var parts = rel.split('/');
				if (parts.length > 1) {
					var sub = (_fbFolder ? _fbFolder + '/' : '') + parts[0];
					if (!seen[sub]) { seen[sub] = true; folders.push(parts[0]); }
				} else {
					files.push(f);
				}
			});
		}

		if (crumb) {
			var html = '<span class="fb-fb-crumb-item' + (_fbFolder === '' ? ' active' : '') + '" onclick="fbFbNav(\'\')">uploads</span>';
			if (_fbFolder) {
				var parts = _fbFolder.split('/');
				var built = '';
				parts.forEach(function (p, i) {
					built = built ? built + '/' + p : p;
					var snap = built;
					html += ' / <span class="fb-fb-crumb-item' + (i === parts.length - 1 ? ' active' : '') + '" onclick="fbFbNav(\'' + snap + '\')">' + esc(p) + '</span>';
				});
			}
			crumb.innerHTML = html;
		}

		if (!folders.length && !files.length) {
			grid.innerHTML = '<p class="fb-muted">' + FlipBookData.i18n.noFiles + '</p>';
			return;
		}

		var out = '';
		folders.sort().forEach(function (f) {
			var fullPath = (_fbFolder ? _fbFolder + '/' : '') + f;
			out += '<div class="fb-fb-folder" onclick="fbFbNav(\'' + esc(fullPath) + '\')">' +
				'<svg width="28" height="28" viewBox="0 0 24 24" fill="#f59e0b"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>' +
				'<span class="fb-fb-name">' + esc(f) + '</span></div>';
		});
		files.forEach(function (f) {
			var thumb = f.thumb || f.url;
			var icon = _fbType === 'pdf'
				? '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'
				: '<img src="' + esc(thumb) + '" alt="" loading="lazy">';
			out += '<div class="fb-fb-file" onclick="fbFbSelect(\'' + esc(f.filename) + '\',\'' + esc(f.url) + '\',\'' + esc(thumb) + '\')" title="' + esc(f.filename) + '">' +
				icon + '<span class="fb-fb-name">' + esc(f.basename) + '</span></div>';
		});
		grid.innerHTML = out;
	};

	window.fbFbNav = function (folder) {
		_fbFolder = folder;
		var s = document.getElementById('fb-fb-search');
		if (s) s.value = '';
		fbFbRenderGrid();
	};

	window.fbFbSelect = function (filename, url, thumb) {
		fbFbClose();
		if (_fbContext === 'imagepage') {
			_fbPages.push({ type: 'image', file: filename });
			renderPagesList();
		} else if (_fbContext === 'textpage') {
			var preview = document.getElementById('fb-textpage-preview');
			preview.src = thumb || url;
			preview.style.display = '';
			preview.dataset.file = filename;
		} else if (_fbContext === 'pdfpage') {
			fbOpenPdfPicker(filename, url);
		}
	};

	window.fbFbUpload = function (files) {
		var file = files && files[0];
		if (!file) return;
		var prog = document.getElementById('fb-fb-upload-progress');
		prog.style.display = '';
		prog.textContent = FlipBookData.i18n.uploading;

		var body = new FormData();
		body.append('type', _fbType);
		body.append('nonce', FlipBookData.nonce);
		body.append('file', file);

		fetch(FlipBookData.ajaxUrl + '&fb_action=upload_file', { method: 'POST', body: body })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				prog.style.display = 'none';
				var input = document.getElementById('fb-fb-file-input');
				if (input) input.value = '';
				if (res.ok) {
					fbFbSelect(res.filename, res.url, res.thumb);
				} else {
					console.error('FlipBook: upload_file failed', res);
					window.alert(res.msg || FlipBookData.i18n.saveError);
				}
			})
			.catch(function (err) {
				console.error('FlipBook: upload_file request failed', err);
				prog.style.display = 'none';
				window.alert(FlipBookData.i18n.saveError);
			});
	};

	// ─────────────────────────────────────────────────────────────────────────
	// PDF PAGE PICKER — renders every page of a chosen PDF as a thumbnail via
	// PDF.js, lets the user multi-select pages, inserts one "pdf" page entry
	// per selected page in the order they were clicked.
	// ─────────────────────────────────────────────────────────────────────────

	var _fbPdfFile = '';
	var _fbPdfSelected = []; // page numbers, in click order
	var _fbPdfLoadToken = 0; // guards against a stale load finishing after the modal was reopened
	var _fbPdfWorkerConfigured = false;

	window.fbOpenPdfPicker = function (filename, url) {
		if (typeof pdfjsLib === 'undefined') {
			console.error('FlipBook: window.pdfjsLib is undefined - the pdf.min.mjs module script failed to load. Check that plugins/FlipBook/js/pdf.min.mjs exists and is served as JavaScript (not an HTML 404 page).');
			window.alert(FlipBookData.i18n.pdfjsNotLoaded);
			return;
		}
		// The PDF.js module shim (a <script type="module">) runs deferred - i.e.
		// after this classic script has already finished executing once - so the
		// worker path can only be configured here, on first actual use, not at
		// the top of this file.
		if (!_fbPdfWorkerConfigured && FlipBookData.pdfWorkerUrl) {
			pdfjsLib.GlobalWorkerOptions.workerSrc = FlipBookData.pdfWorkerUrl;
			_fbPdfWorkerConfigured = true;
		}

		_fbPdfFile = filename;
		_fbPdfSelected = [];
		var token = ++_fbPdfLoadToken;

		var status = document.getElementById('fb-pdfpicker-status');
		var grid = document.getElementById('fb-pdfpicker-grid');
		grid.innerHTML = '';
		status.textContent = FlipBookData.i18n.loadingPdf;
		document.getElementById('fb-pdfpicker-bg').style.display = 'flex';

		pdfjsLib.getDocument({ url: url }).promise.then(function (pdf) {
			if (token !== _fbPdfLoadToken) return; // modal was reopened with a different file meanwhile

			var numPages = pdf.numPages;
			var i = 0;

			function renderNext() {
				if (token !== _fbPdfLoadToken) return;
				if (i >= numPages) { status.textContent = ''; return; }
				i++;
				status.textContent = FlipBookData.i18n.renderingPage + ' ' + i + ' / ' + numPages;

				pdf.getPage(i).then(function (page) {
					var viewport = page.getViewport({ scale: 1 });
					var scale = 130 / viewport.width;
					var scaledViewport = page.getViewport({ scale: scale });

					var canvas = document.createElement('canvas');
					canvas.width = scaledViewport.width;
					canvas.height = scaledViewport.height;

					return page.render({ canvasContext: canvas.getContext('2d'), viewport: scaledViewport }).promise.then(function () {
						if (token !== _fbPdfLoadToken) return;

						var pageNum = page.pageNumber;
						var cell = document.createElement('div');
						cell.className = 'fb-pdfpicker-page';
						cell.dataset.page = pageNum;
						cell.appendChild(canvas);
						var label = document.createElement('span');
						label.className = 'fb-pdfpicker-num';
						label.textContent = pageNum;
						cell.appendChild(label);
						var badge = document.createElement('span');
						badge.className = 'fb-pdfpicker-badge';
						cell.appendChild(badge);

						cell.addEventListener('click', function () { fbTogglePdfPage(pageNum, cell); });
						grid.appendChild(cell);

						renderNext();
					});
				}).catch(function (pageErr) {
					console.error('FlipBook: PDF page render failed', pageErr);
					renderNext(); // skip a page that failed to render rather than aborting the whole picker
				});
			}

			renderNext();
		}).catch(function (err) {
			if (token !== _fbPdfLoadToken) return;
			console.error('FlipBook: PDF load failed', err);
			var detail = err && (err.message || err.name);
			status.textContent = FlipBookData.i18n.pdfLoadError + (detail ? ' (' + detail + ')' : '');
		});
	};

	function fbTogglePdfPage(pageNum, cell) {
		var idx = _fbPdfSelected.indexOf(pageNum);
		if (idx === -1) {
			_fbPdfSelected.push(pageNum);
		} else {
			_fbPdfSelected.splice(idx, 1);
		}
		// Renumber every badge so the click order is always visible, not just selected/unselected
		var grid = document.getElementById('fb-pdfpicker-grid');
		grid.querySelectorAll('.fb-pdfpicker-page').forEach(function (el) {
			var p = parseInt(el.dataset.page, 10);
			var order = _fbPdfSelected.indexOf(p);
			el.classList.toggle('selected', order !== -1);
			el.querySelector('.fb-pdfpicker-badge').textContent = order !== -1 ? (order + 1) : '';
		});
	}

	window.fbClosePdfPicker = function () {
		_fbPdfLoadToken++; // invalidate any render loop still in flight
		document.getElementById('fb-pdfpicker-bg').style.display = 'none';
	};

	window.fbInsertPdfPages = function () {
		if (!_fbPdfSelected.length) { window.alert(FlipBookData.i18n.noPagesSelected); return; }
		_fbPdfSelected.forEach(function (pageNum) {
			_fbPages.push({ type: 'pdf', file: _fbPdfFile, page: pageNum });
		});
		renderPagesList();
		fbClosePdfPicker();
	};

	ready(function () {
		initList();
		initEdit();
	});
})();
