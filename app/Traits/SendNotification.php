<?php

namespace App\Traits;

use App\Services\Firebase\FirebaseService;
use Illuminate\Support\Facades\Log;

trait SendNotification
{
    /**
     * Send notification to device tokens using Firebase Cloud Messaging HTTP v1 API
     * 
     * Supports sending to one or multiple device tokens with platform-specific payloads.
     * Uses OAuth 2.0 authentication with Firebase Service Account credentials.
     * 
     * @param array $tokens Array of device tokens to send to
     * @param array $data Notification data (title, body, and custom data fields)
     * @param string|null $type Notification type: 'android', 'ios', 'silent' (null = auto)
     * @return array Results with success count and failed array
     * 
     * @example
     * $this->notifyByFirebase(
     *     ['device_token_1', 'device_token_2'],
     *     [
     *         'title' => 'Order Update',
     *         'body' => 'Your order has been delivered',
     *         'order_id' => '12345',
     *         'action_type' => 'order-delivered'
     *     ],
     *     'android'
     * );
     */
    public function notifyByFirebase(array $tokens, array $data = [], ?string $type = null): array
    {
        try {
            $firebaseService = new FirebaseService();
            return $firebaseService->sendToTokens($tokens, $data, $type);
        } catch (\Exception $e) {
            Log::error('Firebase notification error', [
                'error' => $e->getMessage(),
                'tokens_count' => count($tokens),
                'type' => $type,
            ]);

            return [
                'success' => 0,
                'failed' => array_map(
                    fn($token) => ['token' => $token, 'error' => $e->getMessage()],
                    $tokens
                ),
            ];
        }
    }

    /**
     * Send notification to a Firebase topic using FCM HTTP v1 API
     * 
     * Use this for broadcast/group notifications to all devices subscribed to a topic.
     * Topics are useful for sending notifications to groups of users (e.g., 'all_users', 'drivers', 'customers').
     * 
     * @param string $topic Topic name to send to
     * @param array $data Notification data (title, body, and custom data fields)
     * @param string|null $type Notification type: 'android', 'ios', 'silent' (null = auto)
     * @return array Result with success boolean and error message if failed
     * 
     * @example
     * $this->notifyByFirebaseTopic(
     *     'new_promotions',
     *     [
     *         'title' => 'Special Offer',
     *         'body' => 'Get 50% off on all rides',
     *         'promotion_id' => '123',
     *         'action_type' => 'new-promotion'
     *     ],
     *     'android'
     * );
     */
    public function notifyByFirebaseTopic(string $topic, array $data = [], ?string $type = null): array
    {
        try {
            $firebaseService = new FirebaseService();
            return $firebaseService->sendToTopic($topic, $data, $type);
        } catch (\Exception $e) {
            Log::error('Firebase topic notification error', [
                'error' => $e->getMessage(),
                'topic' => $topic,
                'type' => $type,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Subscribe a device token to a Firebase topic
     * 
     * Allows a device to receive broadcast notifications sent to a specific topic.
     * Typically called from the mobile client, but can be called from the backend if needed.
     * 
     * @param string $token Device token to subscribe
     * @param string $topic Topic name
     * @return bool True if successful, false otherwise
     * 
     * @example
     * $this->subscribeDeviceToTopic('device_token_123', 'all_users');
     */
    public function subscribeDeviceToTopic(string $token, string $topic): bool
    {
        try {
            $firebaseService = new FirebaseService();
            return $firebaseService->subscribeToTopic($token, $topic);
        } catch (\Exception $e) {
            Log::error('Firebase topic subscription error', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);
            return false;
        }
    }

    /**
     * Unsubscribe a device token from a Firebase topic
     * 
     * Prevents a device from receiving further broadcasts to a specific topic.
     * Typically called from the mobile client, but can be called from the backend if needed.
     * 
     * @param string $token Device token to unsubscribe
     * @param string $topic Topic name
     * @return bool True if successful, false otherwise
     * 
     * @example
     * $this->unsubscribeDeviceFromTopic('device_token_123', 'all_users');
     */
    public function unsubscribeDeviceFromTopic(string $token, string $topic): bool
    {
        try {
            $firebaseService = new FirebaseService();
            return $firebaseService->unsubscribeFromTopic($token, $topic);
        } catch (\Exception $e) {
            Log::error('Firebase topic unsubscription error', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);
            return false;
        }
    }
}

