# Theme Developer Manual

## Table of Contents

1. [Quick Start Checklist (5 Minutes)](#1-quick-start-checklist-5-minutes)
2. [Common Block Type Naming Rules](#2-common-block-type-naming-rules)
3. [Purpose](#3-purpose)
4. [Theme Directory Structure](#4-theme-directory-structure)
5. [Rendering Flow](#5-rendering-flow)
6. [How To Create a New Frontend Block](#6-how-to-create-a-new-frontend-block)
7. [Product Gallery (Current Implementation)](#7-product-gallery-current-implementation)
8. [Admin Page Builder Integration](#8-admin-page-builder-integration)
9. [Best Practices for Theme Development](#9-best-practices-for-theme-development)
10. [Troubleshooting](#10-troubleshooting)
11. [Recommended Next Improvements](#11-recommended-next-improvements)
12. [New Block Starter (Copy-Paste)](#12-new-block-starter-copy-paste)
13. [Block Do/Don’t Checklist](#13-block-dodont-checklist)

---

## 1) Quick Start Checklist (5 Minutes)

1. Confirm active theme is `greenbs` in `.env` (`ACTIVE_THEME=greenbs`).
2. Open these theme files:
   - `htdocs/themes/greenbs/views/layouts/main.blade.php`
   - `htdocs/themes/greenbs/views/page.blade.php`
   - `htdocs/themes/greenbs/views/blocks/`
3. Add or edit a block template in `views/blocks/{type}.blade.php`.
4. In admin builder files, add block button with `addBlock('{type}')`:
   - `views/admin/pages/create.blade.php`
   - `views/admin/pages/edit.blade.php`
5. Save a page containing that block and verify frontend rendering.
6. If changes are not visible, clear compiled cache:
   - `find storage/cache -type f -name '*.php' -delete`

This checklist is the fastest path to ship a new frontend block.

---

## 2) Common Block Type Naming Rules

| Rule | Good | Avoid | Why |
|---|---|---|---|
| Use lowercase only | `product_gallery` | `ProductGallery` | Matches dynamic include path safely |
| Use snake_case words | `hero_basic` | `hero-basic` | Keeps file + type naming predictable |
| Keep type == filename | `type: product_gallery` + `blocks/product_gallery.blade.php` | Type and filename mismatch | `@includeIf('blocks.' . type)` depends on exact match |
| Keep names short and specific | `cta_box`, `team_grid` | `my_custom_super_block_v2` | Easier admin usage and maintenance |
| Reserve internal prefix | `__custom_fields` (system only) | Using `__*` for regular blocks | Avoid collision with internal metadata blocks |

Recommended pattern for user blocks: `feature_name` (example: `faq`, `product_gallery`, `testimonial_slider`).

---

## 3) Purpose

This manual explains how themes work in PankhCMS and how to build or extend frontend blocks safely.

It is focused on the current implementation used by the `greenbs` theme.

---

## 4) Theme Directory Structure

Main theme location:

- `htdocs/themes/greenbs/theme.json`
- `htdocs/themes/greenbs/views/layouts/main.blade.php`
- `htdocs/themes/greenbs/views/page.blade.php`
- `htdocs/themes/greenbs/views/blocks/*.blade.php`
- `htdocs/themes/greenbs/assets/*`

### `theme.json`

Defines basic metadata for the theme:

- name
- description
- author
- author_url
- version

---

## 5) Rendering Flow

### Layout

`layouts/main.blade.php` provides the global shell:

- head, CSS and JS includes
- shared sections (`topbar`, `header`, `slider_bootstrap`, `footer`)
- `@yield('content')` where page content blocks render

### Dynamic Block Rendering

`views/page.blade.php` renders page builder blocks from `content_json`:

1. Loop each block entry.
2. Read `type`.
3. Include `blocks.{type}` using `@includeIf`.

If a block has:

```json
{"type":"text"}
```

the renderer attempts to load:

- `views/blocks/text.blade.php`

If no matching block file exists, it is skipped safely.

---

## 6) How To Create a New Frontend Block

Example: create a `faq` block.

### Step A: Add block template

Create file:

- `htdocs/themes/greenbs/views/blocks/faq.blade.php`

Use block data via `$block`, for example:

- `$block['title'] ?? ''`
- `$block['items'] ?? []`

### Step B: Add block in Admin Builder UI

Update both files:

- `views/admin/pages/create.blade.php`
- `views/admin/pages/edit.blade.php`

Add a button:

- `addBlock('faq')`

Then update JS `render()` to show editable inputs for that block type.

### Step C: Save and test

1. Add the block to a page in admin.
2. Save page.
3. Open frontend page and verify rendering.

---

## 7) Product Gallery (Current Implementation)

Product Gallery is powered by Pages + Content Types + Custom Fields.

### Source block file

- `htdocs/themes/greenbs/views/blocks/product_gallery.blade.php`

### Data rules

A page appears in gallery only when all conditions match:

1. Page type is `product`.
2. Page status is `published`.
3. Custom field `show_in_product_gallery` is true.

### Sorting

Gallery order uses custom field `gallery_order` (ascending).

Fallback order is page title (A-Z) for stable ordering.

### Custom field storage note

Custom field values are currently persisted in `pages.content_json` inside an internal metadata block:

- block type: `__custom_fields`
- values path: `fields.{field_name}`

Helper methods in `app/Models/Page.php` read these values:

- `customFields()`
- `customField(name, default)`
- `isCustomFieldTruthy(name)`

---

## 8) Admin Page Builder Integration

The page builder supports adding `product_gallery` block directly from admin:

- `views/admin/pages/create.blade.php`
- `views/admin/pages/edit.blade.php`

When this block exists in page JSON, frontend auto-renders it through dynamic block include.

---

## 9) Best Practices for Theme Development

1. Keep block templates presentation-focused; avoid heavy DB logic in block files.
2. Reuse model helpers for computed logic (example: custom field truth checks).
3. Use null-safe access for block keys (`$block['x'] ?? ''`).
4. Keep CSS in theme assets, not inline in many blocks.
5. Ensure new block names are lowercase snake_case to match include path convention.
6. Run syntax checks after changes:
   - `php -l path/to/file.php`

---

## 10) Troubleshooting

### New block not visible on frontend

- Check block `type` value saved in page JSON.
- Ensure file exists at `views/blocks/{type}.blade.php`.
- Clear cache:
  - `find storage/cache -type f -name '*.php' -delete`

### Custom fields not affecting block behavior

- Confirm content type fields are defined in admin.
- Confirm values were saved in page update/create.
- Verify field names exactly match expected keys (example: `show_in_product_gallery`).

### Wrong product order

- Check `gallery_order` values are numeric.
- Empty values go to fallback ordering.

---

## 11) Recommended Next Improvements

1. Move custom field values from `content_json` to dedicated relational table for scalability.
2. Add admin-level validation per custom field type/options.
3. Add reusable helper for parsing select/radio field options.
4. Add frontend block-level caching for heavy pages.

---

## 12) New Block Starter (Copy-Paste)

Use this starter when creating a new block quickly.

### A) Frontend block template

Create: `htdocs/themes/greenbs/views/blocks/faq.blade.php`

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

### B) Add button in admin builder

In both files:

- `views/admin/pages/create.blade.php`
- `views/admin/pages/edit.blade.php`

Add button near other block buttons:

```html
<button type="button" onclick="addBlock('faq')" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center ml-2">
  + FAQ
</button>
```

### C) Add default block data in `addBlock(type)`

```js
if (type === 'faq') {
  block.title = 'Frequently Asked Questions';
  block.items = [
    { q: 'Question 1', a: 'Answer 1' },
    { q: 'Question 2', a: 'Answer 2' },
  ];
}
```

### D) Add editor UI in `render()`

```js
if (b.type === 'faq') {
  innerHTML += `<label class="block font-medium text-sm">Title:</label>
    <input type="text" class="w-full border p-2 rounded mb-2" value="${b.title || ''}" oninput="updateBlock(${i}, 'title', this.value)">`;

  (b.items || []).forEach((item, idx) => {
    innerHTML += `<div class="border rounded p-2 mb-2">
      <input type="text" class="w-full border p-2 rounded mb-1" value="${item.q || ''}" oninput="updateFaqItem(${i}, ${idx}, 'q', this.value)" placeholder="Question">
      <textarea class="w-full border p-2 rounded" oninput="updateFaqItem(${i}, ${idx}, 'a', this.value)" placeholder="Answer">${item.a || ''}</textarea>
    </div>`;
  });
}
```

Add helper:

```js
function updateFaqItem(blockIndex, itemIndex, key, value) {
  if (!blocks[blockIndex] || !Array.isArray(blocks[blockIndex].items)) return;
  if (!blocks[blockIndex].items[itemIndex]) return;
  blocks[blockIndex].items[itemIndex][key] = value;
  syncContentJson();
}
```

### E) Test

1. Add FAQ block to a page.
2. Save page.
3. Open frontend page and verify output.
4. If needed, clear cache: `find storage/cache -type f -name '*.php' -delete`

---

## 13) Block Do/Don’t Checklist

### ✅ Do

- Use safe fallback access for all block keys (`$block['key'] ?? ''`).
- Keep data-heavy logic in models/helpers, not repeated across many block files.
- Validate and normalize data before saving in admin builder JSON.
- Keep block names consistent with file names (exact type-to-template match).
- Reuse existing assets/CSS classes to keep frontend lightweight.
- Test block behavior with empty, partial, and invalid data.

### ❌ Don’t

- Don’t trust raw HTML/JS from untrusted block fields without sanitization.
- Don’t query database repeatedly inside nested loops in block templates.
- Don’t hardcode environment-specific URLs in templates.
- Don’t use internal reserved block types (like `__custom_fields`) for user UI blocks.
- Don’t break existing page JSON shape when adding new block fields.
- Don’t forget cache clear when debugging stale output.

---

## 14) Quick Reference Appendix

### Most-used files

- Theme layout: `htdocs/themes/greenbs/views/layouts/main.blade.php`
- Theme page renderer: `htdocs/themes/greenbs/views/page.blade.php`
- Theme blocks: `htdocs/themes/greenbs/views/blocks/`
- Admin page create builder: `views/admin/pages/create.blade.php`
- Admin page edit builder: `views/admin/pages/edit.blade.php`
- Page model helpers: `app/Models/Page.php`

### Add a new block in 30 seconds

1. Create `htdocs/themes/greenbs/views/blocks/{type}.blade.php`
2. Add `addBlock('{type}')` button in create/edit admin files.
3. Add default data in `addBlock(type)` JS.
4. Add editor controls in `render()` JS.
5. Save page and verify frontend.

### Product gallery keys

- Include flag field: `show_in_product_gallery` (`1/0`)
- Sort field: `gallery_order` (integer; lower first)
- Internal custom field storage block: `__custom_fields`

### Useful commands

- Clear compiled cache:
  - `find storage/cache -type f -name '*.php' -delete`
- Syntax check a file:
  - `php -l path/to/file.php`

