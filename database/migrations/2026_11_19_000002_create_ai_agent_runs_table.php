<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

return new class extends XotBaseMigration
{
    protected ?string $table_name = 'ai_agent_runs';

    /**
     * Run degli agenti in pausa (EloquentPersistence di neuron-ai): un'azione che chiede approvazione
     * ferma il run, la decisione lo riprende in una richiesta successiva.
     * partition/key: fino a 510 caratteri esadecimali, in ascii perche' l'indice unico stia nei limiti di MySQL.
     */
    public function up(): void
    {
        // ascii/ascii_bin esistono solo in MySQL/MariaDB: su SQLite (test) la colonna resta una stringa normale.
        $ascii = $this->isMysqlFamilyDriver();
        $this->tableCreate(function (Blueprint $table) use ($ascii): void {
            $table->id();

            $partition = $table->string('partition', 510);
            $key = $table->string('key', 510);
            if ($ascii) {
                $partition->charset('ascii')->collation('ascii_bin');
                $key->charset('ascii')->collation('ascii_bin');
            }

            $table->longText('value');

            $table->unique(['partition', 'key']);

            $table->timestamps();
        });
    }
};
