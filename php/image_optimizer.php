<?php
/**
 * Image Optimizer for VectorizeAI
 * Handles large image optimization before processing
 */

class ImageOptimizer {
    private $maxDimension;
    private $maxFileSize;
    private $quality;
    
    public function __construct($maxDimension = 2048, $maxFileSize = 10 * 1024 * 1024, $quality = 90) {
        $this->maxDimension = $maxDimension;
        $this->maxFileSize = $maxFileSize;
        $this->quality = $quality;
    }
    
    /**
     * Optimize image if it's too large
     */
    public function optimizeImage($inputPath, $outputPath = null) {
        if (!file_exists($inputPath)) {
            throw new Exception("Input file does not exist: $inputPath");
        }
        
        // If no output path specified, create one
        if (!$outputPath) {
            $pathInfo = pathinfo($inputPath);
            $outputPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_optimized.' . $pathInfo['extension'];
        }
        
        // Get image info
        $imageInfo = getimagesize($inputPath);
        if (!$imageInfo) {
            throw new Exception("Invalid image file: $inputPath");
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        $fileSize = filesize($inputPath);
        
        error_log("ImageOptimizer: Original image - {$width}x{$height}, {$fileSize} bytes, {$mimeType}");
        
        // Check if optimization is needed
        $needsResize = ($width > $this->maxDimension || $height > $this->maxDimension);
        $needsCompress = ($fileSize > $this->maxFileSize);
        
        if (!$needsResize && !$needsCompress) {
            error_log("ImageOptimizer: No optimization needed");
            return $inputPath; // Return original path if no optimization needed
        }
        
        // Create image resource
        $sourceImage = $this->createImageFromFile($inputPath, $mimeType);
        if (!$sourceImage) {
            throw new Exception("Failed to create image resource from: $inputPath");
        }
        
        // Calculate new dimensions if resizing is needed
        if ($needsResize) {
            if ($width > $height) {
                $newWidth = $this->maxDimension;
                $newHeight = intval(($height * $this->maxDimension) / $width);
            } else {
                $newHeight = $this->maxDimension;
                $newWidth = intval(($width * $this->maxDimension) / $height);
            }
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
        
        error_log("ImageOptimizer: New dimensions - {$newWidth}x{$newHeight}");
        
        // Create new image
        $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($optimizedImage, false);
            imagesavealpha($optimizedImage, true);
            $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
            imagefill($optimizedImage, 0, 0, $transparent);
        }
        
        // Resize image
        imagecopyresampled(
            $optimizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight, $width, $height
        );
        
        // Save optimized image
        $success = $this->saveOptimizedImage($optimizedImage, $outputPath, $mimeType);
        
        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($optimizedImage);
        
        if (!$success) {
            throw new Exception("Failed to save optimized image: $outputPath");
        }
        
        $newFileSize = filesize($outputPath);
        error_log("ImageOptimizer: Optimized image saved - {$newWidth}x{$newHeight}, {$newFileSize} bytes");
        
        return $outputPath;
    }
    
    private function createImageFromFile($filePath, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/gif':
                return imagecreatefromgif($filePath);
            default:
                return false;
        }
    }
    
    private function saveOptimizedImage($imageResource, $outputPath, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagejpeg($imageResource, $outputPath, $this->quality);
            case 'image/png':
                // PNG compression level (0-9, where 9 is maximum compression)
                $pngQuality = 9 - intval(($this->quality / 100) * 9);
                return imagepng($imageResource, $outputPath, $pngQuality);
            case 'image/gif':
                return imagegif($imageResource, $outputPath);
            default:
                return false;
        }
    }
    
    /**
     * Get recommended settings based on image size
     */
    public static function getRecommendedSettings($width, $height, $fileSize) {
        $totalPixels = $width * $height;
        
        if ($totalPixels > 4000000 || $fileSize > 5 * 1024 * 1024) { // > 4MP or > 5MB
            return [
                'max_dimension' => 1536,
                'quality' => 85,
                'message' => 'Large image detected - will be optimized for faster processing'
            ];
        } elseif ($totalPixels > 2000000 || $fileSize > 2 * 1024 * 1024) { // > 2MP or > 2MB
            return [
                'max_dimension' => 2048,
                'quality' => 90,
                'message' => 'Medium image detected - minor optimization may be applied'
            ];
        } else {
            return [
                'max_dimension' => 2048,
                'quality' => 95,
                'message' => 'Image size is optimal for processing'
            ];
        }
    }
}
?>
