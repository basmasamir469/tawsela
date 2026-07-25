# Firebase HTTP v1 Migration - Quick Reference

## What Was Done

### Files Created
- ✅ `app/Services/Firebase/FirebaseService.php` - OAuth 2.0 + FCM v1 implementation
- ✅ `storage/firebase/tawsela-2ed55-firebase-adminsdk-fbsvc-4bade656fe.json` - Service Account credentials
- ✅ `FIREBASE_MIGRATION.md` - Complete documentation (this folder)

### Files Updated
- ✅ `app/Traits/SendNotification.php` - Refactored to use HTTP v1 (backward compatible)
- ✅ `.env` - Added `FIREBASE_PROJECT_ID` and `FIREBASE_SERVICE_ACCOUNT_PATH`
- ✅ `.env.example` - Added Firebase configuration with documentation
- ✅ `.gitignore` - Added `/storage/firebase/*.json` to prevent credential commits

---

## Key Changes Summary

| Aspect | Before (Legacy) | After (HTTP v1) |
|--------|-----------------|-----------------|
| **Endpoint** | `https://fcm.googleapis.com/fcm/send` | `https://fcm.googleapis.com/v1/projects/{id}/messages:send` |
| **Auth** | API Key: `Authorization: key=...` | OAuth 2.0: `Authorization: Bearer {token}` |
| **Lib** | Raw cURL | Guzzle HTTP Client |
| **SSL** | ❌ Disabled | ✅ Enabled |
| **Token** | Single request | Efficient loop per token |
| **Topics** | Hardcoded 'welcome-bonus' | Dynamic via new method |
| **Errors** | Silent failures | Logged + returned |

---

## Usage - No Changes Needed!

All existing code works without modification:

```php
// Still works as before (backward compatible)
$this->notifyByFirebase([$token], $data, 'android');
$this->notifyByFirebase($tokens, $data, 'ios');
$this->notifyByFirebase($tokens, $data, 'silent');
```

**Return value changed** (for better error handling):
```php
// Old: Raw JSON string (unused)
// New: Structured array
[
    'success' => 5,
    'failed' => [['token' => '...', 'error' => '...']]
]
```

---

## New Features

### Send Topic Notifications (Broadcast)

```php
$this->notifyByFirebaseTopic(
    'promotions',
    [
        'title' => 'Special Offer',
        'body' => 'Get 50% off!',
        'promo_id' => '123'
    ],
    'android'
);
```

### Topic Management (Optional Backend)

```php
// Devices typically manage subscriptions from mobile client
// But available for backend if needed:
$this->subscribeDeviceToTopic($token, 'all_users');
$this->unsubscribeDeviceFromTopic($token, 'old_topic');
```

---

## Configuration

### Already Configured ✅

- ✅ `FIREBASE_PROJECT_ID=tawsela-2ed55`
- ✅ `FIREBASE_SERVICE_ACCOUNT_PATH=storage/firebase/tawsela-2ed55-firebase-adminsdk-fbsvc-4bade656fe.json`
- ✅ Service Account JSON copied to `storage/firebase/`
- ✅ `.gitignore` updated to exclude credentials

**For new developers**:
```bash
# They get .env.example (no credentials)
cp .env.example .env

# They add the credentials path manually:
# FIREBASE_PROJECT_ID=tawsela-2ed55
# FIREBASE_SERVICE_ACCOUNT_PATH=storage/firebase/firebase-adminsdk.json
```

---

## Architecture

```
SendNotification Trait (app/Traits/SendNotification.php)
    ↓
FirebaseService (app/Services/Firebase/FirebaseService.php)
    ├─ OAuth 2.0 Token Generation (cached for 58 min)
    ├─ Device Token Notifications (loop per token)
    ├─ Topic Notifications (single request)
    └─ Topic Management (subscribe/unsubscribe)
    ↓
FCM HTTP v1 API (https://fcm.googleapis.com/v1/projects/...)
    ↓
Firebase Cloud Messaging
    ↓
Android & iOS Devices
```

---

## Notification Types

| Type | Use Case | Android | iOS | Notes |
|------|----------|---------|-----|-------|
| **android** | Android devices | ✅ Full | ✅ Compat | High priority, color #203E78 |
| **ios** | iOS devices | ✅ Compat | ✅ Full | APNs, correct content-available |
| **silent** | Background sync | ✅ Data only | ✅ Wake | No visible alert |
| **null/auto** | Both platforms | ✅ Yes | ✅ Yes | Default if no type specified |

