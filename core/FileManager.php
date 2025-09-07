<?php

namespace TPT\ERP\Core;

/**
 * File Manager
 *
 * Handles file uploads, storage, validation, and management.
 */
class FileManager
{
    private string $uploadPath;
    private array $allowedTypes;
    private int $maxFileSize;
    private array $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private array $documentTypes = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx'];

    public function __construct(
        string $uploadPath = null,
        array $allowedTypes = null,
        int $maxFileSize = null
    ) {
        $this->uploadPath = $uploadPath ?: __DIR__ . '/../storage/uploads';
        $this->allowedTypes = $allowedTypes ?: array_merge($this->imageTypes, $this->documentTypes);
        $this->maxFileSize = $maxFileSize ?: 10 * 1024 * 1024; // 10MB default

        $this->ensureUploadDirectory();
    }

    /**
     * Upload single file
     */
    public function uploadFile(array $file, string $subdirectory = ''): array
    {
        $this->validateFile($file);

        $filename = $this->generateUniqueFilename($file['name']);
        $uploadPath = $this->getUploadPath($subdirectory);

        if (!move_uploaded_file($file['tmp_name'], $uploadPath . '/' . $filename)) {
            throw new \Exception('Failed to move uploaded file');
        }

        return [
            'original_name' => $file['name'],
            'filename' => $filename,
            'path' => $subdirectory ? $subdirectory . '/' . $filename : $filename,
            'size' => $file['size'],
            'type' => $file['type'],
            'extension' => $this->getFileExtension($file['name']),
            'url' => $this->getFileUrl($filename, $subdirectory)
        ];
    }

