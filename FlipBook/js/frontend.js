(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') fn();
		else document.addEventListener('DOMContentLoaded', fn);
	}

	// ── PDF loading (mirrors the admin-side picker's approach) ────────────────

	var _pdfWorkerConfigured = false;
	var _pdfDocCache = {};
	var _pdfHighResCache = {};

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

	// Re-renders a PDF page at a much higher resolution than the inline book
	// view, for the zoom overlay. Cached per file+page so repeated zoom clicks
	// on the same page don't re-render every time.
	function getHighResPdfImage(uploadUrl, page) {
		var key = page.file + '#' + page.page;
		if (_pdfHighResCache[key]) return _pdfHighResCache[key];

		var url = uploadUrlFor(uploadUrl, page.file);
		_pdfHighResCache[key] = loadPdfDoc(url)
			.then(function (pdf) { return pdf.getPage(page.page); })
			.then(function (pdfPage) {
				var viewport = pdfPage.getViewport({ scale: 4 });
				var canvas = document.createElement('canvas');
				canvas.width = viewport.width;
				canvas.height = viewport.height;
				return pdfPage.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise
					.then(function () { return canvas.toDataURL('image/jpeg', 0.92); });
			});
		return _pdfHighResCache[key];
	}

	// ── Zoom (GLightbox) ────────────────────────────────────────────────────────

	// Adds a hover-revealed magnifying-glass button to a page. getSrc is called
	// lazily on click and must return a Promise<string> resolving to the image
	// URL/data-URL to zoom into. stopPropagation on every pointer event that
	// could otherwise be picked up as the start of a StPageFlip page-drag, so
	// clicking the zoom icon never accidentally flips the page underneath it.
	function addZoomButton(wrap, getSrc) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'flipbook-zoom-btn';
		btn.setAttribute('aria-label', 'Zoom this page');
		btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>';

		['mousedown', 'touchstart', 'pointerdown'].forEach(function (evt) {
			btn.addEventListener(evt, function (e) { e.stopPropagation(); });
		});

		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			e.preventDefault();
			if (typeof GLightbox === 'undefined') {
				console.error('FlipBook: GLightbox did not load - check that glightbox.min.js/.css are present.');
				return;
			}
			getSrc().then(function (src) {
				GLightbox({ elements: [{ href: src, type: 'image', zoomable: true, draggable: true }] }).open();
			}).catch(function (err) {
				console.error('FlipBook: could not prepare zoom image', err);
			});
		});

		wrap.appendChild(btn);
	}

	// ── Building one page element per book page ────────────────────────────────

	function buildImagePage(uploadUrl, file) {
		var wrap = document.createElement('div');
		wrap.className = 'flipbook-page';
		var src = uploadUrlFor(uploadUrl, file);
		var img = document.createElement('img');
		img.src = src;
		img.alt = '';
		wrap.appendChild(img);
		addZoomButton(wrap, function () { return Promise.resolve(src); });
		return Promise.resolve(wrap);
	}

	function buildImageTextPage(uploadUrl, page) {
		var wrap = document.createElement('div');
		wrap.className = 'flipbook-page flipbook-page-text flipbook-text-' + (page.text_position === 'above' ? 'above' : 'below');
		var src = uploadUrlFor(uploadUrl, page.file);
		var img = document.createElement('img');
		img.src = src;
		img.alt = '';
		wrap.appendChild(img);
		var textEl = document.createElement('div');
		textEl.className = 'flipbook-page-text-content';
		textEl.innerHTML = page.text || '';
		wrap.appendChild(textEl);
		addZoomButton(wrap, function () { return Promise.resolve(src); });
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
				// The zoom overlay re-renders separately at a much higher scale -
				// this one only needs to look good at inline book size.
				var viewport = pdfPage.getViewport({ scale: 2 });
				var canvas = document.createElement('canvas');
				canvas.width = viewport.width;
				canvas.height = viewport.height;
				return pdfPage.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise.then(function () {
					wrap.appendChild(canvas);
					addZoomButton(wrap, function () { return getHighResPdfImage(uploadUrl, page); });
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

		// StPageFlip is handed its own inner element (bookEl), not the outer
		// container. Reason: the library sets `min-height` on whatever element
		// it's given, derived from its own internal default bounds (100px)
		// unless min/max are explicitly configured - and since its page
		// elements are all position:absolute, that min-height is the ONLY
		// thing establishing the element's height for layout purposes. Left as
		// the container itself, anything appended after it (our toolbar, or
		// any of the site's own content) would sit underneath the book's real
		// visual size rather than below it. Giving the library its own
		// element, then setting that element's height explicitly ourselves,
		// keeps bookEl's reserved space correct while letting the toolbar
		// live as a normal sibling that properly pushes later content down.
		var bookEl = document.createElement('div');
		bookEl.className = 'flipbook-book';
		container.appendChild(bookEl);

		var stage = document.createElement('div');
		stage.className = 'flipbook-stage';

		Promise.all((data.pages || []).map(function (page) { return buildPage(data.uploadUrl, page); }))
			.then(function (pageEls) {
				pageEls.forEach(function (el) { stage.appendChild(el); });
				bookEl.appendChild(stage);

				var bookHeight = settings.height || 800;
				var pageFlip = new St.PageFlip(bookEl, {
					width: settings.width || 600,
					height: bookHeight,
					size: settings.size === 'fixed' ? 'fixed' : 'stretch',
					showCover: !!settings.show_cover,
					usePortrait: !!settings.single_page_mobile
				});
				// Backstop the library's own min-height default (100px) with the
				// book's actual configured height, so bookEl reserves roughly the
				// right amount of space even before any finer per-viewport
				// adjustment the library makes internally.
				bookEl.style.minHeight = bookHeight + 'px';

				var pageNodes = stage.querySelectorAll('.flipbook-page');
				// The library's own docs are inconsistent about the casing of this
				// method name - check for whichever actually exists rather than
				// assuming one.
				if (typeof pageFlip.loadFromHTML === 'function') pageFlip.loadFromHTML(pageNodes);
				else if (typeof pageFlip.loadFromHtml === 'function') pageFlip.loadFromHtml(pageNodes);
				else console.error('FlipBook: StPageFlip instance has neither loadFromHTML nor loadFromHtml.');

				container.classList.remove('flipbook-loading');

				if (settings.show_nav_buttons || settings.show_page_numbers) {
					buildToolbar(container, pageFlip, settings);
				}

				// Free addition alongside the nav buttons: left/right arrow keys flip
				// pages once the book has focus. Scoped per-container (not document-wide)
				// so multiple books on one page don't fight over which one responds.
				container.setAttribute('tabindex', '0');
				container.addEventListener('keydown', function (e) {
					if (e.key === 'ArrowRight') { pageFlip.flipNext('top'); e.preventDefault(); }
					else if (e.key === 'ArrowLeft') { pageFlip.flipPrev('top'); e.preventDefault(); }
				});

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

	// One combined row: prev button, page indicator, fullscreen toggle, next
	// button - only the pieces the settings actually asked for are included.
	// Lives as a normal (non-absolutely-positioned) sibling after the book, so
	// it reserves its own space in the page's normal flow instead of floating
	// over whatever content comes next.
	function buildToolbar(container, pageFlip, settings) {
		var toolbar = document.createElement('div');
		toolbar.className = 'flipbook-toolbar';

		if (settings.show_nav_buttons) {
			var prev = document.createElement('button');
			prev.type = 'button';
			prev.className = 'flipbook-nav flipbook-nav-prev';
			prev.setAttribute('aria-label', 'Previous page');
			prev.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>';
			prev.addEventListener('click', function () { pageFlip.flipPrev('top'); });
			toolbar.appendChild(prev);
		}

		if (settings.show_page_numbers) {
			var indicator = document.createElement('div');
			indicator.className = 'flipbook-page-indicator';
			toolbar.appendChild(indicator);

			var update = function () {
				indicator.textContent = (pageFlip.getCurrentPageIndex() + 1) + ' / ' + pageFlip.getPageCount();
			};
			update();
			if (typeof pageFlip.on === 'function') pageFlip.on('flip', update);
		}

		if (document.fullscreenEnabled) {
			toolbar.appendChild(buildFullscreenButton(container, pageFlip));
		}

		if (settings.show_nav_buttons) {
			var next = document.createElement('button');
			next.type = 'button';
			next.className = 'flipbook-nav flipbook-nav-next';
			next.setAttribute('aria-label', 'Next page');
			next.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>';
			next.addEventListener('click', function () { pageFlip.flipNext('top'); });
			toolbar.appendChild(next);
		}

		container.appendChild(toolbar);
	}

	// Toggles the outer container into the browser's Fullscreen API.
	// NOTE: whether StPageFlip reflows cleanly on a fullscreen-driven size
	// change (as opposed to a window resize) hasn't been confirmed - the
	// update() call below is a best-effort nudge, worth specifically checking
	// in real testing.
	function buildFullscreenButton(container, pageFlip) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'flipbook-fullscreen-btn';
		btn.setAttribute('aria-label', 'Toggle fullscreen');
		var expandIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>';
		var compressIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>';
		btn.innerHTML = expandIcon;

		btn.addEventListener('click', function () {
			if (document.fullscreenElement === container) {
				document.exitFullscreen();
			} else {
				container.requestFullscreen();
			}
		});

		document.addEventListener('fullscreenchange', function () {
			var isFull = document.fullscreenElement === container;
			container.classList.toggle('flipbook-fullscreen', isFull);
			btn.innerHTML = isFull ? compressIcon : expandIcon;
			if (typeof pageFlip.update === 'function') pageFlip.update();
		});

		return btn;
	}

	ready(function () {
		document.querySelectorAll('.flipbook-embed[data-flipbook]').forEach(initBook);
	});
})();
