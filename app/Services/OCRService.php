<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OCRService
{
    /**
     * Extract text from image or PDF using OCR
     */
    public function extractText(string $mediaUrl, string $provider): ?string
    {
        try {
            // Download media file
            $mediaContent = $this->downloadMedia($mediaUrl, $provider);
            
            if (!$mediaContent) {
                return null;
            }

            // Use Google Vision API if available, otherwise Tesseract
            if (config('services.google_vision.key')) {
                return $this->extractWithGoogleVision($mediaContent);
            }

            return $this->extractWithTesseract($mediaContent);

        } catch (\Exception $e) {
            Log::error('OCR extraction failed', [
                'error' => $e->getMessage(),
                'media_url' => $mediaUrl
            ]);
            return null;
        }
    }

    /**
     * Download media from WhatsApp provider
     */
    private function downloadMedia(string $mediaUrl, string $provider): ?string
    {
        try {
            if ($provider === 'meta' || $provider === '360dialog') {
                // For Meta, mediaUrl is actually a media ID
                $accessToken = config('whatsapp.access_token');
                
                // Get media URL
                $response = Http::withToken($accessToken)
                    ->get("https://graph.facebook.com/v17.0/{$mediaUrl}");

                if (!$response->successful()) {
                    return null;
                }

                $mediaInfo = $response->json();
                $downloadUrl = $mediaInfo['url'] ?? null;

                if (!$downloadUrl) {
                    return null;
                }

                // Download media content
                $mediaResponse = Http::withToken($accessToken)
                    ->get($downloadUrl);

                return $mediaResponse->successful() ? $mediaResponse->body() : null;

            } elseif ($provider === 'twilio') {
                // Twilio provides direct media URL
                $response = Http::withBasicAuth(
                    config('whatsapp.account_sid'),
                    config('whatsapp.auth_token')
                )->get($mediaUrl);

                return $response->successful() ? $response->body() : null;
            }

            // Generic URL download
            $response = Http::get($mediaUrl);
            return $response->successful() ? $response->body() : null;

        } catch (\Exception $e) {
            Log::error('Media download failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract text using Google Vision API
     */
    private function extractWithGoogleVision(string $imageContent): ?string
    {
        try {
            $apiKey = config('services.google_vision.key');
            
            $response = Http::post("https://vision.googleapis.com/v1/images:annotate?key={$apiKey}", [
                'requests' => [
                    [
                        'image' => [
                            'content' => base64_encode($imageContent)
                        ],
                        'features' => [
                            [
                                'type' => 'TEXT_DETECTION',
                                'maxResults' => 1
                            ]
                        ]
                    ]
                ]
            ]);

            if (!$response->successful()) {
                Log::warning('Google Vision API failed', ['response' => $response->body()]);
                return null;
            }

            $result = $response->json();
            $text = $result['responses'][0]['textAnnotations'][0]['description'] ?? null;

            return $text;

        } catch (\Exception $e) {
            Log::error('Google Vision extraction failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract text using Tesseract OCR (fallback)
     */
    private function extractWithTesseract(string $imageContent): ?string
    {
        try {
            // Save image temporarily
            $tempPath = Storage::disk('local')->put('temp/ocr_' . uniqid() . '.jpg', $imageContent);
            $fullPath = Storage::disk('local')->path($tempPath);

            // Run Tesseract (requires tesseract to be installed on server)
            // Supports Arabic and English
            $command = "tesseract {$fullPath} stdout -l ara+eng 2>&1";
            $output = shell_exec($command);

            // Clean up
            Storage::disk('local')->delete($tempPath);

            return $output ?: null;

        } catch (\Exception $e) {
            Log::error('Tesseract extraction failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
