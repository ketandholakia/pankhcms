@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Create New Page</h1>

    <form action="/admin/pages" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                Title
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="title" name="title" type="text" placeholder="Page Title">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="slug">
                Slug
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="slug" name="slug" type="text" placeholder="page-slug">
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="content">
                Content
            </label>
            <div class="p-4 border bg-gray-50 rounded">
                <h3 class="text-lg font-semibold">Page Builder</h3>
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
                    <div>
                        <select onchange="loadTemplate(this.value)" class="border p-2 rounded">
                          <option value="">Load Template</option>
                          @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                          @endforeach
                        </select>
                    </div>
                </div>
                <input type="hidden" name="content_json" id="content_json" value="[]">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              @foreach($categories as $category)
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" class="rounded">
                  <span>{{ $category->name }}</span>
                </label>
              @endforeach
            </div>
          </div>

          <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">Tags</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              @foreach($tags as $tag)
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" class="rounded">
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
                value=""
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
            ></textarea>

        </div>

        <div class="flex items-center justify-between">
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                Create Page
            </button>
            <a href="/admin/pages" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Cancel
            </a>
        </div>
    </form>
@endsection

@push('scripts')
<script>

let blocks = [];

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

    let innerHTML = `<div class="flex justify-between items-center mb-2">
                        <strong class="text-gray-700">${b.type.toUpperCase()}</strong>
                        <button type="button" onclick="removeBlock(${i})" class="text-red-500 hover:text-red-700 font-bold">Delete</button>
                     </div>`;

    if (b.type === 'text') {
      innerHTML += `<textarea class="w-full border p-2 rounded" rows="5" oninput="updateBlock(${i}, 'html', this.value)">${b.html || ''}</textarea>`;
    }

    if (b.type === 'hero') {
      innerHTML += `<label class="block font-medium text-sm">Title:</label>
        <input type="text" class="w-full border p-2 rounded" value="${b.title || ''}" oninput="updateBlock(${i}, 'title', this.value)">`;
    }
    
    blockWrapper.innerHTML = innerHTML;
    container.appendChild(blockWrapper);
  });
}

function updateBlock(index, key, value) {
    if (blocks[index]) {
        blocks[index][key] = value;
    }
}

function removeBlock(i) {
  blocks.splice(i,1);
  render();
}

// Save JSON before submit
document.querySelector('form').addEventListener('submit', () => {
  document.getElementById('content_json').value =
    JSON.stringify(blocks);
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

render(); // Initial render for an empty builder
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