---

## Error Handling

All errors are **logged automatically**:
```bash
tail -f storage/logs/laravel.log

# Watch for Firebase errors:
[local.ERROR]: Firebase: Failed to send to token {...}
[local.ERROR]: Firebase: Failed to get access token {...}
```

**Check Results**:
```php
$result = $this->notifyByFirebase($tokens, $data);

// Device tokens return:
[
    'success' => 5,      // Count of successful
    'failed' => [        // Array of failures
        ['token' => '...', 'error' => 'Invalid token']
    ]
]

// Topics return:
[
    'success' => true,   // OR false
    'error' => '...'     // Error message if failed
]
```

---

## Data Payload Requirements

✅ **What's Required**:
- `title` - Notification title (optional)
- `body` - Notification body (optional)

✅ **Custom Data** (all must be **strings**):
```php
[
    'title' => 'New Order',
    'body' => 'Order #123',
    'order_id' => '123',           // ✅ String
    'amount' => (string)99.99,     // ✅ Convert to string
    'urgent' => $isUrgent ? 'true' : 'false',  // ✅ String
]
```

**The trait handles conversion automatically** - but ensure clean data.

---

## Testing Checklist

```bash
# 1. Verify configuration
cat .env | grep FIREBASE

# 2. Check credentials file
ls -la storage/firebase/

# 3. Test in tinker
php artisan tinker

# Then:
$result = app(AuthController::class)->notifyByFirebase(
    [Token::first()->token],
    ['title' => 'Test', 'body' => 'Testing'],
    'android'
);
dd($result);

# 4. Monitor logs
tail -f storage/logs/laravel.log

# 5. Check Firebase console
# https://console.firebase.google.com → tawsela-2ed55 → Messaging
```

---

## Important Files

| File | Purpose |
|------|---------|
| [app/Services/Firebase/FirebaseService.php](app/Services/Firebase/FirebaseService.php) | Core OAuth 2.0 + FCM v1 implementation |
| [app/Traits/SendNotification.php](app/Traits/SendNotification.php) | Public API (trait methods) |
| [.env](.env) | Firebase configuration (credentials path) |
| [.env.example](.env.example) | Template for new developers |
| [storage/firebase/](storage/firebase/) | Service Account credentials (.gitignored) |
| [FIREBASE_MIGRATION.md](FIREBASE_MIGRATION.md) | Full documentation |

---

## Current Usages (All Still Work ✅)

- `app/Http/Controllers/Api/AuthController.php:61` - Send welcome bonus
- `app/Http/Controllers/Api/Admin/PromotionController.php:60-72` - Send promotions
- `app/Http/Controllers/Api/Driver/OrderController.php:61,102,239` - Driver notifications
- `app/Http/Controllers/Api/User/OrderController.php:139,151,185` - Customer notifications
- `app/Jobs/SendLicenseAlert.php:47,52` - License alerts

**No code changes needed in these files** - they continue to work with the new implementation.

---

## Security

✅ **Implemented**:
- OAuth 2.0 (no API keys in headers)
- SSL certificate verification enabled
- Service Account JSON in `.gitignore`
- Credentials via `.env` (not committed)
- Error logging without exposing secrets
- Access token caching (prevents token abuse)

✅ **Your Team Should**:
1. Never commit `storage/firebase/*.json`
2. Share Service Account JSON securely
3. Never share `.env` - devs use `.env.example`
4. Monitor logs for OAuth errors
5. Rotate Service Account keys periodically

---

## Next Steps

1. ✅ **Review** - Read [FIREBASE_MIGRATION.md](FIREBASE_MIGRATION.md) for complete details
2. ✅ **Test** - Send test notifications in development
3. ✅ **Monitor** - Watch logs for errors: `tail -f storage/logs/laravel.log`
4. ✅ **Deploy** - Push to production (no app changes needed)
5. 🔄 **Optionally** - Use new topic features for broadcast notifications
6. 📚 **Document** - Share topic naming conventions with your team

---

## Questions?

Refer to the complete documentation in `FIREBASE_MIGRATION.md` or Firebase v1 API docs:
- [Firebase Cloud Messaging HTTP v1 API](https://firebase.google.com/docs/cloud-messaging/migrate-v1)
- [Firebase Admin SDK](https://firebase.google.com/docs/admin/setup)
