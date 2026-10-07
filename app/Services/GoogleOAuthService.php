<?php
declare(strict_types=1);

namespace Noir\App\Services;

final class GoogleOAuthService
{
    public function authorizationUrl(string $redirectUri, string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => (string)config('google.client_id', ''),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);
    }

    public function profile(string $code, string $redirectUri): array
    {
        $token = $this->request('https://oauth2.googleapis.com/token', [], [
            'code' => $code,
            'client_id' => (string)config('google.client_id', ''),
            'client_secret' => (string)config('google.client_secret', ''),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);
        $accessToken = (string)($token['access_token'] ?? '');
        if ($accessToken === '') {
            error_log('Google OAuth token error: access_token not found in response: ' . json_encode($token));
            throw new \RuntimeException('Google did not return an access token.');
        }

        return $this->request('https://openidconnect.googleapis.com/v1/userinfo', [
            'Authorization: Bearer ' . $accessToken,
        ]);
    }

    private function request(string $url, array $headers = [], ?array $form = null): array
    {
        if (!function_exists('curl_init')) throw new \RuntimeException('PHP cURL is required for Google sign-in.');

        $handle = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
        ];

        // Ensure Windows / XAMPP CA cert bundle is loaded if available
        if (file_exists('C:\\xamp\\apache\\bin\\curl-ca-bundle.crt')) {
            $options[CURLOPT_CAINFO] = 'C:\\xamp\\apache\\bin\\curl-ca-bundle.crt';
        }

        if ($form !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($form, '', '&');
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
        }
        curl_setopt_array($handle, $options);
        $body = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($body === false || $error !== '' || $status >= 400) {
            $errDetail = $error !== '' ? $error : "HTTP {$status}: " . (string)$body;
            error_log("Google OAuth request failed at [{$url}]: {$errDetail}");
            
            // Try to extract readable error message from Google response
            $decoded = is_string($body) ? json_decode($body, true) : null;
            $readableError = is_array($decoded) 
                ? ($decoded['error_description'] ?? $decoded['error'] ?? 'Google OAuth request failed.')
                : ($error ?: "HTTP {$status}");
                
            throw new \RuntimeException((string)$readableError);
        }
        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            error_log("Google OAuth invalid JSON response at [{$url}]: " . substr((string)$body, 0, 500));
            throw new \RuntimeException('Google returned an invalid response.');
        }

        return $data;
    }
}
