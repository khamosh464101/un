<?php

namespace App\Services;

use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;
use Google\Cloud\Translate\V3\TranslateTextResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoogleTranslationService
{
    protected $translate;
    protected $projectId;

    public function __construct()
    {
        // Try GOOGLE_APPLICATION_CREDENTIALS first (standard Google Cloud env var)
        $keyFilePath = storage_path('app/public/settings/google-storage-service-account.json');
        
        $keyFilePath = env('GOOGLE_APPLICATION_CREDENTIALS');
        // Fall back to GOOGLE_CLOUD_KEY_FILE
        if (empty($keyFilePath) || !file_exists($keyFilePath)) {
            $keyFilePath = env('GOOGLE_CLOUD_KEY_FILE');
            
            // Convert relative path to absolute
            if (!empty($keyFilePath) && !str_starts_with($keyFilePath, '/')) {
                $keyFilePath = base_path($keyFilePath);
            }
        }
        
        if (empty($keyFilePath) || !file_exists($keyFilePath)) {
            throw new \Exception("Google Cloud key file not found. Checked: " . 
                env('GOOGLE_APPLICATION_CREDENTIALS') . " and " . 
                env('GOOGLE_CLOUD_KEY_FILE'));
        }

        // Read the key file content
        $keyFileContent = file_get_contents($keyFilePath);
        if ($keyFileContent === false) {
            throw new \Exception("Failed to read Google Cloud key file at: " . $keyFilePath);
        }
        
        $keyFileData = json_decode($keyFileContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in Google Cloud key file: " . json_last_error_msg());
        }
        
        $this->projectId = env('GOOGLE_CLOUD_PROJECT_ID', $keyFileData['project_id'] ?? '');
        
        $this->translate = new TranslationServiceClient([
            'credentials' => $keyFileData
        ]);
    }

    /**
     * Translate English name to Persian/Dari
     * 
     * @param string|null $text
     * @param string $targetLanguage
     * @param string $sourceLanguage
     * @return string
     */
    public function translate(?string $text, string $targetLanguage = 'fa', string $sourceLanguage = 'en'): string
    {
        if (empty($text)) {
            return '';
        }

        // Check if already in Persian/Dari (contains Persian characters)
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return $text;
        }

        // Check cache first (to save API calls and costs)
        $cacheKey = 'google_translate_' . md5($text . '_' . $targetLanguage);
        
        return Cache::remember($cacheKey, now()->addMonths(6), function () use ($text, $targetLanguage, $sourceLanguage) {
            try {
                // Create request for V3 API
                $request = new TranslateTextRequest();
                $request->setContents([$text]);
                $request->setTargetLanguageCode($targetLanguage);
                $request->setSourceLanguageCode($sourceLanguage);
                $request->setParent("projects/{$this->projectId}/locations/global");
                
                // Call the API
                $response = $this->translate->translateText($request);
                
                // Get the translations from response
                $translations = $response->getTranslations();
                if (count($translations) > 0) {
                    $translated = $translations[0]->getTranslatedText();
                } else {
                    $translated = $text;
                }
                
                Log::info('Google Translation', [
                    'original' => $text,
                    'translated' => $translated,
                    'target' => $targetLanguage
                ]);

                return $translated;
                
            } catch (\Exception $e) {
                Log::error('Google Translation failed', [
                    'text' => $text,
                    'error' => $e->getMessage()
                ]);
                
                // Return original if translation fails
                return $text;
            }
        });
    }

    /**
     * Translate multiple texts at once (batch)
     * More efficient for multiple translations
     * 
     * @param array $texts
     * @param string $targetLanguage
     * @string $sourceLanguage
     * @return array
     */
    public function batchTranslate(array $texts, string $targetLanguage = 'fa', string $sourceLanguage = 'en'): array
    {
        $results = [];
        $textsToTranslate = [];
        $cachedResults = [];
        
        // Check cache for each text
        foreach ($texts as $key => $text) {
            if (empty($text)) {
                $results[$key] = '';
                continue;
            }
            
            // Check if already in Persian
            if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
                $results[$key] = $text;
                continue;
            }
            
            $cacheKey = 'google_translate_' . md5($text . '_' . $targetLanguage);
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                $results[$key] = $cached;
            } else {
                $textsToTranslate[$key] = $text;
            }
        }
        
        // Translate remaining texts
        if (!empty($textsToTranslate)) {
            try {
                // Create request for V3 API
                $request = new TranslateTextRequest();
                $request->setContents(array_values($textsToTranslate));
                $request->setTargetLanguageCode($targetLanguage);
                $request->setSourceLanguageCode($sourceLanguage);
                $request->setParent("projects/{$this->projectId}/locations/global");
                
                // Call the API
                $response = $this->translate->translateText($request);
                
                // Get the translations from response
                $translations = $response->getTranslations();
                
                $i = 0;
                foreach ($textsToTranslate as $key => $originalText) {
                    if ($i < count($translations)) {
                        $translated = $translations[$i]->getTranslatedText();
                    } else {
                        $translated = $originalText;
                    }
                    
                    $results[$key] = $translated;
                    
                    // Cache the result
                    $cacheKey = 'google_translate_' . md5($originalText . '_' . $targetLanguage);
                    Cache::put($cacheKey, $translated, now()->addMonths(6));
                    
                    $i++;
                }
                
            } catch (\Exception $e) {
                Log::error('Google Batch Translation failed', [
                    'texts' => $textsToTranslate,
                    'error' => $e->getMessage()
                ]);
                
                // Return originals if translation fails
                foreach ($textsToTranslate as $key => $text) {
                    $results[$key] = $text;
                }
            }
        }
        
        return $results;
    }

    /**
     * Clear translation cache
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
}
