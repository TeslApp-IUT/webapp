<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Shared\TeslaApi;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\Exceptions\VehicleAsleepException;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\TeslaApi\VehicleWaker;
use Teslapp\Models\Shared\ValueObjects\Vin;

#[CoversClass(VehicleWaker::class)]
final class VehicleWakerTest extends TestCase
{
    private const VIN = '5YJ3E1EA7KF000316';

    #[Test]
    public function runsTheCommandOnceAndDoesNotWakeWhenItSucceeds(): void
    {
        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('wakeUp');

        $calls = 0;
        $waker = new VehicleWaker($commands, [0]);
        $waker->runAwake(new Vin(self::VIN), function () use (&$calls): void {
            ++$calls;
        });

        $this->assertSame(1, $calls);
    }

    #[Test]
    public function returnsTheCommandValueWhenItSucceeds(): void
    {
        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('wakeUp');

        $waker = new VehicleWaker($commands, [0]);
        $result = $waker->runAwake(new Vin(self::VIN), fn(): ?int => 42);

        $this->assertSame(42, $result);
    }

    #[Test]
    public function wakesTheVehicleOnceAndRetriesWhenAsleep(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $calls = 0;
        $waker = new VehicleWaker($commands, [0, 0]);
        $result = $waker->runAwake($vin, function () use (&$calls): string {
            if (++$calls === 1) {
                throw new VehicleAsleepException('asleep');
            }

            return 'done';
        });

        $this->assertSame('done', $result);
        $this->assertSame(2, $calls);
    }

    #[Test]
    public function preservesTheReturnValueOfARetriedCommand(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $calls = 0;
        $waker = new VehicleWaker($commands, [0]);
        $result = $waker->runAwake($vin, function () use (&$calls): ?int {
            if (++$calls === 1) {
                throw new VehicleAsleepException('asleep');
            }

            return 1337;
        });

        $this->assertSame(1337, $result);
    }

    #[Test]
    public function givesUpAfterTheLastRetryWhenTheVehicleStaysAsleep(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $calls = 0;
        $waker = new VehicleWaker($commands, [0, 0, 0]);

        try {
            $waker->runAwake($vin, function () use (&$calls): void {
                ++$calls;
                throw new VehicleAsleepException('asleep');
            });
            $this->fail('Expected VehicleAsleepException');
        } catch (VehicleAsleepException) {
            // 1 initial attempt + 3 retries (one per delay), all rejected as asleep.
            $this->assertSame(4, $calls);
        }
    }

    #[Test]
    public function doesNotWakeOnOtherApiErrors(): void
    {
        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('wakeUp');

        $waker = new VehicleWaker($commands, [0]);

        $this->expectException(TeslaApiException::class);
        $waker->runAwake(new Vin(self::VIN), function (): void {
            throw new TeslaApiException('command failed');
        });
    }

    #[Test]
    public function letsANonAsleepErrorBubbleUpFromARetry(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $calls = 0;
        $waker = new VehicleWaker($commands, [0, 0]);

        $this->expectException(TeslaApiException::class);
        $this->expectExceptionMessage('command failed');
        $waker->runAwake($vin, function () use (&$calls): void {
            if (++$calls === 1) {
                throw new VehicleAsleepException('asleep');
            }
            throw new TeslaApiException('command failed');
        });
    }

    #[Test]
    public function stillRetriesOnceWhenConfiguredWithNoDelay(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $calls = 0;
        $waker = new VehicleWaker($commands, []);
        $waker->runAwake($vin, function () use (&$calls): void {
            if (++$calls === 1) {
                throw new VehicleAsleepException('asleep');
            }
        });

        $this->assertSame(2, $calls);
    }
}
