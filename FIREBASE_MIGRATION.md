# Firebase FCM HTTP v1 API Migration - Implementation Guide

## Overview

The Firebase notification system has been successfully refactored to use **FCM HTTP v1 API** with OAuth 2.0 authentication, replacing the deprecated Legacy API.

### What Changed

#### ❌ Removed (Legacy FCM API)
- Endpoint: `https://fcm.googleapis.com/fcm/send`
- Authentication: `Authorization: key=...` (API Key)
- Parameters: `registration_ids`, hardcoded `topic`
- Security: SSL verification disabled (`CURLOPT_SSL_VERIFYPEER = false`)
- Library: Raw cURL implementation

#### ✅ New Implementation (FCM HTTP v1 API)
- Endpoint: `https://fcm.googleapis.com/v1/projects/{project-id}/messages:send`
- Authentication: OAuth 2.0 with Firebase Service Account
- Parameters: Proper `token` or `topic` (mutually exclusive)
- Security: SSL verification enabled (secure)
- Library: Guzzle HTTP client
- Features: Access token caching, proper error logging, TypeScript-style payloads

---

## Architecture

### FirebaseService Class
**File**: `app/Services/Firebase/FirebaseService.php`

**Responsibilities**:
- OAuth 2.0 authentication with Firebase Service Account
- Generate and cache access tokens (58-minute cache)
- Build proper FCM v1 message payloads
- Send notifications to device tokens
- Send notifications to topics
- Manage topic subscriptions
- Handle errors and logging

**Key Methods**:
```php
// Send to device tokens
public function sendToTokens(array $tokens, array $data = [], ?string $type = null): array

// Send to topic
public function sendToTopic(string $topic, array $data = [], ?string $type = null): array

// Topic management
public function subscribeToTopic(string $token, string $topic): bool
public function unsubscribeFromTopic(string $token, string $topic): bool
```

### SendNotification Trait
**File**: `app/Traits/SendNotification.php`

**Public Methods**:
```php
// Device token notifications (existing, refactored)
public function notifyByFirebase(array $tokens, array $data = [], ?string $type = null): array

// Topic notifications (NEW)
public function notifyByFirebaseTopic(string $topic, array $data = [], ?string $type = null): array

// Topic subscription (NEW - optional backend usage)
public function subscribeDeviceToTopic(string $token, string $topic): bool

// Topic unsubscription (NEW - optional backend usage)
public function unsubscribeDeviceFromTopic(string $token, string $topic): bool
```

---

## Configuration

### Environment Variables

**`.env`** (actual values):
```env
FIREBASE_PROJECT_ID=tawsela-2ed55
FIREBASE_SERVICE_ACCOUNT_PATH=storage/firebase/tawsela-2ed55-firebase-adminsdk-fbsvc-4bade656fe.json
```

**`.env.example`** (placeholders - for developers):
```env
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_SERVICE_ACCOUNT_PATH=storage/firebase/firebase-adminsdk.json
```

### Firebase Service Account Setup

1. **Location**: `storage/firebase/` directory (in `.gitignore`)
2. **File**: `tawsela-2ed55-firebase-adminsdk-fbsvc-4bade656fe.json` ✓ Already configured
3. **Access**: Service Account credentials are loaded automatically from the path in `.env`
4. **Security**: 
   - Never commit the JSON file to Git
   - `.gitignore` includes `/storage/firebase/*.json`
   - Share credentials via secure channels only

---

## Usage Patterns

### 1. Send to Device Tokens (User-specific notifications)

**Use Case**: Order updates, private messages, personal notifications

```php
// Single token
$this->notifyByFirebase(
    ['device_token_abc123'],
    [
        'title' => 'Order Delivered',
        'body' => 'Your order has been delivered successfully',
        'order_id' => '12345',
        'action_type' => 'order-delivered',
    ],
    'android'
);

// Multiple tokens (Android)
$tokens = Token::where('device_type', 'android')
    ->whereIn('user_id', $driver_ids)
    ->pluck('token')
    ->toArray();

$result = $this->notifyByFirebase($tokens, [
    'title' => 'New Ride Request',
    'body' => 'You have a new ride request',
    'request_id' => '99999',
], 'android');

// Multiple tokens (iOS)
$tokens = Token::where('device_type', 'ios')
    ->whereIn('user_id', $user_ids)
    ->pluck('token')
    ->toArray();

$this->notifyByFirebase($tokens, [
    'title' => 'Welcome Bonus',
    'body' => 'Claim your welcome bonus now!',
    'bonus_amount' => '50',
], 'ios');
```

