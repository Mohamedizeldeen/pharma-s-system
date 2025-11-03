<?php

namespace App\Jobs;

use App\Services\ExtractionService;
use App\Services\OCRService;
use App\Services\STTService;
use App\Services\SearchService;
use App\Services\DistanceService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected array $messageData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $messageData)
    {
        $this->messageData = $messageData;
    }

    /**
     * Execute the job.
     */
    public function handle(
        OCRService $ocrService,
        STTService $sttService,
        ExtractionService $extractionService,
        SearchService $searchService,
        DistanceService $distanceService,
        WhatsAppService $whatsAppService
    ): void {
        try {
            Log::info('Processing incoming message', ['message_id' => $this->messageData['message_id']]);

            // Step 1: Extract text based on message type
            $extractedText = $this->extractText($ocrService, $sttService);

            if (!$extractedText) {
                $this->sendErrorResponse($whatsAppService, 'لم أتمكن من فهم الرسالة. يرجى إرسال نص واضح أو صورة جيدة.');
                return;
            }

            Log::info('Text extracted', ['text' => $extractedText]);

            // Step 2: Extract medicine name from text
            $medicineName = $extractionService->extractMedicineName($extractedText);

            if (!$medicineName) {
                $this->sendErrorResponse($whatsAppService, 'لم أتمكن من التعرف على اسم الدواء. يرجى إعادة المحاولة بشكل أوضح.');
                return;
            }

            Log::info('Medicine name extracted', ['medicine' => $medicineName]);

            // Step 3: Get user location
            $userLocation = $this->getUserLocation();

            if (!$userLocation) {
                // Request location from user
                $whatsAppService->sendMessage(
                    $this->messageData['from'],
                    'يرجى مشاركة موقعك الحالي للعثور على أقرب الصيدليات.'
                );
                return;
            }

            // Step 4: Search for medicine in database
            $searchResults = $searchService->searchMedicine($medicineName, $userLocation);

            if ($searchResults->isEmpty()) {
                $this->sendErrorResponse(
                    $whatsAppService,
                    "عذراً، لم أجد الدواء \"{$medicineName}\" في الصيدليات القريبة منك. يرجى التحقق من الاسم والمحاولة مرة أخرى."
                );
                return;
            }

            Log::info('Search results found', ['count' => $searchResults->count()]);

            // Step 5: Calculate distances and sort
            $resultsWithDistance = $distanceService->calculateDistances(
                $searchResults,
                $userLocation['latitude'],
                $userLocation['longitude']
            );

            // Step 6: Send reply with results
            $this->sendSuccessResponse($whatsAppService, $medicineName, $resultsWithDistance, $userLocation);

            Log::info('Message processed successfully', ['message_id' => $this->messageData['message_id']]);

        } catch (\Exception $e) {
            Log::error('Error processing message', [
                'message_id' => $this->messageData['message_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->sendErrorResponse(
                app(WhatsAppService::class),
                'حدث خطأ في معالجة طلبك. يرجى المحاولة لاحقاً.'
            );

            throw $e;
        }
    }

    /**
     * Extract text from message based on type
     */
    private function extractText(OCRService $ocrService, STTService $sttService): ?string
    {
        $type = $this->messageData['type'];

        switch ($type) {
            case 'text':
                return $this->messageData['text'];

            case 'image':
            case 'document':
                if (!$this->messageData['media_url']) {
                    return null;
                }
                return $ocrService->extractText($this->messageData['media_url'], $this->messageData['provider']);

            case 'audio':
                if (!$this->messageData['media_url']) {
                    return null;
                }
                return $sttService->transcribeAudio($this->messageData['media_url'], $this->messageData['provider']);

            default:
                return null;
        }
    }

    /**
     * Get user location from message or database
     */
    private function getUserLocation(): ?array
    {
        // If location shared in message
        if ($this->messageData['latitude'] && $this->messageData['longitude']) {
            return [
                'latitude' => $this->messageData['latitude'],
                'longitude' => $this->messageData['longitude']
            ];
        }

        // TODO: Get last known location from database
        // For now, return null to request location
        return null;
    }

    /**
     * Send error response to user
     */
    private function sendErrorResponse(WhatsAppService $whatsAppService, string $message): void
    {
        $whatsAppService->sendMessage($this->messageData['from'], $message);
    }

    /**
     * Send success response with results
     */
    private function sendSuccessResponse(
        WhatsAppService $whatsAppService,
        string $medicineName,
        $results,
        array $userLocation
    ): void {
        // Build response message
        $message = "🔍 نتائج البحث عن: *{$medicineName}*\n\n";
        $message .= "وجدت {$results->count()} صيدلية قريبة منك:\n\n";

        $locations = [];

        foreach ($results->take(5) as $index => $result) {
            $num = $index + 1;
            $message .= "📍 *{$num}. {$result['pharmacy_name']} - {$result['branch_name']}*\n";
            $message .= "   💊 الدواء: {$result['medicine_name']}\n";
            $message .= "   💰 السعر: {$result['price']} ج.م\n";
            $message .= "   📦 متوفر: {$result['quantity']} عبوة\n";
            $message .= "   📏 المسافة: {$result['distance_km']} كم (~{$result['eta_minutes']} دقيقة)\n";
            $message .= "   📞 الهاتف: {$result['phone']}\n";
            $message .= "   🕒 مواعيد العمل: {$result['opening_hours']} - {$result['closing_hours']}\n\n";

            $locations[] = [
                'latitude' => $result['latitude'],
                'longitude' => $result['longitude'],
                'name' => $result['pharmacy_name']
            ];
        }

        // Send text message
        $whatsAppService->sendMessage($this->messageData['from'], $message);

        // Send map with locations
        $mapUrl = $whatsAppService->generateStaticMap($userLocation, $locations);
        if ($mapUrl) {
            $whatsAppService->sendImage($this->messageData['from'], $mapUrl, 'خريطة توضح موقعك والصيدليات القريبة');
        }

        // Send interactive buttons (if supported)
        if ($results->count() > 0) {
            $firstResult = $results->first();
            $whatsAppService->sendButtons(
                $this->messageData['from'],
                'ماذا تريد أن تفعل؟',
                [
                    ['id' => 'directions_' . $firstResult['branch_id'], 'title' => '🗺️ الاتجاهات'],
                    ['id' => 'call_' . $firstResult['branch_id'], 'title' => '📞 اتصال'],
                    ['id' => 'order_' . $firstResult['medicine_id'], 'title' => '🛒 حجز']
                ]
            );
        }
    }
}
