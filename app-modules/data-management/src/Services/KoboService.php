<?php
namespace Modules\DataManagement\Services;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KoboService
{
    protected $baseUrl;
    protected $token;
    protected $formId;
    protected $copyFormId;

    public function __construct()
    {
        $this->baseUrl = config('services.kobo.base_url');
        $this->token = config('services.kobo.token'); // Prefer token over username/password
        $this->formId = config('services.kobo.form_id');
        $this->copyFormId = config('services.kobo.copy_form_id');
    }

    public function getForms()
    {



            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->token,
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/assets");
        
            // Optionally debug
            \Log::info('Status: ' . $response->status());
            \Log::info('Body: ' . $response->body());
        
            return $response->json();
    }

    public function getFormDetails($formId = null)
    {
        //  return $this->formId;
        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $this->token,
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/assets/{$this->copyFormId}/deployment/");
        return $response;
    }

    public function getFormSubmissions($url = null)
    {
        $formId = $this->formId;

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $this->token,
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/assets/{$formId}/data?limit=10&offset=10");

        return $response;
    }

    public function getSubmission($submissionId) {
        $formId = $this->formId;
        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $this->token,
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/assets/{$formId}/data/{$submissionId}/");

        return $response->json();
        
    }

        

    public function downloadAttachment(array $attachment, string $directory = 'kobo-attachments'): ?string
    {
        $url = $attachment['download_url'];
        $filename = basename($attachment['filename']); // Extract filename from full path
        $uuidPrefix = $attachment['instance']; //Str::uuid();
        $finalName = "$uuidPrefix-$filename"; // avoid name collisions

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->token,
                'Accept' => 'application/json',
            ])->get($url); // Add auth if needed

            if ($response->successful()) {
                Storage::disk('public')->put("$directory/$finalName", $response->body());
                return "$directory/$finalName"; // return relative path to use later
            } else {
                logger()->warning("Failed to download attachment from: $url");
            }
        } catch (\Exception $e) {
            logger()->error("Attachment download failed: " . $e->getMessage());
        }

        return null;
    }
}
