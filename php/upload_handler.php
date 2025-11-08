<?php
require_once __DIR__ . '/db_connect.php';

/**
 * Handles secure file upload for product images
 * @param array $file $_FILES array element
 * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
 */
function handleProductImageUpload(array $file): array
{
    $uploadDir = __DIR__ . '/../uploads/products/';
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    $allowedExtensions = ['jpg', 'jpeg', 'png'];

    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'path' => null, 'error' => 'Failed to create upload directory.'];
        }
    }

    // Check if file was uploaded
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'path' => null, 'error' => null]; // No file uploaded, not an error
        }
        return ['success' => false, 'path' => null, 'error' => 'File upload error occurred.'];
    }

    // Validate file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'path' => null, 'error' => 'File size exceeds 2MB limit.'];
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid file type. Only JPG, JPEG, and PNG are allowed.'];
    }

    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid file extension.'];
    }

    // Generate unique filename
    $filename = uniqid('product_', true) . '.' . $extension;
    $targetPath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'path' => null, 'error' => 'Failed to save uploaded file.'];
    }

    // Return relative path for database storage
    $relativePath = 'uploads/products/' . $filename;
    return ['success' => true, 'path' => $relativePath, 'error' => null];
}

/**
 * Delete an uploaded product image file
 * @param string $imagePath Relative path from project root
 * @return bool
 */
function deleteProductImage(string $imagePath): bool
{
    // Only delete if it's in the uploads/products directory (security)
    if (strpos($imagePath, 'uploads/products/') !== 0) {
        return false;
    }

    $fullPath = __DIR__ . '/../' . $imagePath;
    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

?>
