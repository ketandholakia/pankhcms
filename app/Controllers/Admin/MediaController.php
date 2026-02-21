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
        $media = $query->get();
        echo \Flight::get('blade')->render('admin.media.index', compact('media', 'search', 'type', 'date'));
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
        $filePath = __DIR__ . '/../../../public' . $media->url;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $media->delete();
        $this->jsonResponse(['success' => true]);
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
        $media = $query->get();
        if (isset($_GET['ajax'])) {
            // Render only the media grid for AJAX
            foreach ($media as $item) {
                echo '<div class="col-6 col-md-3 col-lg-2 mb-3">';
                echo '<div class="card">';
                if (strpos($item->mime_type, 'image/') === 0) {
                    echo '<img src="' . $item->url . '" class="card-img-top" style="height:120px;object-fit:cover">';
                } else {
                    echo '<div class="card-body text-center"><span class="text-muted">' . htmlspecialchars($item->mime_type) . '</span></div>';
                }
                echo '<div class="card-body p-2 text-center">';
                echo '<button type="button" class="btn btn-sm btn-success media-picker-select-btn" data-id="' . $item->id . '" data-url="' . $item->url . '">Select</button>';
                echo '</div></div></div>';
            }
            exit;
        }
        echo \Flight::get('blade')->render('admin.media.picker', compact('media'));
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        \Flight::response()->status($status);
        \Flight::json($data);
    }
}
