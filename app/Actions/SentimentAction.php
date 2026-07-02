<?php

declare(strict_types=1);

namespace Modules\AI\Actions;

use Exception;
use Modules\AI\Actions\Sentiment\BasicSentimentAnalyzer;
use Modules\AI\Actions\Sentiment\TransformersSentimentAnalyzer;
use Modules\AI\Contracts\SentimentAnalyzer;
use Modules\AI\Datas\SentimentData;
use Spatie\QueueableAction\QueueableAction;

use function Safe\error_log;

/**
 * Sentiment analysis using the driver configured in `ai.sentiment_driver`
 * ('transformers' when the ML pipeline is desired, otherwise 'basic').
 */
class SentimentAction
{
    use QueueableAction;

    public function execute(string $prompt): SentimentData
    {
        $analyzer = $this->resolveAnalyzer();

        try {
            $result = $analyzer->analyze($prompt);

            return SentimentData::from($result);
        } catch (Exception $e) {
            error_log('Sentiment analysis error: '.$e->getMessage());

            return SentimentData::from([
                'error' => $e->getMessage(),
                'status' => 'error',
            ]);
        }
    }

    private function resolveAnalyzer(): SentimentAnalyzer
    {
        return match (config('ai.sentiment_driver', 'basic')) {
            'transformers' => new TransformersSentimentAnalyzer,
            default => new BasicSentimentAnalyzer,
        };
    }
}
