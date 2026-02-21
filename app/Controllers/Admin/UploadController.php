<?php

namespace App\Controllers\Admin;

class UploadController
{
    public static function image()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_FILES['file']) || !isset($_FILES['file']['error'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No file']);
            return;
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'Upload error']);
            return;
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid upload']);
            return;
        }

        $maxBytes = 5 * 1024 * 1024; // 5MB
        if (!empty($file['size']) && (int)$file['size'] > $maxBytes) {
            http_response_code(413);
            echo json_encode(['error' => 'File too large']);
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string)($finfo->file($file['tmp_name']) ?: '');
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mimeType])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid type']);
            return;
        }

        $ext = $allowed[$mimeType];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;

        $dir = dirname(__DIR__, 3) . '/public/uploads/editor/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save']);
            return;
        }

        $url = '/uploads/editor/' . $name;
        echo json_encode(['url' => $url]);
    }
}
