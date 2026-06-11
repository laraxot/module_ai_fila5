<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions;

use Mockery;
use Modules\AI\Actions\SentimentAction;
use Modules\AI\Datas\SentimentData;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class SentimentActionTest extends TestCase
{
    private SentimentAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new SentimentAction;
    }

    /** @test */
    public function it_analyzes_positive_sentiment_correctly(): void
    {
        $text = 'This is a great product with excellent features. I am very happy with it.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
        Assert::assertGreaterThan(0, $result->score);
    }

    /** @test */
    public function it_analyzes_negative_sentiment_correctly(): void
    {
        $text = 'This is a bad product with terrible features. I am very unhappy with it.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('NEGATIVE', $result->label);
        Assert::assertGreaterThan(0, $result->score);
    }

    /** @test */
    public function it_analyzes_neutral_sentiment_correctly(): void
    {
        $text = 'This is a product with some features. I have mixed feelings about it.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertContains($result->label, ['POSITIVE', 'NEGATIVE']);
    }

    /** @test */
    public function it_handles_empty_text(): void
    {
        $text = '';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('NEGATIVE', $result->label);
        Assert::assertSame(0, $result->score);
    }

    /** @test */
    public function it_handles_text_with_only_positive_words(): void
    {
        $text = 'good great excellent positive happy';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
        Assert::assertSame(1.0, $result->score);
    }

    /** @test */
    public function it_handles_text_with_only_negative_words(): void
    {
        $text = 'bad poor terrible negative unhappy';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('NEGATIVE', $result->label);
        Assert::assertSame(1.0, $result->score);
    }

    /** @test */
    public function it_handles_text_with_mixed_sentiment(): void
    {
        $text = 'This product is good but has some bad aspects. Overall I am happy but also concerned.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertContains($result->label, ['POSITIVE', 'NEGATIVE']);
    }

    /** @test */
    public function it_handles_case_insensitive_sentiment_analysis(): void
    {
        $text = 'This is a GREAT product with EXCELLENT features. I am VERY HAPPY with it.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_special_characters(): void
    {
        $text = 'This is a great product! I am very happy with it. :)';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_numbers(): void
    {
        $text = 'I rate this product 5 out of 5. It is excellent and I am very happy.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_punctuation(): void
    {
        $text = 'This product is terrible!!! I am very unhappy with it...';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('NEGATIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_multiple_sentences(): void
    {
        $text = 'This is a great product. I am very happy with it. The features are excellent.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_technical_terms(): void
    {
        $text = 'The API integration is good. The documentation is excellent. I am happy with the performance.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_emotions(): void
    {
        $text = 'I feel great about this decision. I am so happy and excited. This is wonderful news.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_negations(): void
    {
        $text = 'This is not a good product. I am not happy with it.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('NEGATIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_intensifiers(): void
    {
        $text = 'This is extremely good. I am very very happy. The features are absolutely excellent.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_comparisons(): void
    {
        $text = 'This product is better than the previous one. I am happier now.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_questions(): void
    {
        $text = 'Is this a good product? I am happy but also wondering about the quality.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertContains($result->label, ['POSITIVE', 'NEGATIVE']);
    }

    /** @test */
    public function it_handles_text_with_quotes(): void
    {
        $text = 'The customer said "This is excellent!" and I agree completely.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_abbreviations(): void
    {
        $text = 'This is gr8! I am v happy with it. The features are excellent.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_foreign_words(): void
    {
        $text = 'This product is bon (good in French). I am molto felice (very happy in Italian).';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_technical_acronyms(): void
    {
        $text = 'The API is good. The UI/UX is excellent. I am happy with the MVP.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_measurements(): void
    {
        $text = 'The 100% uptime is excellent. The 5-star rating is great. I am very happy.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_time_expressions(): void
    {
        $text = 'I am happy today. Yesterday was great. Tomorrow will be excellent.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    /** @test */
    public function it_handles_text_with_conditional_statements(): void
    {
        $text = 'If this works, I will be happy. The current state is good.';

        $result = $this->action->execute($text);

        Assert::assertInstanceOf(SentimentData::class, $result);
        Assert::assertSame('POSITIVE', $result->label);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
