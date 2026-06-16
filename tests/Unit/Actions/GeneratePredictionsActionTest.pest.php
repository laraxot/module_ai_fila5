<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AI\Actions\GeneratePredictionsAction;
use Modules\AI\Datas\PredictionData;
use Modules\Predict\Models\Category;
use Modules\Predict\Models\Predict;
use Modules\Rating\Models\Rating;

uses(RefreshDatabase::class);

/**
 * Test per la generazione AI di predizioni singole.
 */
it('generates a single prediction with AI', function () {
    // Arrange
    $action = app(GeneratePredictionsAction::class);
    
    // Act
    $prediction = $action->execute('Elezioni politiche 2026', [
        'category' => 'Politica',
        'language' => 'it',
    ]);
    
    // Assert
    expect($prediction)->toBeInstanceOf(PredictionData::class);
    expect($prediction->title)->toBeString();
    expect($prediction->title)->not->toBeEmpty();
    expect($prediction->description)->toBeString();
    expect($prediction->category)->toBe('Politica');
    expect($prediction->tags)->toBeArray();
    expect($prediction->closed_at)->toBeString();
    expect($prediction->is_wagerable)->toBeTrue();
});

/**
 * Test per la generazione di multiple predizioni.
 */
it('generates multiple unique predictions', function () {
    // Arrange
    $action = app(GeneratePredictionsAction::class);
    $titles = [];
    
    // Act
    for ($i = 0; $i < 5; $i++) {
        $prediction = $action->execute("Topic {$i}");
        $titles[] = $prediction->title;
    }
    
    // Assert
    expect($titles)->toHaveCount(5);
    expect(array_unique($titles))->toHaveCount(5); // All titles should be unique
});

/**
 * Test per la validazione dei dati generati.
 */
it('generates valid prediction data structure', function () {
    // Arrange
    $action = app(GeneratePredictionsAction::class);
    
    // Act
    $prediction = $action->execute('Test prediction');
    
    // Assert - Verify all required fields
    expect($prediction)->toHaveProperties([
        'title',
        'description',
        'content',
        'excerpt',
        'category',
        'tags',
        'closed_at',
        'ends_at',
        'liquidity_parameter',
        'stocks_count',
        'is_wagerable',
    ]);
    
    // Validate data types
    expect($prediction->liquidity_parameter)->toBeFloat();
    expect($prediction->liquidity_parameter)->toBeGreaterThanOrEqual(0.0);
    expect($prediction->liquidity_parameter)->toBeLessThanOrEqual(1.0);
    
    expect($prediction->stocks_count)->toBeInt();
    expect($prediction->stocks_count)->toBeGreaterThan(0);
    
    expect($prediction->tags)->toBeArray();
    expect($prediction->tags)->not->toBeEmpty();
});

/**
 * Test per la validazione delle date future.
 */
it('generates future dates for closed_at', function () {
    // Arrange
    $action = app(GeneratePredictionsAction::class);
    $now = now();
    
    // Act
    $prediction = $action->execute('Test prediction');
    
    // Assert
    $closedAt = new DateTime($prediction->closed_at);
    expect($closedAt)->toBeGreaterThan($now);
});

/**
 * Test per il fallback quando OpenAI non è disponibile.
 */
it('uses fallback when OpenAI API key is not configured', function () {
    // Arrange
    $originalKey = config('openai.api_key');
    config(['openai.api_key' => null]); // Simulate missing API key
    
    $action = app(GeneratePredictionsAction::class);
    
    // Act
    $prediction = $action->execute('Test prediction');
    
    // Assert - Should still generate valid data from fallback
    expect($prediction)->toBeInstanceOf(PredictionData::class);
    expect($prediction->title)->not->toBeEmpty();
    
    // Restore
    config(['openai.api_key' => $originalKey]);
})->skip('Requires config manipulation');

/**
 * Test per la generazione con categoria specifica.
 */
it('respects category parameter', function () {
    // Arrange
    $action = app(GeneratePredictionsAction::class);
    
    // Act
    $prediction = $action->execute('Serie A 2026', [
        'category' => 'Sport',
    ]);
    
    // Assert
    expect($prediction->category)->toBe('Sport');
});

/**
 * Test per la conversione in array per Predict model.
 */
it('converts prediction data to predict array', function () {
    // Arrange
    $action = app(GeneratePredictionsAction::class);
    $prediction = $action->execute('Test prediction');
    
    // Act
    $predictArray = $prediction->toPredictArray();
    
    // Assert
    expect($predictArray)->toBeArray();
    expect($predictArray)->toHaveKeys([
        'title',
        'description',
        'content',
        'excerpt',
        'category_name',
        'tags',
        'closed_at',
        'ends_at',
        'liquidity_parameter',
        'stocks_count',
        'is_wagerable',
        'status',
        'published_at',
    ]);
    
    // Verify title is localized
    expect($predictArray['title'])->toBeArray();
    expect($predictArray['title'])->toHaveKey('it');
});

/**
 * Test per la validazione dei tags.
 */
it('generates valid tags array', function () {
    // Arrange
    $action = app(GeneratePredictionsAction::class);
    
    // Act
    $prediction = $action->execute('Bitcoin crypto prediction');
    
    // Assert
    expect($prediction->tags)->toBeArray();
    expect($prediction->tags)->not->toBeEmpty();
    
    // Verify all tags are strings
    foreach ($prediction->tags as $tag) {
        expect($tag)->toBeString();
        expect($tag)->not->toBeEmpty();
    }
});

/**
 * Test per la gestione errori con topic vuoto.
 */
it('handles empty topic gracefully', function () {
    // Arrange
    $action = app(GeneratePredictionsAction::class);
    
    // Act & Assert
    expect(fn() => $action->execute(''))
        ->not->toThrow(Exception::class);
})->todo('Implement error handling');

/**
 * Test per la generazione batch con rate limiting.
 */
it('respects rate limiting in batch generation', function () {
    // Arrange
    $action = app(GeneratePredictionsAction::class);
    $startTime = microtime(true);
    
    // Act - Generate 3 predictions
    for ($i = 0; $i < 3; $i++) {
        $action->execute("Prediction {$i}");
    }
    
    // Assert
    $elapsedTime = microtime(true) - $startTime;
    // Should take at least 2 seconds (1 second delay between calls)
    expect($elapsedTime)->toBeGreaterThan(1.5);
})->skip('Rate limiting not implemented yet');
