<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

return new class() extends XotBaseMigration
{
    /**
     * Table name following Laraxot philosophy.
     */
    protected ?string $table_name = 'ai_threads';

    /**
     * Run the migration following AI module naming standards.
     *
     * Nota: nessuna FK verso 'users' perché la tabella users è sulla connessione 'user',
     * mentre ai_threads è sulla connessione 'xot'. MySQL non permette FK cross-database.
     */
    public function up(): void
    {
        $this->tableCreate(function (Blueprint $table): void {
            $table->id();

            $table->uuid('public_id')->unique();

<<<<<<< .merge_file_KKefsY
            $table->unsignedBigInteger('created_by_user_id')->index();
=======
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();
>>>>>>> .merge_file_F3y1g4

            $table->string('panel_id')->default('operator');

            $table->dateTime('last_message_at')->nullable();

            $table->json('meta')->nullable();

            $this->updateTimestamps($table);
        });
<<<<<<< .merge_file_KKefsY
=======

        // Nullable: un thread puo' appartenere a un visitatore anonimo (es. widget pubblico).
        $this->tableUpdate(function (Blueprint $table): void {
            if ($this->hasColumn('created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->change();
            }
        });
>>>>>>> .merge_file_F3y1g4
    }
};
