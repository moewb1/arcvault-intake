<?php

namespace App\Console\Commands;

use App\Models\IntakeRecord;
use App\Services\IntakeTriageService;
use App\Support\SampleIntakeMessages;
use Illuminate\Console\Command;

class ProcessSampleIntake extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intake:process-samples';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the five ArcVault sample intake messages.';

    /**
     * Execute the console command.
     */
    public function handle(IntakeTriageService $triageService): int
    {
        $created = 0;

        foreach (SampleIntakeMessages::all() as $sample) {
            $processed = $triageService->process($sample['source'], $sample['raw_message']);
            $record = IntakeRecord::query()->create($processed);
            $created++;

            $this->line(
                "Created #{$record->id} | {$record->category} | {$record->routing_queue} | confidence {$record->confidence_score}%"
            );
        }

        $this->info("Processed {$created} sample messages.");

        return self::SUCCESS;
    }
}
