<?php

namespace App\Services\Firebase;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class FirebaseService
{
    private Client $httpClient;
    private string $projectId;
    private array $credentials;
    private string $accessToken;
    private const FCM_V1_API_URL = 'https://fcm.googleapis.com/v1/projects';
    private const GOOGLE_OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const TOKEN_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const ACCESS_TOKEN_CACHE_KEY = 'firebase_access_token';
    private const ACCESS_TOKEN_CACHE_DURATION = 3500; // 58 minutes (tokens expire in 1 hour)

    public function __construct()
    {
        $this->httpClient = new Client([
            'verify' => true, // Enable SSL certificate verification
            'timeout' => 30,
        ]);

        // Load Firebase Service Account credentials
        $credentialsPath = env('FIREBASE_SERVICE_ACCOUNT_PATH');
        if (!$credentialsPath || !file_exists($credentialsPath)) {
            throw new Exception('Firebase Service Account credentials file not found at: ' . $credentialsPath);
        }

        $this->credentials = json_decode(file_get_contents($credentialsPath), true);
        if (!$this->credentials) {
            throw new Exception('Invalid Firebase Service Account credentials JSON');
        }

        $this->projectId = env('FIREBASE_PROJECT_ID');
        if (!$this->projectId) {
            throw new Exception('FIREBASE_PROJECT_ID environment variable not set');
        }
    }

