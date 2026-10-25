<?php

namespace App\Controllers\Admin;

use App\Models\Media;
use Exception;

class MediaController
{
    public function index(): void
    {
        $query = Media::orderBy('created_at', 'desc');
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? '';
        $date = $_GET['date'] ?? '';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%$search%")
                  ->orWhere('filename', 'like', "%$search%")
                  ->orWhere('title', 'like', "%$search%")
                  ->orWhere('alt', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
            });
        }
        if ($type) {
            $query->where('mime_type', 'like', "$type%");
        }
        if ($date) {
            $query->whereDate('created_at', $date);
        }

        // Hand-rolled pagination, same style as the admin pages index.
        $perPage = 50;
        $pageNum = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $total = (int) $query->count();
        $media = $query->skip(($pageNum - 1) * $perPage)->take($perPage)->get();
        $paginator = [
            'current' => $pageNum,
            'last' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
            'perPage' => $perPage,
        ];
        echo \Flight::get('blade')->render('admin.media.index', compact('media', 'search', 'type', 'date', 'paginator'));
    }

    public function upload(): void
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'No file uploaded or upload error.'], 400);
            return;
        }
        $file = $_FILES['file'];
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $this->jsonResponse(['error' => 'Invalid upload.'], 400);
            return;
        }

        $maxBytes = 20 * 1024 * 1024; // 20MB
        if (!empty($file['size']) && (int)$file['size'] > $maxBytes) {
            $this->jsonResponse(['error' => 'File too large.'], 413);
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string)($finfo->file($file['tmp_name']) ?: '');

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        if (!isset($allowed[$mimeType])) {
            $this->jsonResponse(['error' => 'Unsupported file type.'], 400);
            return;
        }

        $ext = $allowed[$mimeType];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;

        $targetDir = dirname(__DIR__, 3) . '/public/uploads/media/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $targetPath = $targetDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->jsonResponse(['error' => 'Failed to move uploaded file.'], 500);
            return;
        }
        $media = Media::create([
            'filename' => $filename,
            'original_name' => isset($file['name']) ? substr((string)$file['name'], 0, 255) : null,
            'mime_type' => $mimeType,
            'size' => isset($file['size']) ? (int)$file['size'] : null,
            'url' => '/uploads/media/' . $filename,
        ]);
        $this->jsonResponse(['success' => true, 'media' => $media], 201);
    }

    public function delete($id): void
    {
        $media = Media::find($id);
        if (!$media) {
            $this->jsonResponse(['error' => 'Media not found.'], 404);
            return;
        }

        // Content-integrity guard: a media file that is still referenced
        // by pages, global blocks or settings must not be deleted — the
        // references would instantly become broken images.
        $references = $this->findMediaReferences($media);
        if ($references !== []) {
            $this->jsonResponse([
                'error' => 'This file is still in use and was not deleted. Remove it from the places listed first.',
                'references' => $references,
            ], 409);
            return;
        }

        $filePath = __DIR__ . '/../../../public' . $media->url;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $media->delete();
        \App\Middleware\PageCache::clear();
        $this->jsonResponse(['success' => true]);
    }

    /**
     * Find live content that references this media file's URL. Scans:
     * page featured_image (exact) + page content_json/content and global
     * block content (boundary-aware substring, so "/a.png" never matches
     * "/a.png.png"-style names or filename fragments), and every settings
     * value (logo_path, favicon_path, og images, ...).
     *
     * @return string[] human-readable reference descriptions; empty = safe to delete
     */
    private function findMediaReferences(Media $media): array
    {
        $url = trim((string) $media->url);
        if ($url === '') {
            return [];
        }

        $references = [];

        foreach (\App\Models\Page::select('id', 'title', 'slug', 'featured_image', 'content', 'content_json')->get() as $page) {
            if ((string) $page->featured_image === $url) {
                $references[] = 'Page "' . $page->title . '" (featured image)';
                continue;
            }
            if (self::contentReferencesUrl((string) $page->content_json, $url)
                || self::contentReferencesUrl((string) $page->content, $url)) {
                $references[] = 'Page "' . $page->title . '" (content)';
            }
        }

        foreach (\App\Models\GlobalBlock::select('id', 'title', 'content')->get() as $block) {
            if (self::contentReferencesUrl((string) $block->content, $url)) {
                $references[] = 'Global block "' . $block->title . '"';
            }
        }

        try {
            foreach (\Illuminate\Database\Capsule\Manager::table('settings')->get() as $setting) {
                if (self::contentReferencesUrl((string) $setting->value, $url)) {
                    $references[] = 'Setting "' . $setting->key . '"';
                }
            }
        } catch (\Throwable $e) {
            // Settings table unavailable: do not block deletion on it.
        }

        return $references;
    }

    /**
     * Whether $haystack references exactly $url. Every occurrence of the
     * URL must be followed by a non-name character (end, quote, space,
     * ?, #, /, >, ...) — a longer filename that merely extends a shorter
     * one (abc1234.png vs abc123.png) is NOT a match, so deletion is
     * never blocked by filename-substring false positives.
     *
     * JSON-encoded content stores "/" as "\/", so the match also runs
     * against the backslash-unescaped form of the haystack; otherwise
     * every block-encoded reference would be invisible.
     */
    public static function contentReferencesUrl(string $haystack, string $url): bool
    {
        if ($url === '' || $haystack === '') {
            return false;
        }

        $haystacks = [$haystack, str_replace('\\', '', $haystack)];

        foreach ($haystacks as $candidate) {
            $offset = 0;
            while (($pos = strpos($candidate, $url, $offset)) !== false) {
                $next = $candidate[$pos + strlen($url)] ?? '';
                if ($next === '' || !str_contains('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789._-', $next)) {
                    return true;
                }
                $offset = $pos + 1;
            }
        }

        return false;
    }

    public function picker(): void
    {
        $query = Media::orderBy('created_at', 'desc');
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? '';
        $date = $_GET['date'] ?? '';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%$search%")
                  ->orWhere('filename', 'like', "%$search%")
                  ->orWhere('title', 'like', "%$search%")
                  ->orWhere('alt', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
            });
        }
        if ($type) {
            $query->where('mime_type', 'like', "$type%");
        }
        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $perPage = 50;
        $pageNum = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $total = (int) $query->count();
        $media = $query->skip(($pageNum - 1) * $perPage)->take($perPage)->get();
        if (isset($_GET['ajax'])) {
            // Render only the media grid for AJAX
            foreach ($media as $item) {
                echo '<div class="col-6 col-md-3 col-lg-2 mb-3">';
                echo '<div class="card">';
                if (strpos($item->mime_type, 'image/') === 0) {
                    echo '<img src="' . htmlspecialchars((string) $item->url, ENT_QUOTES, 'UTF-8') . '" class="card-img-top" style="height:120px;object-fit:cover">';
                } else {
                    echo '<div class="card-body text-center"><span class="text-muted">' . htmlspecialchars($item->mime_type) . '</span></div>';
                }
                echo '<div class="card-body p-2 text-center">';
                echo '<button type="button" class="btn btn-sm btn-success media-picker-select-btn" data-id="' . (int) $item->id . '" data-url="' . htmlspecialchars((string) $item->url, ENT_QUOTES, 'UTF-8') . '">Select</button>';
                echo '</div></div></div>';
            }
            exit;
        }
        $paginator = [
            'current' => $pageNum,
            'last' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
            'perPage' => $perPage,
        ];
        echo \Flight::get('blade')->render('admin.media.picker', compact('media', 'paginator'));
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        // Flight::json() re-sets the response status from its $code
        // parameter (default 200) — the status must travel WITH the json
        // call, not be set beforehand, or error responses ship as 200.
        \Flight::json($data, $status);
    }
}
