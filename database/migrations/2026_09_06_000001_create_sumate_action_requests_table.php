<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sumate_action_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('sumate_participants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accion_id')->constrained('sumate_acciones')->cascadeOnDelete();
            $table->text('description');
            $table->string('evidence', 500)->nullable();
            $table->foreignId('form_submission_id')->nullable()->unique()->constrained('form_submissions')->nullOnDelete();
            $table->string('status', 20)->default('pendiente');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('granted')->default(false);
            $table->timestamp('granted_at')->nullable();
            $table->integer('points_granted')->nullable();
            $table->timestamps();

            $table->index(['participant_id', 'accion_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sumate_action_requests');
    }
};