    /**
     * Upload multiple files
     */
    public function uploadFiles(array $files, string $subdirectory = ''): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $uploadedFiles[] = $this->uploadFile($file, $subdirectory);
            }
        }

        return $uploadedFiles;
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(array $file): void
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception($this->getUploadErrorMessage($file['error']));
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new \Exception('File size exceeds maximum allowed size');
        }

        // Check file type
        $extension = $this->getFileExtension($file['name']);
        if (!in_array(strtolower($extension), $this->allowedTypes)) {
            throw new \Exception('File type not allowed');
        }

        // Validate file content for images
        if (in_array(strtolower($extension), $this->imageTypes)) {
            $this->validateImageFile($file['tmp_name']);
        }
    }

    /**
     * Validate image file
     */
    private function validateImageFile(string $filePath): void
    {
        $imageInfo = getimagesize($filePath);

        if (!$imageInfo) {
            throw new \Exception('Invalid image file');
        }

        // Check for malicious content (basic check)
        if ($imageInfo[0] > 10000 || $imageInfo[1] > 10000) {
            throw new \Exception('Image dimensions too large');
        }
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(string $originalName): string
    {
        $extension = $this->getFileExtension($originalName);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        // Sanitize filename
        $basename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $basename);
        $basename = substr($basename, 0, 100); // Limit length

        do {
            $uniqueId = bin2hex(random_bytes(8));
            $filename = $basename . '_' . $uniqueId . '.' . $extension;
        } while (file_exists($this->uploadPath . '/' . $filename));

        return $filename;
    }

    /**
     * Get file extension
     */
    private function getFileExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Get upload path
     */
    private function getUploadPath(string $subdirectory = ''): string
    {
        $path = $this->uploadPath;

        if ($subdirectory) {
            $path .= '/' . trim($subdirectory, '/');
        }

        $this->ensureDirectory($path);

        return $path;
    }

    /**
     * Ensure upload directory exists
     */
    private function ensureUploadDirectory(): void
    {
        $this->ensureDirectory($this->uploadPath);
    }

    /**
     * Ensure directory exists
     */
    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Get file URL
     */
    private function getFileUrl(string $filename, string $subdirectory = ''): string
    {
        $baseUrl = getenv('APP_URL') ?: 'http://localhost:8080';
        $path = $subdirectory ? $subdirectory . '/' . $filename : $filename;

        return $baseUrl . '/storage/uploads/' . $path;
    }

    /**
     * Delete file
     */
    public function deleteFile(string $filename, string $subdirectory = ''): bool
    {
        $filePath = $this->getUploadPath($subdirectory) . '/' . $filename;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Get file info
     */
    public function getFileInfo(string $filename, string $subdirectory = ''): ?array
    {
        $filePath = $this->getUploadPath($subdirectory) . '/' . $filename;

        if (!file_exists($filePath)) {
            return null;
        }

        $fileInfo = stat($filePath);

        return [
            'filename' => $filename,
            'path' => $subdirectory ? $subdirectory . '/' . $filename : $filename,
            'size' => $fileInfo['size'],
            'modified' => $fileInfo['mtime'],
            'type' => mime_content_type($filePath),
            'url' => $this->getFileUrl($filename, $subdirectory)
        ];
    }

    /**
     * List files in directory
     */
    public function listFiles(string $subdirectory = '', array $filters = []): array
    {
        $directory = $this->getUploadPath($subdirectory);
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $filePath = $directory . '/' . $item;

            if (is_file($filePath)) {
                $fileInfo = $this->getFileInfo($item, $subdirectory);

                if ($fileInfo) {
                    // Apply filters
                    if ($this->matchesFilters($fileInfo, $filters)) {
                        $files[] = $fileInfo;
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Check if file matches filters
     */
    private function matchesFilters(array $fileInfo, array $filters): bool
    {
        foreach ($filters as $key => $value) {
            if (!isset($fileInfo[$key])) {
                return false;
            }

            if (is_array($value)) {
                if (!in_array($fileInfo[$key], $value)) {
                    return false;
                }
            } else {
                if ($fileInfo[$key] !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Create thumbnail for image
     */
    public function createThumbnail(string $filename, string $subdirectory = '', int $width = 200, int $height = 200): ?string
    {
        $filePath = $this->getUploadPath($subdirectory) . '/' . $filename;

        if (!file_exists($filePath)) {
            return null;
        }

        $extension = $this->getFileExtension($filename);

        if (!in_array(strtolower($extension), $this->imageTypes)) {
            return null;
        }

        $thumbnailFilename = 'thumb_' . $width . 'x' . $height . '_' . $filename;
        $thumbnailPath = $this->getUploadPath($subdirectory . '/thumbnails') . '/' . $thumbnailFilename;

        // Create thumbnail using GD
        $this->createImageThumbnail($filePath, $thumbnailPath, $width, $height);

        return $thumbnailFilename;
    }

    /**
     * Create image thumbnail
     */
    private function createImageThumbnail(string $source, string $destination, int $width, int $height): void
    {
        $imageInfo = getimagesize($source);
        $mime = $imageInfo['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($source);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($source);
                break;
            default:
                throw new \Exception('Unsupported image type');
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Calculate thumbnail dimensions
        $ratio = min($width / $sourceWidth, $height / $sourceHeight);
        $thumbWidth = (int) ($sourceWidth * $ratio);
        $thumbHeight = (int) ($sourceHeight * $ratio);

        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

        // Preserve transparency for PNG/GIF
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagecolortransparent($thumbnail, imagecolorallocatealpha($thumbnail, 0, 0, 0, 127));
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $sourceWidth, $sourceHeight);

        // Save thumbnail
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($thumbnail, $destination, 90);
                break;
            case 'image/png':
                imagepng($thumbnail, $destination, 9);
                break;
            case 'image/gif':
                imagegif($thumbnail, $destination);
                break;
            case 'image/webp':
                imagewebp($thumbnail, $destination, 90);
                break;
        }

        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $error): string
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Clean old files
     */
    public function cleanOldFiles(int $daysOld = 30, string $subdirectory = ''): int
    {
        $directory = $this->getUploadPath($subdirectory);
        $deletedCount = 0;

        if (!is_dir($directory)) {
            return $deletedCount;
        }

        $items = scandir($directory);
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $filePath = $directory . '/' . $item;

            if (is_file($filePath) && filemtime($filePath) < $cutoffTime) {
                if (unlink($filePath)) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }
}
