<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions;

use Modules\AI\Actions\Predict\GeneratePredictionDraftsAction;
use Modules\AI\Tests\TestCase;

class GeneratePredictionDraftsActionTest extends TestCase
{
    public function test_it_returns_fallback_drafts_when_openai_api_key_is_missing(): void
    {
        config()->set('openai.api_key', null);

        $drafts = (new GeneratePredictionDraftsAction)->execute(3);

        $this->assertCount(3, $drafts);
        $this->assertSame(['title', 'subtitle', 'description', 'category', 'tags', 'analysis', 'event_end_date', 'liquidity', 'options'], array_keys($drafts[0]));
        $this->assertNotEmpty($drafts[0]['tags']);
        $this->assertNotEmpty($drafts[0]['options']);
    }
}
