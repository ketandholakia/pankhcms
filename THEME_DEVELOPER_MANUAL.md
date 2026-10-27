# Theme Developer Manual

This manual explains how themes work in PankhCMS and provides guidelines for building or extending frontend blocks safely and efficiently.

---

## 1. Quick Start (5-Minute Checklist)

To quickly ship a new frontend block in the active theme (e.g., `pankhcmsstarter`):

1. **Verify Active Theme**: Ensure the `active_theme` setting (Admin > Themes) or `ACTIVE_THEME=pankhcmsstarter` in your `.env` file.
2. **Create Block Template**: Add a new block template at `themes/pankhcmsstarter/views/blocks/{type}.blade.php`.
3. **Register Block in Admin**: Open `views/admin/pages/create.blade.php` and `views/admin/pages/edit.blade.php`. Add a block button with `addBlock('{type}')`.
4. **Test**: Save a page containing that block in the admin panel and verify its rendering on the frontend.
5. **Clear Cache**: If changes aren't visible, clear the compiled Blade cache:
   ```bash
   find storage/cache -type f -name '*.php' -delete
   ```

---

## 2. Theme Directory Structure

The structure of a typical theme (e.g., `pankhcmsstarter`):

- **`theme.json`**: Metadata (name, slug, version, author, `options` — see §6 Theme Options) and `supports` flags.
- **`views/layouts/main.blade.php`**: The global layout shell (head, header/footer partials, view beacon).
- **`views/site/*.blade.php`**: Public page templates — `page`, `post` (uses the `$page` contract), `blog` (paginated post cards), `archive` (category/tag), `search`, `contact`, `404` (themed not-found), `feed` (RSS XML).
- **`views/blocks/*.blade.php`**: Global block templates rendered by `blocks_html()` into layout locations (header_top, footer, sidebar, ...). Receives `$data`.
- **`views/page-blocks/*.blade.php`**: Page-builder block templates rendered by `render_page_blocks()` from a page's `content_json`. Receives `$block` (array). Shipped: `text`, `image`, `cta`, `faq`, `columns`, `video_embed`, `testimonials`, `product_gallery`, `hero`.
- **`assets/css/style.css`**: Self-hosted stylesheet (framework subset + custom chrome, ~15KB). No CDN calls.
- **`assets/js/app.js`**: Self-hosted JS — burger menu, keyboard/touch dropdowns, scroll-snap slider (prev/next/dots/pause/autoplay + `prefers-reduced-motion`), dark-mode toggle (localStorage).
- **`assets/`**: Theme CSS/JS/images (published into `public/themes/<slug>/` by `Theme::publishAssets()`).

### Data shapes: `blocks/` vs `page-blocks/`

**Global blocks (`views/blocks/`, rendered via `blocks_html($location)`)** receive a `$data` array with TWO possible shapes — handle both:

1. **String content** (legacy): `$data = ['text' => string (sanitized), 'title' => string, 'show_title' => bool, 'block' => $block]`.
2. **Array content** (JSON): `$data = ['title' => ..., 'text'/'html' => string (sanitized), ...custom keys..., 'show_title' => bool]`. The admin controller stores JSON strings; `blocks_html()` unwraps (possibly double-encoded) JSON and sanitizes `text`/`html` before your template runs.

Rules: never read `$block->content` in templates; treat `text`/`html` as sanitized HTML (`{!! !!}`) and escape every other key (`{{ }}`).

**Page-builder blocks (`views/page-blocks/`, rendered via `render_page_blocks($blocks)`)** receive `$block` as an ARRAY from `content_json`: `['type' => 'faq', 'lines' => "question | answer\n...", ...]`. Rules: escape everything with `{{ }}`; use `sanitize_html()` for rich-text fields the admin may type HTML into; `html`/`text` keys are pre-sanitized on save by `HtmlSanitizer::sanitizeBlocksJson()`; `video_embed.url` is validated server-side against the YouTube/Vimeo allowlist (`PageController::validateBlocks()`).

---

## 3. Dynamic Rendering Flow

1. **Global Shell**: `layouts/main.blade.php` loads the HTML structure and `@yield('content')`; theme options are emitted as CSS variables in `<head>`.
2. **Page resolution**: `SiteController::renderPage()` picks the first existing view from `templates.<layout>` -> `site.<type>` -> `templates.page` -> `site.page`. Posts render through `site.post` (the `$page` contract), the blog index through `site.blog`.
3. **Page-builder blocks**: `render_page_blocks()` renders each `content_json` entry via `page-blocks.{type}`. Example: `{"type":"video_embed","url":"https://youtu.be/..."}` loads `page-blocks/video_embed.blade.php`.
4. **Global blocks**: `blocks_html($location)` renders `blocks.{type}` for every block placed in that location.
5. **Errors**: unknown pages/terms render the theme's `site.404` (search box + recent pages) with a real 404 status.

---

