<?php

namespace App\Services;

use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TransliterationService
{
    protected $translate;
    protected $projectId;

    public function __construct()
    {
        // Try GOOGLE_APPLICATION_CREDENTIALS first (standard Google Cloud env var)
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
     * Transliterate English name to Persian/Dari
     * 
     * @param string $englishName
     * @return string
     */
    public function toPersian(string $englishName): string
    {
        if (empty($englishName)) {
            return '';
        }

        // Check cache first (to save API calls)
        $cacheKey = 'transliterate_' . md5($englishName);
        
        return Cache::remember($cacheKey, now()->addDays(30), function () use ($englishName) {
            try {
                // Create request for V3 API
                $request = new TranslateTextRequest();
                $request->setContents([$englishName]);
                $request->setTargetLanguageCode('fa'); // Persian/Farsi
                $request->setSourceLanguageCode('en');
                $request->setParent("projects/{$this->projectId}/locations/global");
                
                // Call the API
                $response = $this->translate->translateText($request);
                
                // Get the translations from response
                $translations = $response->getTranslations();
                if (count($translations) > 0) {
                    return $translations[0]->getTranslatedText();
                } else {
                    return $englishName;
                }
                
            } catch (\Exception $e) {
                Log::error('Transliteration failed', [
                    'name' => $englishName,
                    'error' => $e->getMessage()
                ]);
                
                // Return original if translation fails
                return $englishName;
            }
        });
    }

    /**
     * Transliterate multiple names at once (batch)
     * 
     * @param array $names
     * @return array
     */
    public function batchToPersian(array $names): array
    {
        $results = [];
        
        foreach ($names as $name) {
            $results[$name] = $this->toPersian($name);
        }
        
        return $results;
    }

    /**
     * Clear transliteration cache
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
}
