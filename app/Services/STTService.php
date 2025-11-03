<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class STTService
{
    /**
     * Transcribe audio to text using Speech-to-Text
     */
    public function transcribeAudio(string $mediaUrl, string $provider): ?string
    {
        try {
            // Download audio file
            $audioContent = $this->downloadMedia($mediaUrl, $provider);
            
            if (!$audioContent) {
                return null;
            }

            // Use OpenAI Whisper if available, otherwise Google Speech-to-Text
            if (config('services.openai.key')) {
                return $this->transcribeWithWhisper($audioContent);
            }

            if (config('services.google_speech.key')) {
                return $this->transcribeWithGoogleSpeech($audioContent);
            }

            Log::warning('No STT service configured');
            return null;

        } catch (\Exception $e) {
            Log::error('STT transcription failed', [
                'error' => $e->getMessage(),
                'media_url' => $mediaUrl
            ]);
            return null;
        }
    }

    /**
     * Download audio from WhatsApp provider
     */
    private function downloadMedia(string $mediaUrl, string $provider): ?string
    {
        try {
            if ($provider === 'meta' || $provider === '360dialog') {
                $accessToken = config('whatsapp.access_token');
                
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

                $mediaResponse = Http::withToken($accessToken)
                    ->get($downloadUrl);

                return $mediaResponse->successful() ? $mediaResponse->body() : null;

            } elseif ($provider === 'twilio') {
                $response = Http::withBasicAuth(
                    config('whatsapp.account_sid'),
                    config('whatsapp.auth_token')
                )->get($mediaUrl);

                return $response->successful() ? $response->body() : null;
            }

            $response = Http::get($mediaUrl);
            return $response->successful() ? $response->body() : null;

        } catch (\Exception $e) {
            Log::error('Audio download failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Transcribe using OpenAI Whisper API
     */
    private function transcribeWithWhisper(string $audioContent): ?string
    {
        try {
            // Save audio temporarily
            $tempPath = Storage::disk('local')->put('temp/audio_' . uniqid() . '.ogg', $audioContent);
            $fullPath = Storage::disk('local')->path($tempPath);

            $response = Http::withToken(config('services.openai.key'))
                ->attach('file', file_get_contents($fullPath), 'audio.ogg')
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'language' => 'ar', // Arabic, can auto-detect
                ]);

            // Clean up
            Storage::disk('local')->delete($tempPath);

            if (!$response->successful()) {
                Log::warning('Whisper API failed', ['response' => $response->body()]);
                return null;
            }

            $result = $response->json();
            return $result['text'] ?? null;

        } catch (\Exception $e) {
            Log::error('Whisper transcription failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Transcribe using Google Speech-to-Text API
     */
    private function transcribeWithGoogleSpeech(string $audioContent): ?string
    {
        try {
            $apiKey = config('services.google_speech.key');
            
            $response = Http::post("https://speech.googleapis.com/v1/speech:recognize?key={$apiKey}", [
                'config' => [
                    'encoding' => 'OGG_OPUS',
                    'sampleRateHertz' => 16000,
                    'languageCode' => 'ar-SA', // Arabic
                    'alternativeLanguageCodes' => ['en-US'], // English fallback
                ],
                'audio' => [
                    'content' => base64_encode($audioContent)
                ]
            ]);

            if (!$response->successful()) {
                Log::warning('Google Speech API failed', ['response' => $response->body()]);
                return null;
            }

            $result = $response->json();
            $transcript = $result['results'][0]['alternatives'][0]['transcript'] ?? null;

            return $transcript;

        } catch (\Exception $e) {
            Log::error('Google Speech transcription failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
