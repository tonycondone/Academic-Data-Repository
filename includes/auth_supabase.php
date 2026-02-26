<?php
/**
 * Supabase Auth Service
 * Interacts with Supabase GoTrue API for authentication
 */

class SupabaseAuth {
    private string $url;
    private string $apiKey;

    public function __construct() {
        $this->url = getenv('SUPABASE_URL') ?: '';
        $this->apiKey = getenv('SUPABASE_ANON_KEY') ?: '';
    }

    /**
     * Sign up a new user
     */
    public function signUp(string $email, string $password, array $data = []): array {
        return $this->request('POST', '/auth/v1/signup', [
            'email' => $email,
            'password' => $password,
            'data' => $data
        ]);
    }

    /**
     * Sign in with email and password
     */
    public function signIn(string $email, string $password): array {
        return $this->request('POST', '/auth/v1/token?grant_type=password', [
            'email' => $email,
            'password' => $password
        ]);
    }

    /**
     * Sign out
     */
    public function signOut(string $accessToken): array {
        return $this->request('POST', '/auth/v1/logout', [], $accessToken);
    }

    /**
     * Get user details from access token
     */
    public function getUser(string $accessToken): array {
        return $this->request('GET', '/auth/v1/user', [], $accessToken);
    }

    /**
     * Internal request helper
     */
    private function request(string $method, string $path, array $body = [], string $accessToken = null): array {
        $ch = curl_init();
        $url = rtrim($this->url, '/') . $path;
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $headers = [
            'apikey: ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method !== 'GET' && !empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $status,
            'data' => json_decode($response, true) ?: $response
        ];
    }
}
?>
