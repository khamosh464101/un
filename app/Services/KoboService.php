<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class KoboService
{
    protected $baseUrl;
    protected $token;
    protected $formId;

    public function __construct()
    {
        $this->baseUrl = config('services.kobo.base_url');
        $this->token = config('services.kobo.token'); // Prefer token over username/password
        $this->formId = config('services.kobo.form_id');
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
        ])->get("{$this->baseUrl}/assets/{$this->formId}/deployment/");
        return $response;
    }

    public function getFormSubmissions($formId = null)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $this->token,
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/assets/{$this->formId}/data/");
        return $response;
    }
}
