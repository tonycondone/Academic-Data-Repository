<?php
class SupabaseStorage {
    private $url;
    private $key;
    private $bucket;
    private $base;

    public function __construct() {
        $this->url = getenv('SUPABASE_URL') ?: '';
        $this->key = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '';
        $this->bucket = getenv('SUPABASE_STORAGE_BUCKET') ?: 'uploads';
        $this->base = rtrim($this->url, '/');
    }

    public function isConfigured(): bool {
        return !empty($this->url) && !empty($this->key);
    }

    public function ensureBucketPrivate(): bool {
        $bucketInfo = $this->request('GET', '/storage/v1/bucket/' . $this->bucket);
        if ($bucketInfo['status'] === 404) {
            $resp = $this->request('POST', '/storage/v1/bucket', [
                'name' => $this->bucket,
                'public' => false
            ]);
            return $resp['status'] >= 200 && $resp['status'] < 300;
        }
        return true;
    }

    public function upload(string $localPath, string $destPath, string $contentType = 'application/octet-stream'): ?string {
        if (!file_exists($localPath)) return null;
        if (!$this->isConfigured()) return null;

        $this->ensureBucketPrivate();

        $endpoint = '/storage/v1/object/' . $this->bucket . '/' . ltrim($destPath, '/');
        $headers = [
            'Authorization: Bearer ' . $this->key,
            'Content-Type: ' . $contentType,
            'x-upsert: true'
        ];

        $result = $this->rawUpload($endpoint, $localPath, $headers);
        if ($result['status'] >= 200 && $result['status'] < 300) {
            return ltrim($destPath, '/');
        }
        return null;
    }

    public function createSignedUrl(string $objectPath, int $expiresIn = 3600): ?string {
        if (!$this->isConfigured()) return null;
        $path = ltrim($objectPath, '/');
        $resp = $this->request('POST', '/storage/v1/object/sign/' . $this->bucket . '/' . $path, [
            'expiresIn' => $expiresIn
        ]);
        if ($resp['status'] >= 200 && $resp['status'] < 300) {
            $data = json_decode($resp['body'], true);
            $signed = $data['signedURL'] ?? $data['signedUrl'] ?? null;
            if ($signed) {
                return $this->base . $signed;
            }
        }
        return null;
    }

    private function request(string $method, string $path, ?array $jsonBody = null): array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, rtrim($this->url, '/') . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $headers = [
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($jsonBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonBody));
        }
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $status, 'body' => $body];
    }

    private function rawUpload(string $path, string $localPath, array $headers): array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, rtrim($this->url, '/') . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        $data = file_get_contents($localPath);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $status, 'body' => $body];
    }
}
?>
