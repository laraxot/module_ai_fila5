<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

return new class extends XotBaseMigration
{
    protected ?string $table_name = 'ai_prospects';

    public function up(): void
    {
        $this->tableCreate(function (Blueprint $table): void {
            $table->id();
            // Identita' stabile e isolamento (vedi app/Models/AiProspect.php).
            $table->string('workspace_id');
            $table->string('workspace', 96)->nullable();
            $table->string('public_id', 64);
            $table->char('fingerprint', 64)->nullable();
            // Anagrafica azienda.
            $table->string('name')->nullable();
            $table->string('domain')->nullable();
            $table->string('website')->nullable();
            $table->string('category')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('industry')->nullable();
            $table->string('phone')->nullable();
            // Provenienza e dati grezzi.
            $table->string('source')->nullable();
            $table->string('source_provider', 64)->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('source_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->text('summary')->nullable();
            // Pipeline di qualificazione.
            $table->json('signals')->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('stage', 32)->default('discovered');
            $table->boolean('is_suppressed')->default(false);
            $table->string('status')->default('new')->index();
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('researched_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('last_contact_attempt_at')->nullable();
            $table->unique(['workspace_id', 'public_id']);
            $table->unique(['workspace', 'fingerprint'], 'ai_prospect_workspace_fingerprint_unique');
            $table->index(['workspace', 'stage', 'score'], 'ai_prospect_workspace_stage_score');
            $this->updateTimestamps($table);
        });
    }
};
