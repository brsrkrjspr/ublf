<?php
class FileUpload {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;

    public function __construct($uploadDir = null, $maxFileSize = 5242880) { // 5MB default
        // Resolve upload directory to absolute path
        if ($uploadDir === null) {
            // Default: assets/uploads/ relative to htdocs root
            $baseDir = __DIR__ . '/../';
            $uploadDir = $baseDir . 'assets/uploads/';
        } elseif (substr($uploadDir, 0, 1) !== '/' && substr($uploadDir, 0, 2) !== 'C:' && substr($uploadDir, 0, 2) !== 'D:') {
            // Relative path - resolve it
            $baseDir = __DIR__ . '/../';
            $uploadDir = $baseDir . ltrim($uploadDir, './');
        }
        
        // Normalize path separators and ensure trailing slash
        $this->uploadDir = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $uploadDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
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
            error_log("File validation failed: " . ($validation['message'] ?? 'Unknown error'));
            return $validation;
        }

        // Generate unique filename
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $ext;
        
        // Ensure upload directory exists
        $uploadDir = $this->uploadDir;
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("Failed to create upload directory: $uploadDir");
                return [
                    'success' => false,
                    'message' => 'Failed to create upload directory. Please check server permissions.'
                ];
            }
        }
        
        $targetPath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Verify file was actually moved
            if (!file_exists($targetPath)) {
                error_log("File move appeared successful but file doesn't exist: $targetPath");
                return [
                    'success' => false,
                    'message' => 'File upload verification failed.'
                ];
            }
            
            // Return relative path for database storage
            $relativePath = 'assets/uploads/' . $filename;
            error_log("File uploaded successfully: $targetPath -> $relativePath");
            return [
                'success' => true,
                'path' => $relativePath,
                'filename' => $filename,
                'message' => 'File uploaded successfully.'
            ];
        } else {
            $error = error_get_last();
            error_log("Failed to move uploaded file. Error: " . ($error['message'] ?? 'Unknown'));
            error_log("Source: " . $file['tmp_name'] . ", Target: $targetPath");
            return [
                'success' => false,
                'message' => 'Failed to move uploaded file. Please check server permissions.'
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

        // Check file type - try multiple methods for better compatibility
        $mimeType = null;
        
        // Method 1: Use finfo if available
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detectedMime = @finfo_file($finfo, $file['tmp_name']);
                if ($detectedMime !== false) {
                    $mimeType = $detectedMime;
                }
                @finfo_close($finfo);
            }
        }
        
        // Method 2: Fallback to getimagesize if finfo failed
        if ($mimeType === null && function_exists('getimagesize')) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo !== false && isset($imageInfo['mime'])) {
                $mimeType = $imageInfo['mime'];
            }
        }
        
        // Method 3: Fallback to file extension if both methods failed
        if ($mimeType === null) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $extensionMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            if (isset($extensionMap[$ext])) {
                $mimeType = $extensionMap[$ext];
            }
        }
        
        // Validate MIME type
        if ($mimeType === null || !in_array($mimeType, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed. Detected: ' . ($mimeType ?? 'unknown')
            ];
        }
        
        // Additional validation: verify it's actually an image using getimagesize
        if (function_exists('getimagesize')) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return [
                    'success' => false,
                    'message' => 'File is not a valid image file.'
                ];
            }
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

    /**
     * Upload base64 encoded image
     * 
     * @param string $base64Data Base64 encoded image data (with or without data:image/... prefix)
     * @param string $prefix Prefix for filename
     * @return array Success status and file path
     */
    public function uploadBase64Image($base64Data, $prefix = 'photo') {
        require_once __DIR__ . '/../includes/Logger.php';
        
        Logger::log("=== FileUpload::uploadBase64Image START ===");
        Logger::log("Prefix: $prefix");
        Logger::log("Base64 data length: " . strlen($base64Data));
        Logger::log("Base64 preview: " . substr($base64Data, 0, 50) . '...');
        
        // Remove data:image/... prefix if present
        if (strpos($base64Data, ',') !== false) {
            $base64Data = explode(',', $base64Data)[1];
            Logger::log("Removed data URI prefix");
        }
        
        // Decode base64
        $imageData = base64_decode($base64Data, true);
        if ($imageData === false) {
            Logger::log("ERROR: Failed to decode base64 data");
            return [
                'success' => false,
                'message' => 'Invalid base64 image data.'
            ];
        }
        Logger::log("Base64 decoded successfully. Image data size: " . strlen($imageData) . " bytes");
        
        // Validate image
        $imageInfo = @getimagesizefromstring($imageData);
        if ($imageInfo === false) {
            Logger::log("ERROR: Invalid image data - getimagesizefromstring failed");
            return [
                'success' => false,
                'message' => 'Invalid image data.'
            ];
        }
        Logger::log("Image validated. Dimensions: {$imageInfo[0]}x{$imageInfo[1]}, MIME: {$imageInfo['mime']}");
        
        // Check file size
        $fileSize = strlen($imageData);
        if ($fileSize > $this->maxFileSize) {
            Logger::log("ERROR: File size ($fileSize bytes) exceeds maximum ($this->maxFileSize bytes)");
            return [
                'success' => false,
                'message' => 'File size exceeds maximum allowed size of ' . $this->formatBytes($this->maxFileSize) . '.'
            ];
        }
        
        // Check MIME type
        $mimeType = $imageInfo['mime'];
        if (!in_array($mimeType, $this->allowedTypes)) {
            Logger::log("ERROR: Invalid MIME type: $mimeType");
            return [
                'success' => false,
                'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.'
            ];
        }
        
        // Determine file extension from MIME type
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        $ext = $extensions[$mimeType] ?? 'jpg';
        Logger::log("File extension determined: $ext");
        
        // Generate unique filename
        $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $ext;
        $targetPath = $this->uploadDir . $filename;
        Logger::log("Target path: $targetPath");
        Logger::log("Upload directory: {$this->uploadDir}");
        Logger::log("Directory exists: " . (is_dir($this->uploadDir) ? 'YES' : 'NO'));
        
        // Ensure upload directory exists
        if (!is_dir($this->uploadDir)) {
            $mkdirResult = mkdir($this->uploadDir, 0755, true);
            Logger::log("Created upload directory: " . ($mkdirResult ? 'SUCCESS' : 'FAILED'));
            if (!$mkdirResult) {
                Logger::log("ERROR: Failed to create upload directory: {$this->uploadDir}");
            }
        }
        
        // Save file
        $writeResult = file_put_contents($targetPath, $imageData);
        if ($writeResult !== false) {
            $relativePath = 'assets/uploads/' . $filename;
            Logger::log("SUCCESS: File saved. Bytes written: $writeResult");
            Logger::log("Relative path: $relativePath");
            Logger::log("File exists: " . (file_exists($targetPath) ? 'YES' : 'NO'));
            Logger::log("=== FileUpload::uploadBase64Image END (SUCCESS) ===");
            return [
                'success' => true,
                'path' => $relativePath,
                'filename' => $filename,
                'message' => 'File uploaded successfully.'
            ];
        } else {
            Logger::log("ERROR: Failed to save file to: $targetPath");
            Logger::log("Directory writable: " . (is_writable($this->uploadDir) ? 'YES' : 'NO'));
            Logger::log("=== FileUpload::uploadBase64Image END (FAILED) ===");
            return [
                'success' => false,
                'message' => 'Failed to save uploaded file.'
            ];
        }
    }
}