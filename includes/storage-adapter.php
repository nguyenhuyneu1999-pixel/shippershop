<?php
/**
 * ShipperShop Storage Adapter
 * Shared hosting: lưu local uploads/
 * VPS: tự động dùng S3/MinIO nếu config
 * KHÔNG CẦN ĐỔI CODE khi chuyển
 */

class StorageAdapter {
    private static $instance = null;
    private $useS3 = false;
    private $s3Config = null;
    
    private function __construct() {
        $configFile = __DIR__ . '/storage-config.php';
        if (file_exists($configFile)) {
            require_once $configFile;
            if (defined('S3_ENDPOINT') && defined('S3_BUCKET')) {
                $this->useS3 = true;
                $this->s3Config = [
                    'endpoint' => S3_ENDPOINT,
                    'bucket' => S3_BUCKET,
                    'key' => defined('S3_KEY') ? S3_KEY : '',
                    'secret' => defined('S3_SECRET') ? S3_SECRET : '',
                    'region' => defined('S3_REGION') ? S3_REGION : 'us-east-1',
                    'cdn_url' => defined('S3_CDN_URL') ? S3_CDN_URL : '',
                ];
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    /**
     * Save uploaded file
     * Returns URL path (relative on local, full URL on S3)
     */
    public function save($tmpPath, $destDir, $filename) {
        if ($this->useS3) {
            return $this->saveToS3($tmpPath, $destDir . '/' . $filename);
        }
        return $this->saveLocal($tmpPath, $destDir, $filename);
    }
    
    private function saveLocal($tmpPath, $destDir, $filename) {
        $dir = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '.', '/') . '/uploads/' . $destDir;
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $dest = $dir . '/' . $filename;
        if (move_uploaded_file($tmpPath, $dest) || copy($tmpPath, $dest)) {
            // Optimize image if applicable
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                require_once __DIR__ . '/image-optimizer.php';
                optimizeImage($dest, $dest, 1200, 82);
            }
            return '/uploads/' . $destDir . '/' . $filename;
        }
        return false;
    }
    
    private function saveToS3($localPath, $s3Key) {
        // Simple S3 upload using pre-signed URL or direct PUT
        $cfg = $this->s3Config;
        $url = $cfg['endpoint'] . '/' . $cfg['bucket'] . '/' . $s3Key;
        
        $content = file_get_contents($localPath);
        $date = gmdate('D, d M Y H:i:s T');
        $contentType = mime_content_type($localPath) ?: 'application/octet-stream';
        
        // Simple PUT (works with MinIO and S3-compatible)
        $ctx = stream_context_create(['http' => [
            'method' => 'PUT',
            'header' => "Content-Type: $contentType\r\nDate: $date\r\nContent-Length: " . strlen($content),
            'content' => $content,
            'timeout' => 30
        ]]);
        
        $result = @file_get_contents($url, false, $ctx);
        
        if ($result !== false || !empty($http_response_header)) {
            return $cfg['cdn_url'] ? $cfg['cdn_url'] . '/' . $s3Key : $url;
        }
        
        // Fallback to local
        error_log("S3 upload failed for $s3Key, falling back to local");
        return $this->saveLocal($localPath, dirname($s3Key), basename($s3Key));
    }
    
    public function getUrl($path) {
        if ($this->useS3 && $this->s3Config['cdn_url'] && strpos($path, '/uploads/') === 0) {
            return $this->s3Config['cdn_url'] . $path;
        }
        return $path;
    }
    
    public function info() {
        return ['mode' => $this->useS3 ? 's3' : 'local', 'config' => $this->useS3 ? ['endpoint' => $this->s3Config['endpoint'], 'bucket' => $this->s3Config['bucket']] : null];
    }
}

function storage() {
    return StorageAdapter::getInstance();
}
