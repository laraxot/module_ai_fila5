<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

return new class extends XotBaseMigration
{
    protected ?string $table_name = 'ai_prospect_suppressions';

    public function up(): void
    {
        $this->tableCreate(function (Blueprint $table): void {
            $table->id();
            $table->string('workspace', 96);
            $table->char('fingerprint', 64);
            $table->string('reason', 255);
            $table->unique(['workspace', 'fingerprint'], 'ai_suppression_workspace_fingerprint_unique');
            $this->updateTimestamps($table);
        });
    }
};
