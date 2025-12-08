<?php

namespace App\Jobs;

use App\Services\Integrations\NeonApiService;
use App\Services\NeonDataTransformer;
use App\Services\PdfIntakeFormService;
use App\Models\NeonHash;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\IntakeFormMailable;
use Illuminate\Support\Facades\Log;

class GenerateParticipantPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $participantId;
    
    /**
     * Create a new job instance.
     */
    public function __construct(int $participantId)
    {
        $this->participantId = $participantId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        NeonApiService $neonApi,
        NeonDataTransformer $transformer,
        PdfIntakeFormService $pdfService
    ) {
        try {
            // Fetch and hash participant data
            $fullRecord = $neonApi->buildFullParticipantRecord($this->participantId);
            
            $hash = hash('sha256', json_encode($fullRecord, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
            $record = NeonHash::firstOrCreate(['id' => $hash]);

            if (!$record->wasRecentlyCreated) {
                return;
            }

            // Transform data and generate PDF
            $participant = $transformer->transformPerson($fullRecord);
            $pdfPath = $pdfService->generate($participant);

            // Send email
            Log::info('📧 Sending PDF email for participant '.$this->participantId);
            Mail::to('hello@example.com')
                ->send(new IntakeFormMailable($participant, $pdfPath));
            Log::info('✅ PDF email sent.');

        } catch (\Exception $e) {
            Log::error('Failed to generate PDF for participant '.$this->participantId.': '.$e->getMessage());
            throw $e; // Let the job retry if needed
        }
    }
}
