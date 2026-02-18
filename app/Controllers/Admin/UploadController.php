<?php

namespace App\Controllers\Admin;

class UploadController
{
    public static function image()
    {
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No file']);
            return;
        }

        $file = $_FILES['file'];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $allowed = ['jpg','jpeg','png','gif','webp'];

        if (!in_array($ext, $allowed)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid type']);
            return;
        }

        $name = uniqid() . '.' . $ext;

        $path = __DIR__ . '/../../../storage/uploads/' . $name;

        move_uploaded_file($file['tmp_name'], $path);

        $url = '/storage/uploads/' . $name;

        echo json_encode(['url' => $url]);
    }
}
