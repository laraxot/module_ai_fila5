<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

return new class extends XotBaseMigration
{
    protected ?string $table_name = 'ai_suppressions';

    /**
     * Lista di soppressione (do-not-contact) persistente.
     *
     * Rispettata anche nei re-import: la suppression sopravvive alle future
     * importazioni CSV/OSM per la stessa identità deterministica (public_id).
     */
    public function up(): void
    {
        $this->tableCreate(function (Blueprint $table): void {
            $table->id();

            $table->string('public_id', 64);

            $table->string('workspace_id', 64)->index();

            $table->string('email_hash', 64)->nullable();

            $table->string('reason'); // do_not_contact, unsubscribed, bounced, invalid_email, gdpr_request

            $table->longText('note')->nullable();

            $table->string('suppressed_by_user_id', 36)->nullable();

            $table->dateTime('suppressed_at');

            $table->unique(['workspace_id', 'public_id']);

            $this->updateTimestamps($table);
        });
    }
};
