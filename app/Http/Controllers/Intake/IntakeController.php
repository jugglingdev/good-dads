<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Mail\IntakeFormMailable;
use App\Services\Integrations\NeonApiService;
use App\Services\NeonDataTransformer;
use App\Services\PdfIntakeFormService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class IntakeController extends Controller
{
    public function generatePdf(int $participantId, NeonApiService $neonApi, NeonDataTransformer $transformer, PdfIntakeFormService $pdfService)
    {
        try {
            // Fetch amd transform participant data from Neon
            $raw = $neonApi->getParticipant($participantId);
            $participant = $transformer->transformPerson($raw);

            // Generate filled PDF
            $pdfPath = $pdfService->generate($participant);

            // Send PDF attachment via email
            Log::info('📧 Attempting to send email', ['path' => $pdfPath]);
            Mail::to('hello@example.com')
                ->send(new IntakeFormMailable($participant, $pdfPath));
            Log::info('✅ Email sent successfully');

            return response('PDF generated and emailed successfully.', 200);

        } catch (\Exception $e) {
            return response('Failed to generate PDF: '.$e->getMessage(), 500);
        }
    }
}
