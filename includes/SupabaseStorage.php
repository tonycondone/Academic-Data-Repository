<?php
/**
 * Supabase Storage Service
 * Handles file uploads to Supabase Storage Buckets
 */

class SupabaseStorage {
    private string $url;
    private string $apiKey;
    private string $bucket;

    public function __construct() {
        $this->url = getenv('SUPABASE_URL') ?: '';
        $this->apiKey = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: getenv('SUPABASE_ANON_KEY') ?: '';
        $this->bucket = 'datasets'; // Default bucket name
    }

    /**
     * Check if storage is properly configured
     */
    public function isConfigured(): bool {
        return !empty($this->url) && !empty($this->apiKey);
    }

    /**
     * Upload a file to the bucket
     */
    public function upload(string $tempFilePath, string $destinationPath, string $contentType): ?string {
        if (!$this->isConfigured()) {
            Logger::error("Supabase Storage not configured.");
            return null;
        }

        $url = rtrim($this->url, '/') . '/storage/v1/object/' . $this->bucket . '/' . ltrim($destinationPath, '/');
        $fileContent = file_get_contents($tempFilePath);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        
        $headers = [
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: ' . $contentType,
            'x-upsert: true'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($status >= 200 && $status < 300) {
            $data = json_decode($response, true);
            return $data['Key'] ?? $destinationPath;
        } else {
            Logger::error("Supabase Storage upload failed", [
                'status' => $status,
                'response' => $response,
                'path' => $destinationPath
            ]);
            return null;
        }
    }

    /**
     * Create a signed URL for a private file
     */
    public function createSignedUrl(string $path, int $expiresIn = 3600): ?string {
        if (!$this->isConfigured()) return null;

        $url = rtrim($this->url, '/') . '/storage/v1/object/sign/' . $this->bucket . '/' . ltrim($path, '/');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['expiresIn' => $expiresIn]));
        
        $headers = [
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($status >= 200 && $status < 300) {
            $data = json_decode($response, true);
            $signedPath = $data['signedURL'] ?? $data['signedUrl'] ?? null;
            if ($signedPath) {
                return rtrim($this->url, '/') . '/storage/v1' . $signedPath;
            }
        }
        
        return null;
    }

    /**
     * Delete a file from the bucket
     */
    public function delete(string $path): bool {
        if (!$this->isConfigured()) return false;

        $url = rtrim($this->url, '/') . '/storage/v1/object/' . $this->bucket . '/' . ltrim($path, '/');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        
        $headers = [
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($status >= 200 && $status < 300);
    }
}
?>
