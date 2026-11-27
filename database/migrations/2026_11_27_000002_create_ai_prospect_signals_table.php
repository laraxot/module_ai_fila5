<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

return new class extends XotBaseMigration
{
    protected ?string $table_name = 'ai_prospect_signals';

    /**
     * Prove e segnali per una prospect.
     *
     * Ogni segnale ha: fonte (csv, osm, public_listing), timestamp raccolta,
     * qualità fonte e confidenza, breakdown dello score spiegabile.
     * Niente deduzioni da pagine non esaminate: ogni prova è puntuale.
     */
    public function up(): void
    {
        $this->tableCreate(function (Blueprint $table): void {
            $table->id();

            $table->foreignId('ai_prospect_id')->constrained('ai_prospects')->cascadeOnDelete();

            $table->string('source'); // csv, osm, public_listing, website_signals, email_verification

            $table->string('source_url')->nullable();

            $table->dateTime('source_at')->nullable();

            $table->string('quality_source')->default('low'); // high | medium | low

            $table->integer('confidence')->default(0); // 0-100

            $table->integer('score')->default(0); // 0-100 breakdown per-campo

            $table->json('breakdown')->nullable(); // {field: {value, score, reason}}

            $table->longText('note')->nullable();

            $this->updateTimestamps($table);
        });
    }
};