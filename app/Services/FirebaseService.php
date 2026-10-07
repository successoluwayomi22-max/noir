<?php
declare(strict_types=1);

namespace Noir\App\Services;

class FirebaseService
{
    private string $credentialsPath;

    public function __construct()
    {
        $possiblePaths = [
            __DIR__ . '/../Config/firebase_credentials.json',
            __DIR__ . '/../../config/firebase_credentials.json',
            defined('BASE_PATH') ? BASE_PATH . '/config/firebase_credentials.json' : '',
            defined('BASE_PATH') ? BASE_PATH . '/firebase_credentials.json' : ''
        ];

        $this->credentialsPath = '';
        foreach ($possiblePaths as $p) {
            if ($p !== '' && file_exists($p)) {
                $this->credentialsPath = $p;
                break;
            }
        }
        if ($this->credentialsPath === '') {
            $this->credentialsPath = __DIR__ . '/../Config/firebase_credentials.json';
        }
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        if ($name === 'sendPushNotification') {
            return (new self())->sendPushNotification(...$arguments);
        }
        throw new \BadMethodCallException("Method {$name} does not exist.");
    }

    private function getAccessToken(): ?string
    {
        if (!file_exists($this->credentialsPath)) {
            error_log('Firebase credentials not found.');
            return null;
        }

        $credentials = json_decode(file_get_contents($this->credentialsPath), true);
        if (!$credentials || !isset($credentials['client_email']) || !isset($credentials['private_key'])) {
            return null;
        }

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $payload = json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $credentials['token_uri'],
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = '';
        openssl_sign($base64UrlHeader . '.' . $base64UrlPayload, $signature, $credentials['private_key'], 'sha256WithRSAEncryption');
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        $jwt = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $credentials['token_uri']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode((string)$response, true);
        return $data['access_token'] ?? null;
    }

    public function sendPushNotification(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (!file_exists($this->credentialsPath)) {
            return false;
        }

        $credentials = json_decode(file_get_contents($this->credentialsPath), true);
        $projectId = $credentials['project_id'] ?? null;
        if (!$projectId) return false;

        $accessToken = $this->getAccessToken();
        if (!$accessToken) return false;

        // FCM only accepts string values in data payload
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[$key] = (string)$value;
        }

        $message = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $stringData
            ]
        ];

        $ch = curl_init("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message, JSON_UNESCAPED_SLASHES));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('FCM Error: ' . $response);
            return false;
        }
        return true;
    }
}
