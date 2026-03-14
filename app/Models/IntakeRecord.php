<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntakeRecord extends Model
{
    protected $fillable = [
        'source',
        'raw_message',
        'category',
        'priority',
        'confidence_score',
        'core_issue',
        'identifiers',
        'urgency_signal',
        'routing_queue',
        'escalation_flag',
        'escalation_reasons',
        'human_summary',
        'model_used',
        'processed_at',
    ];

    protected $casts = [
        'identifiers' => 'array',
        'escalation_reasons' => 'array',
        'escalation_flag' => 'boolean',
        'processed_at' => 'datetime',
    ];
}
