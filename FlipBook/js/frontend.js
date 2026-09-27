(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') fn();
		else document.addEventListener('DOMContentLoaded', fn);
	}

	// ── PDF loading (mirrors the admin-side picker's approach) ────────────────

	var _pdfWorkerConfigured = false;
	var _pdfDocCache = {};

	function loadPdfDoc(url) {
		if (_pdfDocCache[url]) return _pdfDocCache[url];
		if (typeof pdfjsLib === 'undefined') {
			return Promise.reject(new Error('pdfjsLib is not loaded - check that page-flip.browser.js and pdf.min.mjs are present in the plugin js/ folder.'));
		}
		// The PDF.js module shim runs deferred (after this classic script has
		// already executed once), so the worker path can only be configured
		// here, on first actual use - same reasoning as the admin side.
		if (!_pdfWorkerConfigured && window.FlipBookFrontend && window.FlipBookFrontend.pdfWorkerUrl) {
			pdfjsLib.GlobalWorkerOptions.workerSrc = window.FlipBookFrontend.pdfWorkerUrl;
			_pdfWorkerConfigured = true;
		}
		_pdfDocCache[url] = pdfjsLib.getDocument({ url: url }).promise;
		return _pdfDocCache[url];
	}

	function uploadUrlFor(uploadUrl, file) {
		return uploadUrl + file.split('/').map(encodeURIComponent).join('/');
	}

	// ── Building one page element per book page ────────────────────────────────

	function buildImagePage(uploadUrl, file) {
		var wrap = document.createElement('div');
		wrap.className = 'flipbook-page';
		var img = document.createElement('img');
		img.src = uploadUrlFor(uploadUrl, file);
		img.alt = '';
		wrap.appendChild(img);
		return Promise.resolve(wrap);
	}

	function buildImageTextPage(uploadUrl, page) {
		var wrap = document.createElement('div');
		wrap.className = 'flipbook-page flipbook-page-text flipbook-text-' + (page.text_position === 'above' ? 'above' : 'below');
		var img = document.createElement('img');
		img.src = uploadUrlFor(uploadUrl, page.file);
		img.alt = '';
		wrap.appendChild(img);
		var textEl = document.createElement('div');
		textEl.className = 'flipbook-page-text-content';
		textEl.innerHTML = page.text || '';
		wrap.appendChild(textEl);
		return Promise.resolve(wrap);
	}

	function buildPdfPage(uploadUrl, page) {
		var wrap = document.createElement('div');
		wrap.className = 'flipbook-page flipbook-page-pdf';
		var url = uploadUrlFor(uploadUrl, page.file);

		return loadPdfDoc(url)
			.then(function (pdf) { return pdf.getPage(page.page); })
			.then(function (pdfPage) {
				// Render at 2x the display scale for crisper output on high-DPI
				// screens, since the canvas is then scaled down by CSS to fit the page.
				var viewport = pdfPage.getViewport({ scale: 2 });
				var canvas = document.createElement('canvas');
				canvas.width = viewport.width;
				canvas.height = viewport.height;
				return pdfPage.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise.then(function () {
					wrap.appendChild(canvas);
					return wrap;
				});
			})
			.catch(function (err) {
				console.error('FlipBook: failed to render PDF page', page, err);
				return wrap; // leave this one page blank rather than aborting the whole book
			});
	}

	function buildPage(uploadUrl, page) {
		if (page.type === 'image') return buildImagePage(uploadUrl, page.file);
		if (page.type === 'image_text') return buildImageTextPage(uploadUrl, page);
		if (page.type === 'pdf') return buildPdfPage(uploadUrl, page);
		return Promise.resolve(document.createElement('div'));
	}

	// ── Wiring one container up to a StPageFlip instance ────────────────────────

	function initBook(container) {
		var raw = container.getAttribute('data-flipbook');
		if (!raw) return;

		var data;
		try { data = JSON.parse(raw); } catch (e) { console.error('FlipBook: bad data-flipbook JSON', e); return; }

		if (typeof St === 'undefined' || !St.PageFlip) {
			console.error('FlipBook: StPageFlip did not load - check that js/page-flip.browser.js is present.');
			return;
		}

		var settings = data.settings || {};
		container.classList.add('flipbook-loading');

		// Pages are built off-DOM first (async, since PDF pages need to be
		// rendered) and only handed to StPageFlip once every page is ready,
		// rather than trying to update pages in place after the fact.
		var stage = document.createElement('div');
		stage.className = 'flipbook-stage';

		Promise.all((data.pages || []).map(function (page) { return buildPage(data.uploadUrl, page); }))
			.then(function (pageEls) {
				pageEls.forEach(function (el) { stage.appendChild(el); });
				container.appendChild(stage);

				var pageFlip = new St.PageFlip(container, {
					width: settings.width || 600,
					height: settings.height || 800,
					size: settings.size === 'fixed' ? 'fixed' : 'stretch',
					showCover: !!settings.show_cover,
					usePortrait: !!settings.single_page_mobile
				});

				var pageNodes = stage.querySelectorAll('.flipbook-page');
				// The library's own docs are inconsistent about the casing of this
				// method name - check for whichever actually exists rather than
				// assuming one.
				if (typeof pageFlip.loadFromHTML === 'function') pageFlip.loadFromHTML(pageNodes);
				else if (typeof pageFlip.loadFromHtml === 'function') pageFlip.loadFromHtml(pageNodes);
				else console.error('FlipBook: StPageFlip instance has neither loadFromHTML nor loadFromHtml.');

				container.classList.remove('flipbook-loading');

				if (settings.sound && data.soundUrl && typeof pageFlip.on === 'function') {
					var audio = new Audio(data.soundUrl);
					pageFlip.on('flip', function () {
						audio.currentTime = 0;
						audio.play().catch(function () {}); // ignore autoplay-policy rejections
					});
				}
			})
			.catch(function (err) {
				console.error('FlipBook: failed to build book', err);
				container.classList.remove('flipbook-loading');
			});
	}

	ready(function () {
		document.querySelectorAll('.flipbook-embed[data-flipbook]').forEach(initBook);
	});
})();
