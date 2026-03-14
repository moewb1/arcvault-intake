<?php

namespace Tests\Feature;

use App\Models\IntakeRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntakeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_submission_processes_and_persists_intake_record(): void
    {
        $response = $this->post('/intake', [
            'source' => 'Email',
            'raw_message' => 'Hi team, logging in returns a 403 error for arcvault.io/user/jsmith after your update.',
        ]);

        $response->assertRedirect('/');

        $this->assertDatabaseCount('intake_records', 1);

        $record = IntakeRecord::query()->firstOrFail();
        $this->assertContains($record->category, [
            'Bug Report',
            'Technical Question',
            'Incident/Outage',
            'Billing Issue',
            'Feature Request',
        ]);
        $this->assertNotEmpty($record->routing_queue);
        $this->assertNotEmpty($record->human_summary);
    }

    public function test_sample_command_processes_all_five_inputs(): void
    {
        $this->artisan('intake:process-samples')
            ->expectsOutputToContain('Processed 5 sample messages.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('intake_records', 5);

        $outageRecord = IntakeRecord::query()
            ->where('raw_message', 'like', '%Multiple users affected%')
            ->first();

        $this->assertNotNull($outageRecord);
        $this->assertTrue($outageRecord->escalation_flag);
        $this->assertSame('Escalation Queue', $outageRecord->routing_queue);
    }
}