**Return Value**:
```php
[
    'success' => 5,  // Number of successfully sent notifications
    'failed' => [    // Array of failed tokens
        [
            'token' => 'invalid_token_xyz...',
            'error' => 'Invalid token format'
        ]
    ]
]
```

### 2. Send to Topics (Broadcast notifications)

**Use Case**: Promotions, announcements, broadcast notifications

```php
// Send to all users subscribed to 'promotions' topic
$this->notifyByFirebaseTopic(
    'promotions',
    [
        'title' => 'Special Offer: 50% Off',
        'body' => 'Get 50% discount on all rides this weekend!',
        'promotion_id' => '555',
        'action_type' => 'new-promotion',
    ],
    'android'
);

// Send silent notification to topic
$result = $this->notifyByFirebaseTopic(
    'maintenance',
    ['maintenance_scheduled' => '2024-08-15T02:00:00Z'],
    'silent'
);

if ($result['success']) {
    Log::info('Maintenance notification sent to all users');
} else {
    Log::error('Failed to send maintenance notification: ' . $result['error']);
}
```

**Return Value**:
```php
[
    'success' => true,
    // OR on failure:
    'success' => false,
    'error' => 'Firebase API returned status 400: ...'
]
```

### 3. Topic Subscriptions (Optional backend usage)

**Note**: Subscriptions are typically managed from the mobile client. Backend methods are available if needed.

```php
// Subscribe device to topic (backend)
$token = 'device_token_from_db';
$this->subscribeDeviceToTopic($token, 'all_users');
$this->subscribeDeviceToTopic($token, 'customers');

// Unsubscribe device from topic
$this->unsubscribeDeviceFromTopic($token, 'old_topic');
```

---

## Supported Notification Types

### 1. **Android** (`type = 'android'`)

