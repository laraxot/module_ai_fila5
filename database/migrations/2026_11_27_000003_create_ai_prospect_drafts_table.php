<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

return new class extends XotBaseMigration
{
    protected ?string $table_name = 'ai_prospect_drafts';

    /**
     * Brief e bozza di primo contatto per una prospect.
     *
     * MAI invio automatico: approvazione umana obbligatoria per ogni messaggio.
     * Stato: draft -> pending_review -> reviewed -> sent -> suppressed.
     * lawful_basis e consenso rimangono obbligatori prima di invio.
     */
    public function up(): void
    {
        $this->tableCreate(function (Blueprint $table): void {
            $table->id();

            $table->foreignId('ai_prospect_id')->constrained('ai_prospects')->cascadeOnDelete();

            $table->string('subject')->nullable();

            $table->longText('body');

            $table->string('channel')->default('email'); // email, linkedin, etc.

            $table->string('status')->default('draft');

            $table->json('lawful_basis')->nullable(); // {basis: string, evidence: string}

            $table->json('personalization')->nullable(); // {icebreaker, hooks, company_data}

            $table->string('reviewed_by_user_id', 36)->nullable();

            $table->dateTime('reviewed_at')->nullable();

            $table->dateTime('sent_at')->nullable();

            $table->longText('suppression_reason')->nullable();

            $this->updateTimestamps($table);
        });
    }
};