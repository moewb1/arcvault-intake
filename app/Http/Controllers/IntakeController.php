<?php

namespace App\Http\Controllers;

use App\Models\IntakeRecord;
use App\Services\IntakeTriageService;
use App\Support\SampleIntakeMessages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IntakeController extends Controller
{
    public function index(): View
    {
        $records = IntakeRecord::query()
            ->latest('processed_at')
            ->latest('id')
            ->paginate(5)
            ->withQueryString();

        return view('intake.index', [
            'records' => $records,
            'sources' => $this->allowedSources(),
            'totalRecords' => IntakeRecord::query()->count(),
            'escalatedRecords' => IntakeRecord::query()->where('escalation_flag', true)->count(),
            'avgConfidence' => (int) round((float) IntakeRecord::query()->avg('confidence_score')),
            'liveLlmRecords' => IntakeRecord::query()->where('model_used', '!=', 'rules-fallback')->count(),
        ]);
    }

    public function store(Request $request, IntakeTriageService $triageService)
    {
        $validated = $request->validate([
            'source' => ['required', 'string', Rule::in($this->allowedSources())],
            'raw_message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $processed = $triageService->process($validated['source'], $validated['raw_message']);
        $record = IntakeRecord::query()->create($processed);

        return redirect()
            ->route('intake.index')
            ->with('status', "Request #{$record->id} processed and routed to {$record->routing_queue}.");
    }

    public function processSamples(IntakeTriageService $triageService)
    {
        $createdCount = 0;

        foreach (SampleIntakeMessages::all() as $sample) {
            $processed = $triageService->process($sample['source'], $sample['raw_message']);
            IntakeRecord::query()->create($processed);
            $createdCount++;
        }

        return redirect()
            ->route('intake.index')
            ->with('status', "Processed {$createdCount} synthetic sample messages.");
    }

    public function exportJson()
    {
        $records = IntakeRecord::query()
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'count' => $records->count(),
            'records' => $records,
        ], 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * @return array<int, string>
     */
    private function allowedSources(): array
    {
        return [
            'Email',
            'Web Form',
            'Support Portal',
        ];
    }
}
