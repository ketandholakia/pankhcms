@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Edit Page</h1>

    <form id="page-form" action="/admin/pages/{{ $page->id }}/update" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                Title
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="title" name="title" type="text" placeholder="Page Title" value="{{ $page->title }}">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="slug">
                Slug
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="slug" name="slug" type="text" placeholder="page-slug" value="{{ $page->slug }}">
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="type">
            Content Type
          </label>
          <select id="type" name="type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            @foreach ($types as $type)
              <option value="{{ $type->slug }}" {{ $page->type === $type->slug ? 'selected' : '' }}>
                {{ $type->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="content">
                Content
            </label>
            <div class="p-4 border bg-gray-50 rounded">
                <h3 class="text-lg font-semibold">Page Builder</h3>
              <p class="text-sm text-gray-500 mt-1">Drag blocks using the handle to reorder them.</p>
                <div id="builder" class="mt-4"></div>
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <button type="button" onclick="addBlock('text')" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center mr-2">
                            + Text Block
                        </button>
                        <button type="button" onclick="addBlock('hero')" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center">
                            + Hero Block
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <select onchange="loadTemplate(this.value)" class="border p-2 rounded">
                          <option value="">Load Template</option>
                          @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                          @endforeach
                        </select>
                        <select onchange="addTemplate(this.value); this.value='';" class="border p-2 rounded">
                          <option value="">Add Template</option>
                          @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                          @endforeach
                        </select>
                    </div>

                </div>
                <input type="hidden" name="content_json" id="content_json">
            </div>
            <div class="mt-4 border-t pt-4 flex items-center">
                <input type="text"
                       id="template_name"
                       placeholder="New template name..."
                       class="border p-2 rounded-l w-full">
                <button type="button"
                        onclick="saveTemplate()" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-r">
                  Save as Template
                </button>
            </div>
        </div>

          <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">Categories</label>
            @php
              $selectedCategoryIds = $page->categories->pluck('id')->all();
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              @foreach($categories as $category)
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" class="rounded" {{ in_array($category->id, $selectedCategoryIds) ? 'checked' : '' }}>
                  <span>{{ $category->name }}</span>
                </label>
              @endforeach
            </div>
          </div>

          <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">Tags</label>
            @php
              $selectedTagIds = $page->tags->pluck('id')->all();
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              @foreach($tags as $tag)
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" class="rounded" {{ in_array($tag->id, $selectedTagIds) ? 'checked' : '' }}>
                  <span>{{ $tag->name }}</span>
                </label>
              @endforeach
            </div>
          </div>

        <h3 class="text-lg font-semibold mt-6">SEO Settings</h3>

        <div class="mt-3">

            <label class="block font-medium">SEO Title</label>
            <input
                type="text"
                name="meta_title"
                value="{{ $page->meta_title }}"
                class="w-full border p-2"
                placeholder="Leave empty to use page title"
            >

        </div>

        <div class="mt-3">

            <label class="block font-medium">Meta Description</label>
            <textarea
                name="meta_description"
                class="w-full border p-2"
                rows="3"
                placeholder="Recommended: 150–160 characters"
            >{{ $page->meta_description }}</textarea>

        </div>

        <div class="flex items-center justify-between">
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                Update Page
            </button>
            <a href="/admin/pages" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Cancel
            </a>
        </div>
    </form>
@endsection

@push('scripts')
<script src="/assets/tinymce/tinymce.min.js"></script>
<script>

// Add Template: Appends template blocks to current blocks
async function addTemplate(id) {
  if (!id) return;
  try {
    const res = await fetch('/admin/templates/' + id);
    const data = await res.json();
    if (data && data.content_json) {
      let newBlocks = JSON.parse(data.content_json);
      // If template is a single object, wrap in array
      if (!Array.isArray(newBlocks)) newBlocks = [newBlocks];
      blocks = blocks.concat(newBlocks);
      render();
    } else {
      alert('Template is empty or invalid.');
    }
  } catch (error) {
    console.error('Error adding template:', error);
    alert('Could not add the template.');
  }
}

let blocks = [];
let draggedBlockIndex = null;

function syncContentJson() {
  const contentInput = document.getElementById('content_json');
  if (contentInput) {
    contentInput.value = JSON.stringify(blocks);
  }
}

function initTinyMCEEditors() {
  if (!window.tinymce) return;

  tinymce.remove('.wysiwyg-text-block');

  document.querySelectorAll('.wysiwyg-text-block').forEach((textarea) => {
    tinymce.init({
      target: textarea,
      menubar: false,
      branding: false,
      promotion: false,
      plugins: 'autoresize link lists table image code',
      toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image table | removeformat code',
      relative_urls: false,
      convert_urls: false,
      automatic_uploads: true,
      file_picker_types: 'image',
      images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/admin/media/upload');

        xhr.upload.onprogress = (e) => {
          if (e.lengthComputable) {
            progress((e.loaded / e.total) * 100);
          }
        };

        xhr.onload = () => {
          if (xhr.status < 200 || xhr.status >= 300) {
            reject('Image upload failed with HTTP ' + xhr.status);
            return;
          }

          try {
            const json = JSON.parse(xhr.responseText);
            const location = json?.location || json?.url || json?.media?.url;
            if (!location) {
              reject('Invalid upload response');
              return;
            }
            resolve(location);
          } catch (error) {
            reject('Invalid upload response');
          }
        };

        xhr.onerror = () => reject('Image upload failed');
        xhr.send(formData);
      }),
      setup: function (editor) {
        editor.on('init change keyup undo redo', function () {
          const idx = parseInt(editor.getElement().dataset.blockIndex || '-1', 10);
          if (!Number.isNaN(idx) && blocks[idx]) {
            blocks[idx].html = editor.getContent();
            syncContentJson();
          }
        });
      }
    });
  });
}

