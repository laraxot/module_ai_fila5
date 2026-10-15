<?php
namespace Modules\AI\Tests\Unit;

use Modules\AI\Tests\TestCase;
use Modules\AI\Models\AiToolLog;

it('has main model class', function () {
    expect(class_exists(AiToolLog::class))->toBeTrue();
});
