<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $provider;
    protected string $apiUrl;
    protected array $credentials;

    public function __construct()
    {
        $this->provider = config('whatsapp.provider');
        $this->setupProvider();
    }

    /**
     * Setup provider-specific configuration
     */
    private function setupProvider(): void
    {
        switch ($this->provider) {
            case 'meta':
                $this->apiUrl = 'https://graph.facebook.com/v17.0/' . config('whatsapp.phone_number_id');
                $this->credentials = [
                    'access_token' => config('whatsapp.access_token')
                ];
                break;

            case 'twilio':
                $this->apiUrl = 'https://api.twilio.com/2010-04-01/Accounts/' . config('whatsapp.account_sid');
                $this->credentials = [
                    'account_sid' => config('whatsapp.account_sid'),
                    'auth_token' => config('whatsapp.auth_token'),
                    'from' => 'whatsapp:' . config('whatsapp.from_number')
                ];
                break;

            case '360dialog':
                $this->apiUrl = config('whatsapp.api_url', 'https://waba.360dialog.io/v1');
                $this->credentials = [
                    'api_key' => config('whatsapp.api_key')
                ];
                break;
        }
    }

    /**
     * Send text message
     */
    public function sendMessage(string $to, string $message): bool
    {
        try {
            switch ($this->provider) {
                case 'meta':
                case '360dialog':
                    return $this->sendMetaMessage($to, $message);
                case 'twilio':
                    return $this->sendTwilioMessage($to, $message);
                default:
                    return false;
            }
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send message via Meta/360Dialog
     */
    private function sendMetaMessage(string $to, string $message): bool
    {
        $response = Http::withToken($this->credentials['access_token'])
            ->post("{$this->apiUrl}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ]);

        if (!$response->successful()) {
            Log::error('Meta message send failed', ['response' => $response->body()]);
            return false;
        }

        return true;
    }

    /**
     * Send message via Twilio
     */
    private function sendTwilioMessage(string $to, string $message): bool
    {
        $response = Http::withBasicAuth(
            $this->credentials['account_sid'],
            $this->credentials['auth_token']
        )->asForm()->post("{$this->apiUrl}/Messages.json", [
            'From' => $this->credentials['from'],
            'To' => 'whatsapp:' . $to,
            'Body' => $message
        ]);

        if (!$response->successful()) {
            Log::error('Twilio message send failed', ['response' => $response->body()]);
            return false;
        }

        return true;
    }

    /**
     * Send image message
     */
    public function sendImage(string $to, string $imageUrl, ?string $caption = null): bool
    {
        try {
            switch ($this->provider) {
                case 'meta':
                case '360dialog':
                    return $this->sendMetaImage($to, $imageUrl, $caption);
                case 'twilio':
                    return $this->sendTwilioImage($to, $imageUrl, $caption);
                default:
                    return false;
            }
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp image', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send image via Meta/360Dialog
     */
    private function sendMetaImage(string $to, string $imageUrl, ?string $caption): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => [
                'link' => $imageUrl
            ]
        ];

        if ($caption) {
            $payload['image']['caption'] = $caption;
        }

        $response = Http::withToken($this->credentials['access_token'])
            ->post("{$this->apiUrl}/messages", $payload);

        return $response->successful();
    }

    /**
     * Send image via Twilio
     */
    private function sendTwilioImage(string $to, string $imageUrl, ?string $caption): bool
    {
        $response = Http::withBasicAuth(
            $this->credentials['account_sid'],
            $this->credentials['auth_token']
        )->asForm()->post("{$this->apiUrl}/Messages.json", [
            'From' => $this->credentials['from'],
            'To' => 'whatsapp:' . $to,
            'MediaUrl' => $imageUrl,
            'Body' => $caption ?? ''
        ]);

        return $response->successful();
    }

    /**
     * Send interactive buttons
     */
    public function sendButtons(string $to, string $text, array $buttons): bool
    {
        if ($this->provider === 'meta' || $this->provider === '360dialog') {
            return $this->sendMetaButtons($to, $text, $buttons);
        }

        // Twilio doesn't support interactive buttons well, send as text fallback
        $buttonText = "\n\n";
        foreach ($buttons as $index => $button) {
            $buttonText .= ($index + 1) . ". " . $button['title'] . "\n";
        }

        return $this->sendMessage($to, $text . $buttonText);
    }

    /**
     * Send buttons via Meta
     */
    private function sendMetaButtons(string $to, string $text, array $buttons): bool
    {
        $formattedButtons = array_map(function ($button) {
            return [
                'type' => 'reply',
                'reply' => [
                    'id' => $button['id'],
                    'title' => $button['title']
                ]
            ];
        }, array_slice($buttons, 0, 3)); // Max 3 buttons

        $response = Http::withToken($this->credentials['access_token'])
            ->post("{$this->apiUrl}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => [
                        'text' => $text
                    ],
                    'action' => [
                        'buttons' => $formattedButtons
                    ]
                ]
            ]);

        return $response->successful();
    }

    /**
     * Generate Google Static Map URL
     */
    public function generateStaticMap(array $userLocation, array $pharmacyLocations): ?string
    {
        $apiKey = config('services.google_maps.key');
        
        if (!$apiKey) {
            return null;
        }

        $markers = [];
        
        // User location (blue marker)
        $markers[] = "color:blue|label:U|{$userLocation['latitude']},{$userLocation['longitude']}";

        // Pharmacy locations (red markers)
        foreach ($pharmacyLocations as $index => $location) {
            $label = $index + 1;
            $markers[] = "color:red|label:{$label}|{$location['latitude']},{$location['longitude']}";
        }

        $markersString = implode('&markers=', $markers);

        $url = "https://maps.googleapis.com/maps/api/staticmap?" .
            "size=600x400&" .
            "maptype=roadmap&" .
            "markers={$markersString}&" .
            "key={$apiKey}";

        return $url;
    }
}