    /**
     * Generate OAuth 2.0 access token from Service Account
     * Tokens are cached to minimize API calls
     */
    private function getAccessToken(): string
    {
        // Check if we have a cached token
        $cachedToken = Cache::get(self::ACCESS_TOKEN_CACHE_KEY);
        if ($cachedToken) {
            return $cachedToken;
        }

        try {
            // Create JWT for OAuth 2.0 token request
            $jwt = $this->createJWT();

            // Request access token from Google OAuth 2.0 endpoint
            $response = $this->httpClient->post(self::GOOGLE_OAUTH_TOKEN_URL, [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            if (!isset($responseData['access_token'])) {
                throw new Exception('Failed to obtain access token from Google OAuth');
            }

            $accessToken = $responseData['access_token'];

            // Cache the token for ~58 minutes (expires in 1 hour)
            Cache::put(self::ACCESS_TOKEN_CACHE_KEY, $accessToken, self::ACCESS_TOKEN_CACHE_DURATION);

            return $accessToken;
        } catch (Exception $e) {
            Log::error('Firebase OAuth token generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a JWT token for OAuth 2.0 authentication
     */
    private function createJWT(): string
    {
        $now = time();
        $expiresAt = $now + 3600; // 1 hour from now

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $this->credentials['client_email'],
            'scope' => self::TOKEN_SCOPE,
            'aud' => self::GOOGLE_OAUTH_TOKEN_URL,
            'exp' => $expiresAt,
            'iat' => $now,
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload));
        $signatureInput = "{$encodedHeader}.{$encodedPayload}";

        // Sign with private key
        $privateKey = openssl_pkey_get_private($this->credentials['private_key']);
        if (!$privateKey) {
            throw new Exception('Failed to load Firebase Service Account private key');
        }

        openssl_sign($signatureInput, $signature, $privateKey, 'sha256');
        $encodedSignature = $this->base64UrlEncode($signature);

        return "{$signatureInput}.{$encodedSignature}";
    }

    /**
     * URL-safe base64 encoding
     */
    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Send notification to device tokens
     * 
     * @param array $tokens Device tokens to send to
     * @param array $data Notification data
     * @param string|null $type Notification type: 'android', 'ios', 'silent'
     * @return array Results array with 'success' count and 'failed' array
     */
    public function sendToTokens(array $tokens, array $data = [], ?string $type = null): array
    {
        $results = [
            'success' => 0,
            'failed' => [],
        ];

        if (empty($tokens)) {
            Log::warning('Firebase: No tokens provided for sending notification');
            return $results;
        }

        try {
            $accessToken = $this->getAccessToken();

            foreach ($tokens as $token) {
                try {
                    $this->sendMessage($accessToken, $this->buildTokenMessage($token, $data, $type));
                    $results['success']++;
                } catch (Exception $e) {
                    Log::error('Firebase: Failed to send to token', [
                        'token' => substr($token, 0, 20) . '...', // Log only first 20 chars
                        'error' => $e->getMessage(),
                    ]);
                    $results['failed'][] = [
                        'token' => $token,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        } catch (Exception $e) {
            Log::error('Firebase: Failed to get access token', [
                'error' => $e->getMessage(),
            ]);
            $results['failed'] = array_map(fn($token) => ['token' => $token, 'error' => 'Authentication failed'], $tokens);
        }

        return $results;
    }

    /**
     * Send notification to a topic
     * 
     * @param string $topic Topic name to send to
     * @param array $data Notification data
     * @param string|null $type Notification type: 'android', 'ios', 'silent'
     * @return array Result array with 'success' boolean and 'error' if failed
     */
    public function sendToTopic(string $topic, array $data = [], ?string $type = null): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $message = $this->buildTopicMessage($topic, $data, $type);
            $this->sendMessage($accessToken, $message);

            return ['success' => true];
        } catch (Exception $e) {
            Log::error('Firebase: Failed to send to topic', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Subscribe a device token to a topic
     */
    public function subscribeToTopic(string $token, string $topic): bool
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = $this->httpClient->post(
                "{$this->getCoreApiUrl()}/iid:batchAdd",
                [
                    'headers' => [
                        'Authorization' => "Bearer {$accessToken}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'to' => "/topics/{$topic}",
                        'registration_tokens' => [$token],
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new Exception("Subscription failed with status code: {$statusCode}");
            }

            Log::info('Firebase: Device subscribed to topic', ['topic' => $topic]);
            return true;
        } catch (Exception $e) {
            Log::error('Firebase: Failed to subscribe to topic', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Unsubscribe a device token from a topic
     */
    public function unsubscribeFromTopic(string $token, string $topic): bool
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = $this->httpClient->post(
                "{$this->getCoreApiUrl()}/iid:batchRemove",
                [
                    'headers' => [
                        'Authorization' => "Bearer {$accessToken}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'to' => "/topics/{$topic}",
                        'registration_tokens' => [$token],
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new Exception("Unsubscription failed with status code: {$statusCode}");
            }

            Log::info('Firebase: Device unsubscribed from topic', ['topic' => $topic]);
            return true;
        } catch (Exception $e) {
            Log::error('Firebase: Failed to unsubscribe from topic', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build FCM v1 message for device token
     */
    private function buildTokenMessage(string $token, array $data = [], ?string $type = null): array
    {
        return [
            'message' => [
                'token' => $token,
                ...$this->buildMessagePayload($data, $type),
            ],
        ];
    }

    /**
     * Build FCM v1 message for topic
     */
    private function buildTopicMessage(string $topic, array $data = [], ?string $type = null): array
    {
        return [
            'message' => [
                'topic' => $topic,
                ...$this->buildMessagePayload($data, $type),
            ],
        ];
    }

    /**
     * Build the payload part of the message (notification, data, android, apns)
     */
    private function buildMessagePayload(array $data = [], ?string $type = null): array
    {
        $payload = [];
        $title = $data['title'] ?? '';
        $body = $data['body'] ?? '';

        // Prepare custom data - ensure all values are strings
        $customData = array_filter($data, fn($key) => !in_array($key, ['title', 'body']), ARRAY_FILTER_USE_KEY);
        $customData = array_map(fn($value) => (string)$value, $customData);

        if ($type === 'silent') {
            // Silent notification - data only, no visible notification
            if (!empty($customData)) {
                $payload['data'] = $customData;
            }
            // For iOS silent notifications
            $payload['apns'] = [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'content-available' => 1,
                    ],
                ],
            ];
        } elseif ($type === 'android') {
            // Android notification
            $payload['notification'] = [
                'title' => $title,
                'body' => $body,
            ];
            $payload['data'] = $customData;
            $payload['android'] = [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'color' => '#203E78',
                ],
            ];
        } elseif ($type === 'ios') {
            // iOS notification
            $payload['notification'] = [
                'title' => $title,
                'body' => $body,
            ];
            $payload['data'] = $customData;
            $payload['apns'] = [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'sound' => 'default',
                        'content-available' => 0,
                    ],
                    'custom_data' => $customData,
                ],
            ];
        } else {
            // Default: send to both Android and iOS
            $payload['notification'] = [
                'title' => $title,
                'body' => $body,
            ];
            $payload['data'] = $customData;
            $payload['android'] = [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'color' => '#203E78',
                ],
            ];
            $payload['apns'] = [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'sound' => 'default',
                        'content-available' => 0,
                    ],
                    'custom_data' => $customData,
                ],
            ];
        }

        return $payload;
    }

    /**
     * Send the actual HTTP request to Firebase
     */
    private function sendMessage(string $accessToken, array $message): void
    {
        $url = "{$this->getCoreApiUrl()}/messages:send";

        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $message,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $responseBody = $response->getBody()->getContents();
                throw new Exception("Firebase API returned status {$statusCode}: {$responseBody}");
            }
        } catch (Exception $e) {
            throw new Exception('Firebase notification send failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the FCM v1 Core API URL
     */
    private function getCoreApiUrl(): string
    {
        return self::FCM_V1_API_URL . '/' . $this->projectId;
    }
}
