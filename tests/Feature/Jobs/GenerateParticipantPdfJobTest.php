<?php

use App\Jobs\GenerateParticipantPdfJob;
use App\Models\NeonHash;
use App\Services\Integrations\NeonApiService;
use App\Services\NeonDataTransformer;
use App\Services\PdfIntakeFormService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\IntakeFormMailable;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Mail::fake();
    Log::spy();

    // Ensure database is clean before each test
    NeonHash::query()->delete();
});

test('job fetches participant record, creates hash, transforms data, generates PDF, and sends email', function () {
    // Arrange
    $participantId = 124;

    // Mock: what the Neon API should return
    $fullRecord = [
        'id'         => 124,
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ];

    // Mock: what the transformer should return
    $transformed = [
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ];

    // Fake PDF binary
    $fakePdf = '%PDF FAKE CONTENT%';

    // Create mocks
    $neonApi = Mockery::mock(NeonApiService::class);
    $neonApi->shouldReceive('buildFullParticipantRecord')
        ->once()
        ->with($participantId)
        ->andReturn($fullRecord);

    $transformer = Mockery::mock(NeonDataTransformer::class);
    $transformer->shouldReceive('transformPerson')
        ->once()
        ->with($fullRecord)
        ->andReturn($transformed);

    $pdfService = Mockery::mock(PdfIntakeFormService::class);
    $pdfService->shouldReceive('generate')
        ->once()
        ->with($transformed)
        ->andReturn($fakePdf);

    // Act: run the job
    (new GenerateParticipantPdfJob($participantId))
        ->handle($neonApi, $transformer, $pdfService);

    // Assert: Hash record was created
    expect(NeonHash::count())->toBe(1);

    // Assert: Email was sent
    Mail::assertSent(IntakeFormMailable::class, function ($mail) use ($transformed) {
        return $mail->participant === $transformed;
    });

    // Assert: Email has attachment
    Mail::assertSent(IntakeFormMailable::class, function (IntakeFormMailable $mail) use ($fakePdf) {
        return count($mail->attachments) > 0;
    });
});

test('job exits early when hash already exists', function () {
    // Arrange
    $participantId = 124;

    $fullRecord = [
        'id'         => 124,
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ];

    $hash = hash('sha256', json_encode($fullRecord));

    // Create the hash BEFORE running job
    NeonHash::create(['id' => $hash]);

    // Mock Neon API (still called once)
    $neonApi = Mockery::mock(NeonApiService::class);
    $neonApi->shouldReceive('buildFullParticipantRecord')
        ->once()
        ->with(124)
        ->andReturn($fullRecord);

    // Mocks should NOT be called
    $transformer = Mockery::mock(NeonDataTransformer::class);
    $transformer->shouldNotReceive('transformPerson');

    $pdfService = Mockery::mock(PdfIntakeFormService::class);
    $pdfService->shouldNotReceive('generate');

    Mail::fake();

    // Act
    (new GenerateParticipantPdfJob($participantId))
        ->handle($neonApi, $transformer, $pdfService);

    // Assert: email was NOT sent
    Mail::assertNothingSent();

    // Assert: still only 1 hash record
    expect(NeonHash::count())->toBe(1);
});