## 4. Block Naming Conventions

Follow these rules for naming new blocks to ensure compatibility and predictability:

| Rule | Good Example | Bad Example | Reason |
|---|---|---|---|
| Lowercase only | `product_gallery` | `ProductGallery` | Prevents filesystem case-sensitivity issues. |
| snake_case | `hero_basic` | `hero-basic` | Keeps file and type naming predictable. |
| Type matches Filename | `type: faq` & `faq.blade.php` | Type `FaqBlock` & `faq.blade.php` | `@includeIf` relies on an exact string match. |
| Short & Specific | `cta_box`, `team_grid` | `my_custom_super_block_v2` | Easier to read in code and admin UI. |
| Reserve `__` prefix | `__custom_fields` (System only) | `__hero_banner` | Avoids collision with internal metadata blocks. |

---

## 5. How to Create a New Frontend Block

Here is a step-by-step example of creating an `faq` block.

### Step A: Frontend Template
Create `themes/pankhcmsstarter/views/blocks/faq.blade.php`:

```blade
@php
    $title = $block['title'] ?? 'FAQ';
    $items = is_array($block['items'] ?? null) ? $block['items'] : [];
@endphp

<section class="py-4">
    <div class="container">
        <h2>{{ $title }}</h2>
        @if(count($items))
            <div class="accordion" id="faq-accordion">
                @foreach($items as $i => $item)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faq-heading-{{ $i }}">
                            <button class="accordion-button {{ $i ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-{{ $i }}">
                                {{ $item['q'] ?? 'Question' }}
                            </button>
                        </h2>
                        <div id="faq-collapse-{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faq-accordion">
                            <div class="accordion-body">{{ $item['a'] ?? '' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
```

### Step B: Admin Builder UI
In both `views/admin/pages/create.blade.php` and `views/admin/pages/edit.blade.php`:

1. **Add Button**:
   ```html
   <button type="button" onclick="addBlock('faq')" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
     + FAQ
   </button>
   ```

2. **Add Default Data in `addBlock(type)`**:
   ```javascript
   if (type === 'faq') {
     block.lines = ''; // one "question | answer" per line, parsed in the view
   }
   ```

3. **Add Editor UI in `render()`**:
   ```javascript
   if (b.type === 'faq') {
     innerHTML += `<p class="text-sm text-gray-600">One entry per line: <strong>question | answer</strong>.</p>
       <textarea class="w-full border p-2 rounded font-mono text-sm" rows="5" oninput="updateBlock(${i}, 'lines', this.value)">${b.lines || ''}</textarea>`;
   }
   ```

4. **Add Server-side Validation** (for data the sanitizer cannot judge, e.g. URLs):
   `PageController::validateBlocks()` runs after `sanitizeBlocksJson()` — `video_embed` there enforces the YouTube/Vimeo host allowlist.

---

## 6. Theme Options

Declare options in `theme.json`:

```json
"options": {
  "primary_color": { "label": "Primary color", "type": "color", "default": "#0f6dff", "css": "--theme-primary" },
  "container_width": { "label": "Container width (px)", "type": "number", "default": "1200", "css": "--theme-container", "unit": "px" },
  "footer_text": { "label": "Footer text", "type": "text", "default": "" }
}
```

- `type`: `color` (validated as 6-digit hex), `number` (numeric pixels), `text`.
- `css`: emitted as a CSS custom property in `<head>` (`:root { --theme-primary: ... }`).
- Values are stored per-theme in the settings table (`theme_options_{slug}`); saving clears the settings cache and the page cache.
- Read options in templates: `Theme::option('footer_text')` / `Theme::options()`.
- Admin: Admin > Themes > "Edit theme options".

## 6b. Best Practices & Checklist

### ✅ Do
- Use null-safe fallbacks for all block keys (e.g., `$block['title'] ?? ''`).
- Keep data-heavy logic in Models/Helpers, not inside Blade block templates.
- Reuse existing theme CSS classes (like Bootstrap utility classes) to keep the frontend lightweight.
- Validate and normalize data before saving it in the admin builder.

### ❌ Don't
- Don't trust raw HTML/JS from untrusted block fields; sanitize appropriately.
- Don't query the database repeatedly inside nested loops in block templates. (Use eager loading or specialized helpers).
- Don't use reserved block prefixes (like `__`) for standard user blocks.
- Don't forget to clear the `storage/cache/` when modifying Blade templates if changes are not appearing.

---

## 7. Advanced: Custom Fields (Example)

Themes can interact with custom fields stored in the `__custom_fields` internal block. For example, a Product Gallery block might filter pages based on these fields.

Helper methods in `app/Models/Page.php` to access this data:
- `$page->customFields()`
- `$page->customField('field_name', 'default_value')`
- `$page->isCustomFieldTruthy('show_in_gallery')`

Keep templates presentation-focused by utilizing these Model helpers rather than parsing raw JSON within the views.
