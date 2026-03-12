# PHPStan Findings - AI Module

**Data**: 2025-10-10  
**Livello**: MAX (9)  
**Status**: 🔧 IN CORREZIONE

## Errori Identificati

### 1. Missing Type Annotations (Priority: ALTA)

#### File: `Filament/Pages/Completion.php`
- **Linea 29**: Property `$completionData` senza type hint array elements
- **Linea 94**: Method `getFormActions()` return type array senza value type

**Correzione**:
```php
// Prima
protected array $completionData = [];

// Dopo
/** @var array<string, mixed> */
protected array $completionData = [];

// Prima
public function getFormActions(): array

// Dopo
/**
 * @return array<int, \Filament\Actions\Action>
 */
public function getFormActions(): array
```

#### File: `Filament/Pages/FineTuning.php`
- **Linea 41**: Method `safeTranslate()` unused
- **Linea 48**: Cannot cast mixed to string
- **Linea 130**: Parameter `$data` senza type hint
- **Linea 142**: Method `getFormActions()` return type

**Correzione**:
```php
// Rimuovere metodo unused o usarlo
// Linea 41: Rimuovere safeTranslate() se non usato

// Linea 48: Aggiungere type check
$value = is_string($mixed) ? $mixed : (string) $mixed;

// Linea 130
/**
 * @param array<string, mixed> $data
 */
protected function sendFineTuningRequest(array $data): void

// Linea 142
/**
 * @return array<int, \Filament\Actions\Action>
 */
public function getFormActions(): array
```

#### File: `Services/AIService.php`
Multipli metodi con return type array senza value type.

**Pattern di Correzione**:
```php
/**
 * @return array<string, mixed>
 */
public function methodName(): array
```

## Best Practices Identificate

### 1. Array Type Hints
Sempre specificare il tipo degli elementi negli array:
```php
// ❌ ERRATO
public function getData(): array

// ✅ CORRETTO
/**
 * @return array<string, mixed>
 */
public function getData(): array
```

### 2. Filament Actions
Per array di Actions Filament:
```php
/**
 * @return array<int, \Filament\Actions\Action>
 */
public function getFormActions(): array
```

### 3. Cast Safety
Sempre verificare il tipo prima di castare:
```php
// ❌ ERRATO
$string = (string) $mixed;

// ✅ CORRETTO
$string = is_string($mixed) ? $mixed : (string) $mixed;
// oppure
assert(is_string($mixed));
$string = $mixed;
```

## Prossimi Step

1. ✅ Documentare findings
2. ⏳ Correggere Completion.php
3. ⏳ Correggere FineTuning.php
4. ⏳ Correggere AIService.php
5. ⏳ Validare con PHPStan

---

**Aggiornato**: 2025-10-10T09:39:07+02:00

## Nuove segnalazioni PHPStan 2026-03-02

### 1. `AIService` e uso di `Webmozart\Assert`

- **Errore**: `staticMethod.alreadyNarrowedType` su chiamate a `Assert::string()` quando il tipo è già `string` (es. risultato di `Safe\json_encode`).  
- **Analisi**:
  - Le funzioni di `thecodingmachine/safe` (`Safe\json_encode`, `Safe\json_decode`, ecc.) hanno già return type non-null (string/array), quindi l’`Assert::string()` è ridondante e PHPStan lo segnala.
- **Piano di fix**:
  - Rimuovere gli `Assert::string()` superflui dove il tipo è garantito dalla funzione `Safe\*`.
  - Mantenere gli assert solo dove realmente restringono un `mixed` a `string`/`array`.
  - Aggiornare la documentazione interna del modulo AI per chiarire il pattern: **Safe\*** per garantire il tipo, `Assert` per validare input esterni/business logic.

### 2. Evoluzione architetturale: da `AIService` ad Actions

- **Regola di progetto**: evitare classi `*Service` per la business logic, preferire **Spatie Queueable Actions**.  
- **Piano di refactor**:
  - Introdurre Actions dedicate (es. `ClassifyTicketAction`, `SuggestSolutionsAction`, `AnalyzeSentimentAction`, `PredictPriorityAction`, `OptimizeRoutingAction`).
  - Fare in modo che `AIService` diventi al massimo un **facade thin** o venga gradualmente dismesso in favore delle Actions, mantenendo la stessa API pubblica verso Filament/HTTP.
  - Documentare in `docs/structure.md` del modulo AI la nuova mappa:
    - `Actions/` = business logic riusabile e queueable
    - `Http/Controllers` / Filament pages = solo orchestrazione I/O

Questa sezione va tenuta sincronizzata con i prossimi run di PHPStan, aggiornando lo stato (✅/⏳) delle correzioni pianificate.

## Correzioni PHPStan 2026-03-12

### 1. Rimozione metodi `parse*Response()` non utilizzati

**File**: `Services/AIService.php`

**Errore**: 7 metodi `protected` mai chiamati:
- `parseClassificationResponse()`
- `parseSolutionResponse()`
- `parseSentimentResponse()`
- `parsePriorityResponse()`
- `parseRoutingResponse()`
- `parsePatternResponse()`
- `parseImprovementResponse()`

**Analisi**:
- I metodi erano stati creati per un uso futuro ma mai integrati
- La logica di parsing JSON è già gestita inline nei metodi pubblici
- PHPStan Level 10 segnala metodi non utilizzati come errore

**Correzione applicata**: ✅ Rimossi completamente tutti i 7 metodi (righe 520-654)

**Rationale**:
- Dead code removal per compliance PHPStan Level 10
- I metodi pubblici già gestiscono il parsing con `json_decode()` inline
- Se necessario in futuro, possono essere reintrodotti come Actions dedicate

**Pattern seguito**: Vedi `base_laravelpizza/Modules/User/app/Filament/Resources/TenantResource/Pages/CreateTenant.php` - metodi non utilizzati rimossi/commentati