function addBlock(type) {

  let block = { type: type };

  if (type === 'text') block.html = '';
  if (type === 'hero') block.title = '';

  blocks.push(block);
  render();
}

function render() {

  const container = document.getElementById('builder');
  container.innerHTML = '';

  blocks.forEach((b, i) => {
    let blockWrapper = document.createElement('div');
    blockWrapper.className = 'bg-white border border-gray-300 p-4 my-2 rounded shadow-sm';
    blockWrapper.dataset.blockIndex = i;
    blockWrapper.addEventListener('dragover', onBlockDragOver);
    blockWrapper.addEventListener('dragenter', onBlockDragEnter);
    blockWrapper.addEventListener('dragleave', onBlockDragLeave);
    blockWrapper.addEventListener('drop', onBlockDrop);

    let innerHTML = `<div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-2">
                          <span class="cursor-move text-gray-400" draggable="true" data-drag-index="${i}" title="Drag to reorder">☰</span>
                          <strong class="text-gray-700">${b.type.toUpperCase()}</strong>
                        </div>
                        <button type="button" onclick="removeBlock(${i})" class="text-red-500 hover:text-red-700 font-bold">Delete</button>
                     </div>`;
    if (b.type === 'text') {
      innerHTML += `<textarea class="w-full border p-2 rounded wysiwyg-text-block" data-block-index="${i}" rows="8" oninput="updateBlock(${i}, 'html', this.value)">${b.html || ''}</textarea>`;
    }
    if (b.type === 'hero') {
      innerHTML += `<label class="block font-medium text-sm">Title:</label>
        <input type="text" class="w-full border p-2 rounded" value="${b.title || ''}" oninput="updateBlock(${i}, 'title', this.value)">`;
    }
    blockWrapper.innerHTML = innerHTML;
    container.appendChild(blockWrapper);
  });

  container.querySelectorAll('[data-drag-index]').forEach((handle) => {
    handle.addEventListener('dragstart', onBlockDragStart);
    handle.addEventListener('dragend', onBlockDragEnd);
  });

  // Always sync content_json hidden input with blocks
  syncContentJson();
  initTinyMCEEditors();
}

function clearDropHighlights() {
  document.querySelectorAll('#builder [data-block-index]').forEach((blockEl) => {
    blockEl.classList.remove('border-blue-400', 'bg-blue-50');
  });
}

function onBlockDragStart(event) {
  draggedBlockIndex = parseInt(event.target.dataset.dragIndex || '-1', 10);
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(draggedBlockIndex));
  }
}

function onBlockDragEnd() {
  clearDropHighlights();
}

function onBlockDragOver(event) {
  event.preventDefault();
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move';
  }
}

function onBlockDragEnter(event) {
  event.preventDefault();
  event.currentTarget.classList.add('border-blue-400', 'bg-blue-50');
}

function onBlockDragLeave(event) {
  event.currentTarget.classList.remove('border-blue-400', 'bg-blue-50');
}

function onBlockDrop(event) {
  event.preventDefault();
  clearDropHighlights();

  const targetIndex = parseInt(event.currentTarget.dataset.blockIndex || '-1', 10);
  if (Number.isNaN(draggedBlockIndex) || Number.isNaN(targetIndex) || draggedBlockIndex < 0 || targetIndex < 0 || draggedBlockIndex === targetIndex) {
    draggedBlockIndex = null;
    return;
  }

  const [movedBlock] = blocks.splice(draggedBlockIndex, 1);
  const insertIndex = draggedBlockIndex < targetIndex ? targetIndex - 1 : targetIndex;
  blocks.splice(insertIndex, 0, movedBlock);
  draggedBlockIndex = null;
  render();
}

function updateBlock(index, key, value) {
    if (blocks[index]) {
        blocks[index][key] = value;
        syncContentJson();
    }
}

function removeBlock(i) {
  blocks.splice(i,1);
  render();
}

  // Copy HTML to hidden input before submit
document.getElementById('page-form').addEventListener('submit', () => {
  if (window.tinymce) {
    tinymce.triggerSave();
    document.querySelectorAll('.wysiwyg-text-block').forEach((el) => {
      const idx = parseInt(el.dataset.blockIndex || '-1', 10);
      if (!Number.isNaN(idx) && blocks[idx]) {
        blocks[idx].html = el.value;
      }
    });
  }
  syncContentJson();
});

async function loadTemplate(id) {
  if (!id) return;

  if (!confirm('This will replace the current content. Are you sure?')) {
    event.target.value = "";
    return;
  }

  try {
    const res = await fetch('/admin/templates/' + id);
    const data = await res.json();

    if (data && data.content_json) {
      blocks = JSON.parse(data.content_json);
      render();
    } else {
      alert('Template is empty or invalid.');
    }
  } catch (error) {
    console.error('Error loading template:', error);
    alert('Could not load the template.');
  }
}

function saveTemplate() {
  const name = document.getElementById('template_name').value;
  if (!name) {
    alert("Please enter a name for the template.");
    return;
  }

  fetch('/admin/templates', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      name: name,
      content_json: JSON.stringify(blocks)
    })
  })
  .then(res => res.json())
  .then(data => {
    alert("Template saved! It will be available the next time you load the editor.");
    document.getElementById('template_name').value = '';
  });
}

blocks = {!! $page->content_json ?: '[]' !!};
render();
</script>

<script>
document.getElementById('title').addEventListener('input', function () {
  let slug = this.value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '');

  document.getElementById('slug').value = slug;
});
</script>
@endpush
