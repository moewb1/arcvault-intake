<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('intake_records', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50);
            $table->text('raw_message');
            $table->string('category', 50);
            $table->string('priority', 20);
            $table->unsignedTinyInteger('confidence_score');
            $table->text('core_issue');
            $table->json('identifiers')->nullable();
            $table->string('urgency_signal', 20);
            $table->string('routing_queue', 50);
            $table->boolean('escalation_flag')->default(false);
            $table->json('escalation_reasons')->nullable();
            $table->text('human_summary');
            $table->string('model_used', 100);
            $table->timestamp('processed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intake_records');
    }
};
