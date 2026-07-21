# Theme Developer Manual

This manual explains how themes work in PankhCMS and provides guidelines for building or extending frontend blocks safely and efficiently.

---

## 1. Quick Start (5-Minute Checklist)

To quickly ship a new frontend block in the active theme (e.g., `greenbs`):

1. **Verify Active Theme**: Ensure `ACTIVE_THEME=greenbs` in your `.env` file.
2. **Create Block Template**: Add a new block template at `htdocs/themes/greenbs/views/blocks/{type}.blade.php`.
3. **Register Block in Admin**: Open `views/admin/pages/create.blade.php` and `views/admin/pages/edit.blade.php`. Add a block button with `addBlock('{type}')`.
4. **Test**: Save a page containing that block in the admin panel and verify its rendering on the frontend.
5. **Clear Cache**: If changes aren't visible, clear the compiled Blade cache:
   ```bash
   find storage/cache -type f -name '*.php' -delete
   ```

---

## 2. Theme Directory Structure

The structure of a typical theme (e.g., `greenbs`):

- **`theme.json`**: Defines basic metadata (name, description, author, version).
- **`views/layouts/main.blade.php`**: The global layout shell (head, shared sections like header/footer).
- **`views/page.blade.php`**: The page renderer. It iterates through the page builder's `content_json` and includes dynamic blocks.
- **`views/blocks/*.blade.php`**: Individual component templates (e.g., text, image, faq).
- **`assets/`**: Theme-specific CSS, JS, and images.

---

## 3. Dynamic Rendering Flow

1. **Global Shell**: `layouts/main.blade.php` loads the basic HTML structure and `@yield('content')`.
2. **Page Parsing**: `views/page.blade.php` loops through the JSON block entries stored in `content_json`.
3. **Block Inclusion**: For each block, it reads the `type` and dynamically includes the corresponding template via `@includeIf('blocks.' . $type)`.
   *Example: If a block has `{"type":"text"}`, it attempts to load `views/blocks/text.blade.php`.*

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
Create `htdocs/themes/greenbs/views/blocks/faq.blade.php`:

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
     block.title = 'Frequently Asked Questions';
     block.items = [{ q: 'Question 1', a: 'Answer 1' }];
   }
   ```

3. **Add Editor UI in `render()`**:
   ```javascript
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

*(Ensure you add an `updateFaqItem` helper function to sync the nested arrays in Javascript).*

---

## 6. Best Practices & Checklist

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
