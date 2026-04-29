# Google Cloud Translation API Setup Guide

## ✅ What Has Been Done

1. **Installed Google Cloud Translation Package**
   - Package: `google/cloud` (meta-package)
   - Includes: `google/cloud-translate` V3 API

2. **Updated Translation Service to Use V3 API**
   - File: `app/Services/GoogleTranslationService.php`
   - Updated from V2 to V3 API
   - Features: Translation with caching, batch translation

3. **Updated Transliteration Service to Use V3 API**
   - File: `app/Services/TransliterationService.php`
   - Updated from V2 to V3 API

4. **Created Helper Function**
   - File: `app/Helpers/TranslationHelper.php`
   - Function: `translateToPersian($text)`

5. **Updated Templates**
   - `resources/views/pdf/kunduz_template.blade.php`
   - `resources/views/pdf/kunduz_local_template.blade.php`
   - Fields: `inter_name`, `inter_father_name`, `inter_grandfather_name`

---

## 🔧 What You Need To Do

### Step 1: Enable Google Cloud Translation API

1. **Go to Google Cloud Console**
   - Visit: https://console.cloud.google.com/

2. **Select Your Project**
   - Project ID: `un-project-462414` (from your .env)

3. **Enable Translation API**
   - Go to: **APIs & Services** → **Library**
   - Search for: **Cloud Translation API**
   - Click **ENABLE**

### Step 2: Verify Service Account Permissions

Your service account needs the **Cloud Translation API User** role.

1. **Go to IAM & Admin** → **Service Accounts**
   - Find your service account (the one in your key file)

2. **Grant Translation Permission**
   - Click on the service account
   - Click **PERMISSIONS** tab
   - Click **GRANT ACCESS**
   - Add role: **Cloud Translation API User**
   - Click **SAVE**

### Step 3: Verify Your .env Configuration

Your `.env` file should have:

```env
GOOGLE_CLOUD_PROJECT_ID=un-project-462414
GOOGLE_CLOUD_KEY_FILE=storage/app/public/settings/google-storage-service-account.json
```

**Make sure the key file exists at that path!**

Check with:
```bash
ls -la storage/app/public/settings/google-storage-service-account.json
```

---

## 🧪 Testing

### Test 1: Check if API is enabled

Visit in your browser:
```
http://127.0.0.1:8000/test-google-translation
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Google Translation Test",
  "results": {
    "Aman": "امان",
    "Ahmad": "احمد",
    "Mohammad": "محمد",
    "Gul Andam": "گل اندام",
    "Abdullah": "عبدالله"
  },
  "service_exists": true,
  "helper_exists": true
}
```

**If you get an error:**
- Check if Translation API is enabled
- Check if service account has permissions
- Check if key file path is correct

### Test 2: Test in Tinker

```bash
php artisan tinker
```

Then run:
```php
$service = new \App\Services\GoogleTranslationService();
echo $service->translate('Gul Andam');
// Should output: گل اندام
```

### Test 3: Test Helper Function

```bash
php artisan tinker
```

Then run:
```php
echo translateToPersian('Gul Andam');
// Should output: گل اندام
```

---

## 💰 Pricing

**Google Cloud Translation API Pricing:**
- **$20 per 1 million characters**
- Characters are counted, not words
- Example: "Gul Andam" = 9 characters

**Cost Estimation:**
- 1,000 names (avg 15 chars each) = 15,000 characters = **$0.30**
- 10,000 names = **$3.00**
- 100,000 names = **$30.00**

**Caching:**
- Translations are cached for 6 months
- Same name won't be translated twice
- Saves API calls and costs

---

## 📝 How It Works

### In Blade Templates:

**Before:**
```blade
{{ $submission->interviewwee->inter_name }}
```

**After:**
```blade
{{ translateToPersian($submission->interviewwee->inter_name) }}
```

### What Happens:

1. **Check if already Persian** - If text contains Persian characters, return as-is
2. **Check cache** - If translated before, return cached result
3. **Call Google API** - Translate English → Persian
4. **Cache result** - Store for 6 months
5. **Return translation**

---

## 🔍 Troubleshooting

### Error: "Class 'Google\Cloud\Translate\V2\TranslateClient' not found"

**Solution:**
This error occurs because we've migrated from the deprecated V2 API to V3 API. The code has been updated to use `TranslationServiceClient` from V3. Make sure:
1. The `google/cloud` package is installed (it includes V3 API)
2. You've run `composer dump-autoload`
3. The updated `GoogleTranslationService.php` and `TransliterationService.php` files are in place

### Error: "Google Cloud key file not found"

**Solution:**
```bash
# Check if file exists
ls -la storage/app/public/settings/google-storage-service-account.json

# If not, check your .env path
cat .env | grep GOOGLE_CLOUD_KEY_FILE
```

### Error: "Permission denied" or "API not enabled"

**Solution:**
1. Enable Translation API in Google Cloud Console
2. Add **Cloud Translation API User** role to service account

### Error: "Quota exceeded"

**Solution:**
1. Check your Google Cloud billing
2. Increase quota limits in Google Cloud Console

### Error: "Call to undefined method translateText()"

**Solution:**
The V3 API uses `translateText` method (not `translate` like V2). Make sure you're using the updated service files.

### Translations not showing in PDF

**Solution:**
```bash
# Clear all caches
php artisan view:clear
php artisan cache:clear
php artisan config:clear

# Regenerate autoload
composer dump-autoload
```

---

## 🎯 Usage Examples

### Single Translation:
```php
$service = new \App\Services\GoogleTranslationService();
$persian = $service->translate('Gul Andam');
// Result: گل اندام
```

### Batch Translation (More Efficient):
```php
$service = new \App\Services\GoogleTranslationService();
$names = ['Aman', 'Ahmad', 'Mohammad'];
$persianNames = $service->batchTranslate($names);
// Result: ['امان', 'احمد', 'محمد']
```

### Using Helper Function:
```php
$persian = translateToPersian('Gul Andam');
// Result: گل اندام
```

### Transliteration Service:
```php
$service = new \App\Services\TransliterationService();
$persian = $service->toPersian('Gul Andam');
// Result: گل اندام
```

---

## 📊 Monitoring

### Check Translation Logs:
```bash
tail -f storage/logs/laravel.log | grep "Google Translation"
```

### Clear Translation Cache:
```bash
php artisan tinker
```
```php
$service = new \App\Services\GoogleTranslationService();
$service->clearCache();
```

---

## ✅ Checklist

- [ ] Google Cloud Translation API enabled
- [ ] Service account has **Cloud Translation API User** role
- [ ] Key file exists at correct path
- [ ] Test URL returns successful translations
- [ ] Tinker test works
- [ ] Helper function works
- [ ] PDF generation includes Persian names
- [ ] Billing is set up in Google Cloud

---

## 🆘 Need Help?

If you encounter issues:

1. **Check the test URL first**: `http://127.0.0.1:8000/test-google-translation`
2. **Check Laravel logs**: `storage/logs/laravel.log`
3. **Verify Google Cloud Console**: API enabled + permissions granted
4. **Test with tinker**: Isolate the issue

---

## 📚 Additional Resources

- [Google Cloud Translation API Docs](https://cloud.google.com/translate/docs)
- [Pricing Calculator](https://cloud.google.com/products/calculator)
- [PHP Client Library](https://github.com/googleapis/google-cloud-php-translate)

