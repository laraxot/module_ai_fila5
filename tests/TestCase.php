<?php

declare(strict_types=1);

namespace Modules\AI\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Modules\AI\Providers\AIServiceProvider;
use Modules\Xot\Tests\XotBaseTestCase;

/**
 * Base test case for AI module.
 *
 * Unit tests here do not require database migrations.
 * Vietato RefreshDatabase / DatabaseMigrations (dati sacri).
 */
abstract class TestCase extends XotBaseTestCase
{
    /**
     * I provider che servono a questa suite.
     *
     * ## Attenzione: questo metodo non lo chiama nessuno
     *
     * `getPackageProviders()` e' un aggancio di **Orchestra Testbench**, e la
     * catena di questo TestCase arriva a `Illuminate\Foundation\Testing\TestCase`:
     *
     *     Modules\AI\Tests\TestCase
     *       <- Modules\Xot\Tests\XotBaseTestCase
     *       <- Illuminate\Foundation\Testing\TestCase
     *       <- PHPUnit\Framework\TestCase
     *
     * Non c'e' Testbench in mezzo, quindi il metodo non viene invocato e questo
     * elenco non registra niente. Lo dichiarano **tutti e 19** i TestCase di
     * modulo di questo progetto (base_quaeris_fila5#243).
     *
     * Per diciotto moduli non si notava, perche' i loro provider li carica
     * l'applicazione vera: stanno in `modules_statuses.json` e nwidart li
     * abilita. **AI non c'e'** in quel file — 19 cartelle, 18 voci, e nessuna
     * dichiarata `false` — quindi `AIServiceProvider` non si carica mai, e con
     * esso nessuna delle sue legature. Il sintomo erano quattro test con
     *
     *     Target [NeuronAI\Chat\History\MessageStoreInterface] is not
     *     instantiable while building [...TestXotAgent]
     *
     * Si registra quindi il provider a mano in {@see setUp()}, e l'elenco resta
     * qui come dichiarazione di cosa serve.
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders(Application $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AIServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Il modulo AI non e' abilitato in **questo** progetto, e sta bene
        // cosi': niente fuori dal modulo riferisce `Modules\AI\`, non esiste
        // nessuna tabella `ai_*`, e abilitarlo creerebbe sette tabelle e
        // risorse Filament che a Quaeris non servono. E' un modulo condiviso
        // (`laraxot/module_ai_fila5`), usato da altri progetti.
        //
        // Ma i suoi test verificano **il modulo**, non la configurazione del
        // progetto ospite: che qui non sia montato non deve decidere se possano
        // girare. Si registra il provider per la durata del test, cosi' le sue
        // legature ci sono senza che il modulo risulti attivo altrove.
        $this->app->register(AIServiceProvider::class);

        config([
            'services.openai.api_key' => 'test-openai-key',
            'services.openai.model' => 'gpt-3.5-turbo-instruct',
        ]);
    }

    /**
     * Le tabelle del modulo sono installate in questo progetto?
     *
     * Registrare il provider da' le legature, non le tabelle: le migration di
     * un modulo non abilitato non vengono eseguite, quindi `ai_threads`,
     * `ai_agent_runs` e le altre cinque qui non esistono.
     *
     * I test che toccano il database si saltano con questa condizione invece di
     * fallire. Non e' un modo di nascondere un difetto: in **questo** progetto
     * quelle tabelle non devono esistere — nessun codice fuori dal modulo
     * riferisce `Modules\AI\`, e crearle per far passare dei test farebbe
     * divergere l'ambiente di prova dal progetto. La verifica di quello schema
     * spetta al progetto che monta il modulo davvero
     * (base_quaeris_fila5#243).
     */
    public static function moduleTablesMissing(): bool
    {
        return ! Schema::hasTable('ai_threads') || ! Schema::hasTable('ai_agent_runs');
    }
}