Sends notification with Android-specific payload:
- High priority
- Default sound
- Brand color (#203E78)
- Title and body
- Custom data fields

```php
$this->notifyByFirebase($tokens, [
    'title' => 'New Message',
    'body' => 'You have a new message',
    'message_id' => '12345',
    'sender' => 'John Doe',
], 'android');
```

### 2. **iOS** (`type = 'ios'`)

Sends notification with APNs (Apple Push Notification) payload:
- APNs-specific headers
- Alert with title and body
- Default sound
- Custom data in payload
- Correct `content-available` handling for background updates

```php
$this->notifyByFirebase($tokens, [
    'title' => 'Special Offer',
    'body' => '30% off your next ride',
    'offer_code' => 'SUMMER30',
], 'ios');
```

### 3. **Silent** (`type = 'silent'`)

Data-only notifications without visible alert:
- **Android**: Data fields only, no notification
- **iOS**: Sets `content-available: 1` for background wake
- Use for sync notifications, background updates, or silent refreshes

```php
$this->notifyByFirebase($tokens, [
    'action' => 'refresh_balance',
    'new_balance' => '150.50',
    'currency' => 'EGP',
], 'silent');
```

### 4. **Auto** (`type = null` or omitted)

Default: Sends to both Android and iOS with appropriate payloads:
- Combines Android and APNs configurations
- Best for generic notifications

```php
$this->notifyByFirebase($tokens, [
    'title' => 'Account Update',
    'body' => 'Your profile was updated',
]);
```

---

## Data Payload Format

### Allowed Fields

```php
[
    // Standard notification fields (optional)
    'title' => 'Notification Title',
    'body' => 'Notification Body',
    
    // Custom data fields (any string values)
    'order_id' => '12345',
    'action_type' => 'order-status-update',
    'user_id' => '999',
    'amount' => '99.99',
    'timestamp' => '2024-07-25T10:30:00Z',
    'priority' => 'high',
    // ... any other custom fields
]
```

### Requirements

- All custom data values **must be strings** (FCM v1 requirement)
- Numbers should be converted: `'amount' => (string)$amount`
- Booleans should be: `'active' => $isActive ? 'true' : 'false'`
- Null values: Use empty string `''` or omit the field

### Automatic Conversion

The FirebaseService automatically converts data values to strings:
```php
$data = [
    'order_id' => 12345,      // Will be converted to '12345'
    'total' => 99.99,         // Will be converted to '99.99'
    'active' => true,         // Will be converted to 'true'
];
// No manual conversion needed!
```

---

## Error Handling

### Logging

All errors are automatically logged to Laravel's Log facade:

```php
// Enable logging to see Firebase errors:
// config/logging.php - ensure your channel captures info/error levels

// Log locations:
// - Daily: storage/logs/laravel-YYYY-MM-DD.log
// - Single: storage/logs/laravel.log
```

### Example Log Output

```
[2024-07-25 10:30:45] local.ERROR: Firebase: Failed to send to token {
  "token": "invalid_token_xyz123...",
  "error": "Invalid token format"
}

[2024-07-25 10:30:50] local.ERROR: Firebase: Failed to send to topic {
  "topic": "promotions",
  "error": "Firebase API returned status 400: Invalid registration token"
}
```

### Return Value Handling

Always check the return value:

```php
$result = $this->notifyByFirebase($tokens, $data, 'android');

// For device tokens:
if ($result['success'] > 0) {
    Log::info("Sent {$result['success']} notifications");
}

if (count($result['failed']) > 0) {
    Log::warning("Failed to send to " . count($result['failed']) . " devices", $result['failed']);
}

// For topics:
$result = $this->notifyByFirebaseTopic('promotions', $data);

if ($result['success']) {
    Log::info("Topic notification sent successfully");
} else {
    Log::error("Topic notification failed: " . $result['error']);
}
```

---

## Performance Considerations

### Access Token Caching

- Tokens are cached for **58 minutes** (expires in 60 minutes)
- Prevents unnecessary OAuth calls
- Cache driver: Whatever is configured in `.env` (`CACHE_DRIVER`)

### Multiple Token Sending

**Not batch send** (Firebase v1 doesn't support `registration_ids`):
```php
// ❌ DO NOT do this - it's inefficient:
foreach ($tokens as $token) {
    $this->notifyByFirebase([$token], $data);  // Authenticates each time!
}

// ✅ DO this instead:
$this->notifyByFirebase($tokens, $data);  // Authenticates once, loops internally
```

The trait handles authentication once and sends to multiple tokens efficiently.

---

## Testing

### 1. Test Device Token Notifications

```bash
# Connect to Laravel tinker
php artisan tinker

# Get a real device token from database
$token = Token::first();

# Send Android notification
$result = app(AuthController::class)->notifyByFirebase(
    [$token->token],
    [
        'title' => 'Test Notification',
        'body' => 'This is a test',
        'test' => 'true',
    ],
    'android'
);

# Check result
dd($result);
```

### 2. Test Topic Notifications

```php
# In tinker:
$result = app(AuthController::class)->notifyByFirebaseTopic(
    'test_topic',
    [
        'title' => 'Test Topic Notification',
        'body' => 'Testing topic functionality',
    ]
);

dd($result);
```

### 3. Check Logs

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log

# Or view full log
cat storage/logs/laravel-$(date +%Y-%m-%d).log
```

### 4. Firebase Console Verification

1. Go to [Firebase Console](https://console.firebase.google.com)
2. Select project: **tawsela-2ed55**
3. Navigate to **Messaging** → **Cloud Messaging**
4. View recent notification sends and delivery status

---

## Migration from Legacy API

### Existing Callers - No Changes Needed! ✅

All existing code continues to work without modification:

```php
// This still works exactly as before:
$this->notifyByFirebase([$token->token], $data, $token->device_type);

// These still work:
$this->notifyByFirebase($android_tokens, $data, 'android');
$this->notifyByFirebase($ios_tokens, $data, 'ios');
```

### Return Value Changed (Important!)

**Legacy API**:
```php
// Returned raw JSON from FCM:
return '{"success":1,"failure":0,"canonical_ids":0,...}'
```

**HTTP v1 API**:
```php
// Returns structured array:
return [
    'success' => 5,      // Number of successful sends
    'failed' => [        // Array of failures with error reasons
        ['token' => '...', 'error' => '...']
    ]
]
```

### Updating Callers (Optional but Recommended)

If you want to use the new return format for better error handling:

```php
// Before (legacy)
$result = $this->notifyByFirebase($tokens, $data, 'android');
// Was: JSON string, unused

// After (HTTP v1)
$result = $this->notifyByFirebase($tokens, $data, 'android');

if ($result['success'] > 0) {
    Log::info("Sent {$result['success']} notifications");
}

if (count($result['failed']) > 0) {
    // Handle failures
    foreach ($result['failed'] as $failure) {
        Log::warning("Failed token: {$failure['error']}");
    }
}
```

---

## Troubleshooting

### Issue: "Firebase Service Account credentials file not found"

**Solution**:
```bash
# Check file exists:
ls -la storage/firebase/

# Ensure .env has correct path:
cat .env | grep FIREBASE_SERVICE_ACCOUNT_PATH

# Verify path is relative to project root:
FIREBASE_SERVICE_ACCOUNT_PATH=storage/firebase/tawsela-2ed55-firebase-adminsdk-fbsvc-4bade656fe.json
```

### Issue: "FIREBASE_PROJECT_ID environment variable not set"

**Solution**:
```bash
# Check .env file:
cat .env | grep FIREBASE_PROJECT_ID

# Should be:
FIREBASE_PROJECT_ID=tawsela-2ed55

# If missing, add it:
echo "FIREBASE_PROJECT_ID=tawsela-2ed55" >> .env
```

### Issue: "Unauthorized" or authentication failures

**Solution**:
1. Verify Service Account JSON file is valid:
   ```bash
   php -r "json_decode(file_get_contents('storage/firebase/tawsela-2ed55-firebase-adminsdk-fbsvc-4bade656fe.json')) or die('Invalid JSON');"
   ```

2. Clear token cache:
   ```bash
   php artisan cache:clear
   ```

3. Check Firebase project ID matches:
   ```bash
   # In JSON file, verify "project_id" field matches .env FIREBASE_PROJECT_ID
   cat storage/firebase/tawsela-2ed55-firebase-adminsdk-fbsvc-4bade656fe.json | grep project_id
   ```

### Issue: "Invalid token format"

**Solution**:
- Device tokens must be valid Firebase tokens
- Check token format in database:
  ```php
  $token = Token::first();
  echo strlen($token->token); // Should be ~150+ characters
  ```

### Issue: Notifications not received on device

**Check**:
1. Device is subscribed to topic (if using topics)
2. Device has FCM library integrated (iOS/Android SDK)
3. App has notification permissions enabled on device
4. Check Firebase console for delivery status
5. View app logs for notification handling

---

## Security Best Practices

✅ **Implemented**:
- OAuth 2.0 authentication with Service Account
- SSL certificate verification enabled
- Service Account JSON in `.gitignore`
- Environment variables for sensitive data
- No hardcoded credentials
- Proper error logging without exposing secrets

✅ **For Your Team**:
- Never commit `storage/firebase/*.json` files
- Share Firebase Project ID only (it's public information)
- Share Service Account JSON securely (Slack, 1Password, etc.)
- Never share `.env` files - developers create their own from `.env.example`
- Rotate Service Account keys periodically

---

## Files Modified

| File | Change |
|------|--------|
| `app/Traits/SendNotification.php` | Refactored to use HTTP v1, added 3 new methods |
| `app/Services/Firebase/FirebaseService.php` | **NEW** - Core implementation |
| `.env` | Added `FIREBASE_PROJECT_ID`, `FIREBASE_SERVICE_ACCOUNT_PATH` |
| `.env.example` | Added Firebase config placeholders |
| `.gitignore` | Added `/storage/firebase/*.json` |
| `storage/firebase/tawsela-2ed55-firebase-adminsdk-fbsvc-4bade656fe.json` | **NEW** - Service Account (not committed) |

---

## Summary

✅ **Migration Complete**

Your Firebase notification system now uses the modern, secure FCM HTTP v1 API with OAuth 2.0 authentication. All existing code continues to work without changes, while offering new topic-based broadcasting capabilities and improved error handling.

**Key Benefits**:
- Modern, secure OAuth 2.0 authentication
- Support for both device tokens and topics
- Better error handling and logging
- SSL verification enabled
- No breaking changes for existing callers
- Efficient access token caching
- Compliance with Firebase best practices

**Next Steps**:
1. Test notifications in development
2. Monitor logs for any issues
3. Optionally enhance error handling in callers using new return format
4. Document topic naming conventions for your team
5. Train mobile team on topic subscription/unsubscription

---

**Questions?** Refer to the inline documentation in the code files or check the Firebase v1 API documentation.
