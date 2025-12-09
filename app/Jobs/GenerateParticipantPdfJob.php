<?php

namespace App\Jobs;

use App\Services\Integrations\NeonApiService;
use App\Services\NeonDataTransformer;
use App\Services\PdfIntakeFormService;
use App\Models\NeonHash;
use Illuminate\Support\Str;
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
            Log::info("Starting PDF generation job for participant {$this->participantId}");

            // Fetch participant data
            $fullRecord = $neonApi->buildFullParticipantRecord($this->participantId);

            // Validate Neon record

            // Hash the full record to check for duplicates
            $hash = hash('sha256', json_encode($fullRecord, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
            $record = NeonHash::firstOrCreate(['id' => $hash]);

            if (!$record->wasRecentlyCreated) {
                Log::info("Participant PDF already exists for participant {$this->participantId}");
                return;
            }

            // Transform data
            Log::info("Transforming participant data for participant {$this->participantId}");
            $participant = $transformer->transformPerson($fullRecord);

            // Generate PDF in memory
            Log::info("Generating PDF for participant {$this->participantId}");
            $pdfContent = $pdfService->generate($participant);

            // Generate timestamped filename
            $timestamp = now()->format('Y-m-d_H-i-s');
            $first = Str::of($participant['first_name'] ?? '')->slug('_')->ucfirst();
            $last  = Str::of($participant['last_name'] ?? '')->slug('_')->ucfirst();

            $filename = "{$last}_{$first}_Enrollment_{$timestamp}.pdf";

            // Send email
            Log::info("Sending PDF email for participant {$this->participantId}");
            Mail::to('hello@example.com')
                ->send(new IntakeFormMailable(
                    $participant,
                    $pdfContent,
                    $filename
                ));

            Log::info("PDF successfully generated and sent for participant {$this->participantId}");

        } catch (\Exception $e) {
            Log::error("PDF generation job failed for participant {$this->participantId}: ".$e->getMessage());
            throw $e; // Let the job retry if needed
        }
    }
}
