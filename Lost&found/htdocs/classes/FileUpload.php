<?php
class FileUpload {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;

    public function __construct($uploadDir = '../assets/uploads/', $maxFileSize = 5242880) { // 5MB default
        $this->uploadDir = $uploadDir;
        $this->maxFileSize = $maxFileSize;
        $this->allowedTypes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp'
        ];
    }

    public function uploadPhoto($file, $prefix = 'photo') {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['success']) {
            return $validation;
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $ext;
        $targetPath = $this->uploadDir . $filename;

        // Ensure upload directory exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Return relative path for database storage
            $relativePath = 'assets/uploads/' . $filename;
            return [
                'success' => true,
                'path' => $relativePath,
                'filename' => $filename,
                'message' => 'File uploaded successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to move uploaded file.'
            ];
        }
    }

    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => $this->getUploadErrorMessage($file['error'])
            ];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'message' => 'File size exceeds maximum allowed size of ' . $this->formatBytes($this->maxFileSize) . '.'
            ];
        }

        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.'
            ];
        }

        return ['success' => true];
    }

    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Unknown upload error.';
        }
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function deleteFile($filePath) {
        $fullPath = __DIR__ . '/../' . $filePath;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public function resizeImage($sourcePath, $targetPath, $maxWidth = 800, $maxHeight = 600, $quality = 85) {
        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];

        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Load source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                // Preserve transparency
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        // Resize image
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save resized image
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $targetPath, $quality);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $targetPath, round($quality / 10));
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $targetPath);
                break;
        }

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return true;
    }
}
?> 