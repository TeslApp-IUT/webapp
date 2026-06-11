<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Shared\TeslaApi;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\Exceptions\VehicleAsleepException;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Shared\TeslaApi\VehicleWaker;
use Teslapp\Models\Shared\ValueObjects\VehicleConnectivityStatus;
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

        $state = $this->createMock(VehicleStateClient::class);
        $state->expects($this->never())->method('fetchConnectivity');

        $calls = 0;
        $waker = new VehicleWaker($commands, $state, [0]);
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

        $waker = new VehicleWaker($commands, $this->createStub(VehicleStateClient::class), [0]);
        $result = $waker->runAwake(new Vin(self::VIN), fn(): ?int => 42);

        $this->assertSame(42, $result);
    }

    #[Test]
    public function wakesAndRetriesOnAFleetApi408WithoutCheckingConnectivity(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        // The 408 already proves the vehicle is asleep: no extra API call.
        $state = $this->createMock(VehicleStateClient::class);
        $state->expects($this->never())->method('fetchConnectivity');

        $calls = 0;
        $waker = new VehicleWaker($commands, $state, [0, 0]);
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
    public function wakesAndRetriesWhenTheProxyErrorHidesASleepingVehicle(): void
    {
        // Real-world case: the signing proxy times out or answers 5xx instead
        // of a clean 408 while the vehicle sleeps. The connectivity check is
        // what reveals the vehicle is asleep.
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $calls = 0;
        $waker = new VehicleWaker($commands, $this->state(VehicleConnectivityStatus::Asleep), [0]);
        $result = $waker->runAwake($vin, function () use (&$calls): string {
            if (++$calls === 1) {
                throw new TeslaApiException('Tesla API network error: timeout');
            }

            return 'done';
        });

        $this->assertSame('done', $result);
        $this->assertSame(2, $calls);
    }

    #[Test]
    public function doesNotWakeWhenTheVehicleIsOnlineAndTheCommandFails(): void
    {
        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('wakeUp');

        $waker = new VehicleWaker($commands, $this->state(VehicleConnectivityStatus::Online), [0]);

        $this->expectException(TeslaApiException::class);
        $this->expectExceptionMessage('command failed');
        $waker->runAwake(new Vin(self::VIN), function (): void {
            throw new TeslaApiException('command failed');
        });
    }

    #[Test]
    public function wakesWhenTheVehicleReportsOffline(): void
    {
        // Deep sleep is often reported as "offline": still worth a wake_up.
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $calls = 0;
        $waker = new VehicleWaker($commands, $this->state(VehicleConnectivityStatus::Offline), [0]);
        $waker->runAwake($vin, function () use (&$calls): void {
            if (++$calls === 1) {
                throw new TeslaApiException('command failed');
            }
        });

        $this->assertSame(2, $calls);
    }

    #[Test]
    public function wakesWhenAPreviousWakeIsAlreadyUnderway(): void
    {
        // A second click while the vehicle boots sees state "waking".
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $calls = 0;
        $waker = new VehicleWaker($commands, $this->state(VehicleConnectivityStatus::Waking), [0]);
        $waker->runAwake($vin, function () use (&$calls): void {
            if (++$calls === 1) {
                throw new TeslaApiException('command failed');
            }
        });

        $this->assertSame(2, $calls);
    }

    #[Test]
    public function surfacesTheOriginalErrorWhenTheVinIsNotListed(): void
    {
        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('wakeUp');

        $state = $this->createMock(VehicleStateClient::class);
        $state->method('fetchConnectivity')->willReturn([]);

        $waker = new VehicleWaker($commands, $state, [0]);

        $this->expectException(TeslaApiException::class);
        $this->expectExceptionMessage('command failed');
        $waker->runAwake(new Vin(self::VIN), function (): void {
            throw new TeslaApiException('command failed');
        });
    }

    #[Test]
    public function surfacesTheOriginalErrorWhenTheConnectivityCheckFails(): void
    {
        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('wakeUp');

        $state = $this->createMock(VehicleStateClient::class);
        $state
            ->method('fetchConnectivity')
            ->willThrowException(new TeslaApiException('listing failed'));

        $waker = new VehicleWaker($commands, $state, [0]);

        $this->expectException(TeslaApiException::class);
        $this->expectExceptionMessage('command failed');
        $waker->runAwake(new Vin(self::VIN), function (): void {
            throw new TeslaApiException('command failed');
        });
    }

    #[Test]
    public function preservesTheReturnValueOfARetriedCommand(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $calls = 0;
        $waker = new VehicleWaker($commands, $this->createStub(VehicleStateClient::class), [0]);
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
        $waker = new VehicleWaker($commands, $this->createStub(VehicleStateClient::class), [
            0,
            0,
            0,
        ]);

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
    public function toleratesProxyErrorsDuringRetriesUntilTheLastAttempt(): void
    {
        // While the vehicle boots, the proxy may keep failing with non-408
        // errors; mid-window retries swallow them, the final attempt decides.
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $calls = 0;
        $waker = new VehicleWaker($commands, $this->createStub(VehicleStateClient::class), [0, 0]);

        try {
            $waker->runAwake($vin, function () use (&$calls): void {
                if (++$calls === 1) {
                    throw new VehicleAsleepException('asleep');
                }
                throw new TeslaApiException('proxy handshake failed');
            });
            $this->fail('Expected TeslaApiException');
        } catch (TeslaApiException $e) {
            $this->assertSame('proxy handshake failed', $e->getMessage());
            $this->assertSame(3, $calls);
        }
    }

    #[Test]
    public function stillRetriesOnceWhenConfiguredWithNoDelay(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $calls = 0;
        $waker = new VehicleWaker($commands, $this->createStub(VehicleStateClient::class), []);
        $waker->runAwake($vin, function () use (&$calls): void {
            if (++$calls === 1) {
                throw new VehicleAsleepException('asleep');
            }
        });

        $this->assertSame(2, $calls);
    }

    private function state(VehicleConnectivityStatus $status): VehicleStateClient
    {
        $state = $this->createMock(VehicleStateClient::class);
        $state->method('fetchConnectivity')->willReturn([self::VIN => $status]);

        return $state;
    }
}
