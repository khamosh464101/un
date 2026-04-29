<?php

use App\Services\GoogleTranslationService;

if (!function_exists('translateToPersian')) {
    /**
     * Translate text to Persian using Google Cloud Translation
     * 
     * @param string|null $text
     * @return string
     */
    function translateToPersian(?string $text): string
    {
        if (empty($text)) {
            return '';
        }
        
        try {
            $service = app(GoogleTranslationService::class);
            return $service->translate($text, 'fa', 'en');
        } catch (\Exception $e) {
            \Log::error('Translation helper failed', [
                'text' => $text,
                'error' => $e->getMessage()
            ]);
            return $text;
        }
    }
}
