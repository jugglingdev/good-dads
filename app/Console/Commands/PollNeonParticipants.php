<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Integrations\NeonApiService;
use App\Jobs\GenerateParticipantPdfJob; 
use App\Models\NeonHash;

class PollNeonParticipants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neon:poll-participants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Polls Neon for today\'s participants and queues PDFs for new records';

    /**
     * Inject NeonApiService.
     */
    protected NeonApiService $neonApi;

    public function __construct(NeonApiService $neonApi)
    {
        parent::__construct();
        $this->neonApi = $neonApi;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $participants = $this->neonApi->getTodaysParticipants();

        foreach ($participants as $person) {
            $participantId = (int) $person['persons_id']['value'];

            // Build the full participant record
            $fullRecord = $this->neonApi->buildFullParticipantRecord($participantId);


                dispatch(new GenerateParticipantPdfJob($participantId));
        }

        $this->info('Polling complete.');
    }
}
