<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessIncomingMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Webhook verification for WhatsApp
     * Used by Meta/Twilio to verify the webhook endpoint
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub.mode');
        $token = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');

        if ($mode === 'subscribe' && $token === config('whatsapp.verify_token')) {
            Log::info('WhatsApp webhook verified successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['error' => 'Verification failed'], 403);
    }

    /**
     * Handle incoming WhatsApp messages
     */
    public function webhook(Request $request)
    {
        // Verify signature based on provider
        if (!$this->verifySignature($request)) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all()
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $payload = $request->all();
        Log::info('WhatsApp webhook received', ['payload' => $payload]);

        // Extract message data based on provider format
        $messageData = $this->extractMessageData($payload);

        if (!$messageData) {
            Log::warning('Could not extract message data', ['payload' => $payload]);
            return response()->json(['status' => 'ignored'], 200);
        }

        // Dispatch job to process the message asynchronously
        ProcessIncomingMessage::dispatch($messageData);

        return response()->json(['status' => 'queued'], 200);
    }

    /**
     * Verify webhook signature based on provider
     */
    private function verifySignature(Request $request): bool
    {
        $provider = config('whatsapp.provider'); // 'twilio', 'meta', '360dialog'

        switch ($provider) {
            case 'twilio':
                return $this->verifyTwilioSignature($request);
            case 'meta':
                return $this->verifyMetaSignature($request);
            case '360dialog':
                return $this->verify360DialogSignature($request);
            default:
                // For development, allow without verification
                return config('app.env') === 'local' || true;
        }
    }

    /**
     * Verify Twilio signature
     */
    private function verifyTwilioSignature(Request $request): bool
    {
        $signature = $request->header('X-Twilio-Signature');
        if (!$signature) {
            return false;
        }

        $url = $request->fullUrl();
        $data = $request->all();
        
        ksort($data);
        $dataString = '';
        foreach ($data as $key => $value) {
            $dataString .= $key . $value;
        }

        $expectedSignature = base64_encode(
            hash_hmac('sha1', $url . $dataString, config('whatsapp.auth_token'), true)
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Meta (Facebook) signature
     */
    private function verifyMetaSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac(
            'sha256',
            $request->getContent(),
            config('whatsapp.app_secret'),
            false
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify 360Dialog signature
     */
    private function verify360DialogSignature(Request $request): bool
    {
        // 360Dialog uses similar approach to Meta
        return $this->verifyMetaSignature($request);
    }

    /**
     * Extract message data from webhook payload
     * Supports multiple providers: Meta, Twilio, 360Dialog
     */
    private function extractMessageData(array $payload): ?array
    {
        $provider = config('whatsapp.provider');

        try {
            switch ($provider) {
                case 'meta':
                case '360dialog':
                    return $this->extractMetaMessage($payload);
                case 'twilio':
                    return $this->extractTwilioMessage($payload);
                default:
                    return $this->extractGenericMessage($payload);
            }
        } catch (\Exception $e) {
            Log::error('Error extracting message data', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return null;
        }
    }

    /**
     * Extract message from Meta/360Dialog format
     */
    private function extractMetaMessage(array $payload): ?array
    {
        if (!isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
            return null;
        }

        $message = $payload['entry'][0]['changes'][0]['value']['messages'][0];
        $contact = $payload['entry'][0]['changes'][0]['value']['contacts'][0] ?? null;

        return [
            'message_id' => $message['id'],
            'from' => $message['from'],
            'timestamp' => $message['timestamp'],
            'type' => $message['type'], // text, image, audio, document, location
            'name' => $contact['profile']['name'] ?? null,
            'text' => $message['text']['body'] ?? null,
            'media_url' => $message['image']['id'] ?? $message['audio']['id'] ?? $message['document']['id'] ?? null,
            'media_type' => $message['type'],
            'mime_type' => $message['image']['mime_type'] ?? $message['audio']['mime_type'] ?? $message['document']['mime_type'] ?? null,
            'latitude' => $message['location']['latitude'] ?? null,
            'longitude' => $message['location']['longitude'] ?? null,
            'provider' => 'meta'
        ];
    }

    /**
     * Extract message from Twilio format
     */
    private function extractTwilioMessage(array $payload): ?array
    {
        return [
            'message_id' => $payload['MessageSid'] ?? null,
            'from' => str_replace('whatsapp:', '', $payload['From'] ?? ''),
            'timestamp' => now()->timestamp,
            'type' => $this->detectTwilioMessageType($payload),
            'name' => $payload['ProfileName'] ?? null,
            'text' => $payload['Body'] ?? null,
            'media_url' => $payload['MediaUrl0'] ?? null,
            'media_type' => $this->detectTwilioMessageType($payload),
            'mime_type' => $payload['MediaContentType0'] ?? null,
            'latitude' => $payload['Latitude'] ?? null,
            'longitude' => $payload['Longitude'] ?? null,
            'provider' => 'twilio'
        ];
    }

    /**
     * Detect Twilio message type
     */
    private function detectTwilioMessageType(array $payload): string
    {
        if (isset($payload['MediaUrl0'])) {
            $mimeType = $payload['MediaContentType0'] ?? '';
            if (str_contains($mimeType, 'image')) return 'image';
            if (str_contains($mimeType, 'audio')) return 'audio';
            if (str_contains($mimeType, 'pdf') || str_contains($mimeType, 'document')) return 'document';
            return 'media';
        }
        
        if (isset($payload['Latitude']) && isset($payload['Longitude'])) {
            return 'location';
        }

        return 'text';
    }

    /**
     * Extract message from generic format (fallback)
     */
    private function extractGenericMessage(array $payload): ?array
    {
        return [
            'message_id' => $payload['id'] ?? uniqid(),
            'from' => $payload['from'] ?? null,
            'timestamp' => $payload['timestamp'] ?? now()->timestamp,
            'type' => $payload['type'] ?? 'text',
            'name' => $payload['name'] ?? null,
            'text' => $payload['text'] ?? $payload['body'] ?? null,
            'media_url' => $payload['media_url'] ?? null,
            'media_type' => $payload['media_type'] ?? $payload['type'] ?? null,
            'mime_type' => $payload['mime_type'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'provider' => 'generic'
        ];
    }
}
