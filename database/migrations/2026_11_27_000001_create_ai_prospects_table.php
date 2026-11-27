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
            $table->string('public_id', 64);
            $table->string('workspace_id');
            $table->string('name')->nullable();
            $table->string('domain')->nullable();
            $table->string('website')->nullable();
            $table->string('source')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('source_at')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('industry')->nullable();
            $table->string('phone')->nullable();
            $table->json('raw_data')->nullable();
            $table->text('summary')->nullable();
            $table->string('status')->default('new')->index();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('last_contact_attempt_at')->nullable();
            $table->unique(['workspace_id', 'public_id']);
            $this->updateTimestamps($table);
        });
    }
};
