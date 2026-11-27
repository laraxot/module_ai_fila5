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
            $table->string('workspace', 96);
            $table->char('fingerprint', 64);
            $table->string('name');
            $table->text('website')->nullable();
            $table->string('domain')->nullable();
            $table->string('category')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('source_provider', 64);
            $table->text('source_url');
            $table->json('signals')->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('stage', 32)->default('discovered');
            $table->boolean('is_suppressed')->default(false);
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('researched_at')->nullable();
            $table->unique(['workspace', 'fingerprint'], 'ai_prospect_workspace_fingerprint_unique');
            $table->index(['workspace', 'stage', 'score'], 'ai_prospect_workspace_stage_score');
            $this->updateTimestamps($table);
        });
    }
};
