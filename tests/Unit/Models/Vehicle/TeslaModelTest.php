<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Vehicle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Vehicle\TeslaModel;

#[CoversClass(TeslaModel::class)]
final class TeslaModelTest extends TestCase
{
    #[Test]
    public function buildsFromADatabaseRow(): void
    {
        $model = TeslaModel::fromRow(['id' => 'model-3-id', 'name' => 'Model 3']);

        self::assertSame('model-3-id', $model->id);
        self::assertSame('Model 3', $model->name);
    }
}
