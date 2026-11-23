# Flip Book

A flip-book builder plugin for [GetSimpleCMS-CE](https://github.com/GetSimpleCMS-CE). Build page-turning books from PDF pages, images, and image + text pages, reorder them by drag and drop, and embed them anywhere with a shortcode.

Powered by [StPageFlip](https://github.com/Nodlik/StPageFlip) and [PDF.js](https://mozilla.github.io/pdf.js/).

## Features

- **Mixed page types** in a single book:
  - **Image**: any JPG, PNG, GIF or WebP from your uploads.
  - **Image + Text**: an image with a rich-text block above or below it, edited with the Autumn-Note WYSIWYG editor.
  - **PDF**: pick individual pages (or all of them) from a PDF and insert them as book pages.
- **Drag-and-drop reordering** of pages in the editor.
- **Built-in file browser** for choosing existing uploads, plus upload-new directly from the editor. Image uploads get a standard GetSimple thumbnail.
- **Zoom**: a hover-revealed magnifier on each page opens the page in a lightbox (GLightbox). PDF pages are re-rendered at a higher resolution for the zoom view.
- **Page-flip sound effect** (optional).
- **Navigation**: previous/next buttons, page indicator, fullscreen toggle, and ← / → keyboard support once the book has focus.
- **Responsive**: optional single-page view on mobile.
- **Multiple books per page** are supported.
- **Translations**: English (`en_US`) and Spanish (`es_ES`) included.
- No jQuery dependency.

## Requirements

- GetSimpleCMS-CE
- PHP 7.4 or newer
- A modern browser (the front end loads PDF.js as an ES module)

## Installation

1. Copy `FlipBook.php` and the `FlipBook/` folder into your site's `plugins/` directory:

   ```
   plugins/
   ├── FlipBook.php
   └── FlipBook/
       ├── admin/
       ├── audio/
       ├── css/
       ├── js/
       └── lang/
   ```

2. In the GetSimple admin, go to **Plugins** and enable **Flip Book**.
3. A **Flip Book** entry appears in the **Pages** sidebar.

Books are stored as one JSON file each in `data/other/flipbook/` (protected with an `.htaccess`). Images and PDFs are stored in the standard `data/uploads/` folder, so anything already in your uploads is available to choose from.

## Usage

### Creating a book

1. Open **Pages → Flip Book** and click **New Flip Book**.
2. Give the book a name. The slug is generated from the name automatically.
3. Adjust the settings (see below).
4. Add pages with the **Image**, **Image + Text** or **PDF** buttons.
   - For PDFs, choose a file, then tick the pages you want and click **Insert selected pages**.
5. Drag pages to reorder them, then click **Save**.

### Settings

| Setting | Default | Description |
|---|---|---|
| Width (px) | 600 | Width of a single page. |
| Height (px) | 800 | Height of a single page. |
| Size mode | Stretch to fit | `Fixed` keeps the dimensions as set; `Stretch to fit` scales the book to its container. |
| Show a hard cover page | On | Treats the first and last pages as hard covers. |
| Page-flip sound effect | On | Plays a sound on each flip. |
| Single page view on mobile | On | Shows one page at a time on narrow screens. |
| Show page numbers | On | Shows the current page indicator. |
| Show previous/next buttons | On | Shows the navigation buttons. |

### Embedding a book

In any page's content, use a shortcode with the book's ID or slug:

```
[flipbook id="1"]
[flipbook slug="your-slug"]
```

Or call it from a theme template:

```php
<?php get_FlipBook('your-slug'); ?>
```

`get_FlipBook()` accepts either a numeric ID or a slug.

The plugin's CSS and JavaScript are only loaded on pages that actually contain a book, via the `theme-footer` hook. **Your theme must call `get_footer()`** (or otherwise fire `theme-footer`) for books to work.

## Limits

- Image uploads: JPG, PNG, GIF, WebP, up to **5 MB**.
- PDF uploads: up to **25 MB**.
- Your server's `post_max_size` and `upload_max_filesize` must be at least as large as the files you want to upload. If they aren't, the editor shows a message asking you to raise them.

## Notes

- If you rename a book, its slug changes with it. Shortcodes that use `id` are unaffected; shortcodes that use `slug` will need updating, so prefer `id` for long-lived embeds.
- Image + Text content is stored as the HTML the editor produces. Only trusted admin users can create it.
- PDF pages are rendered in the visitor's browser, so very large PDFs take longer to load than images do. For heavy documents, consider exporting pages to optimized images.

## Folder structure

```
FlipBook.php            Plugin bootstrap, storage, AJAX, shortcode, rendering
FlipBook/
├── admin/
│   ├── list.php        Book list + embed help
│   └── edit.php        Book editor
├── audio/flip.mp3      Page-flip sound
├── css/                admin.css, frontend.css, autumnnote.css, glightbox.min.css
├── js/                 admin.js, frontend.js and bundled libraries
└── lang/               en_US.php, es_ES.php
```

## Bundled libraries

| Library | Purpose |
|---|---|
| [StPageFlip](https://github.com/Nodlik/StPageFlip) 2.0.7 | Page-turning effect |
| [PDF.js](https://mozilla.github.io/pdf.js/) | Rendering PDF pages |
| [GLightbox](https://github.com/biati-digital/glightbox) | Zoom overlay |
| [SortableJS](https://github.com/SortableJS/Sortable) 1.15.2 | Drag-and-drop ordering |
| Autumn-Note | Rich-text editing for Image + Text pages |

Each library is distributed under its own license.

## Author

[risingisland](https://ko-fi.com/ericmontgomery)
