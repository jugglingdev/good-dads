<?php

namespace App\Services;

use mikehaertl\pdftk\Pdf;
use Carbon\Carbon;

class PdfIntakeFormService
{
    protected string $pdfTemplatePath = 'app/pdfs/intake-forms/enrollment_documents_fillable_gd_global_DRAFT_v1.pdf';

    /**
     * Generate PDF content for a participant.
     *
     * @param object $participant
     * @return string
     * @throws \Exception
     */
    public function generate($data): string
    {
        // Format Carbon dates
        $mapped = [];
        foreach ($data as $field => $value) {
            if ($value instanceof Carbon) {
                $mapped[$field] = $value->format('m/d/Y');
            } else {
                $mapped[$field] = $value ?? '';
            }
        }

        $pdfPath = storage_path($this->pdfTemplatePath);

        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF template not found at {$pdfPath}");
        }

        // Load and fill the PDF
        $pdf = new Pdf(storage_path($this->pdfTemplatePath));
        $result = $pdf->fillForm($data)
            ->needAppearances()
            ->flatten()
            ->execute();

        if (!$result) { 
            throw new \Exception('PDF generation failed: '.$pdf->getError());
        }

        $tmpFile = $pdf->getTmpFile();
        if (!file_exists($tmpFile)) {
            throw new \Exception('Temporary PDF file was not created.');
        }

        $pdfContent = file_get_contents((string) $pdf->getTmpFile());

        return $pdfContent;
    }
}
