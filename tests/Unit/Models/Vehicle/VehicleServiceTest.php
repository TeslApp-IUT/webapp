<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Vehicle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Shared\ValueObjects\VehicleConnectivityStatus;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\Vehicle;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;
use Teslapp\Models\Vehicle\VehicleService;

#[CoversClass(VehicleService::class)]
final class VehicleServiceTest extends TestCase
{
    #[Test]
    public function savesVehiclesPresentInTheApiButNotInDatabase(): void
    {
        $api = $this->createMock(VehicleStateClient::class);
        $api->method('listVehicles')->willReturn([
            Vehicle::fromTeslaResponse([
                'vin' => '5YJ3E1EA7KF000316',
                'display_name' => 'Ma Model 3',
            ]),
        ]);

        $vehicleRepo = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepo->method('findByUser')->willReturn([]); // database empty
        $vehicleRepo
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(
                    // model_code is the 4th VIN character ('3' for a Model 3).
                    static fn(Vehicle $v): bool => $v->vin->value === '5YJ3E1EA7KF000316' &&
                        $v->userId === 'user-1' &&
                        $v->modelCode === '3' &&
                        $v->name === 'Ma Model 3',
                ),
            );
        $vehicleRepo->expects($this->never())->method('detachByVin');

        $this->makeService($api, $vehicleRepo)->syncUserVehicles('user-1');
    }

    #[Test]
    public function detachesVehiclesInDatabaseButGoneFromTheApi(): void
    {
        $api = $this->createMock(VehicleStateClient::class);
        $api->method('listVehicles')->willReturn([]); // API empty

        $existing = new Vehicle(new Vin('5YJ3E1EA7KF000316'), 'user-1', 'Ma Model 3', '3');
        $vehicleRepo = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepo->method('findByUser')->willReturn([$existing]);
        $vehicleRepo
            ->expects($this->once())
            ->method('detachByVin')
            ->with(
                $this->callback(static fn(Vin $vin): bool => $vin->value === '5YJ3E1EA7KF000316'),
            );
        $vehicleRepo->expects($this->never())->method('save');

        $this->makeService($api, $vehicleRepo)->syncUserVehicles('user-1');
    }

    #[Test]
    public function listForUserReturnsTheUsersVehicles(): void
    {
        $vehicles = [new Vehicle(new Vin('5YJ3E1EA7KF000316'), 'user-1', 'Ma Model 3', '3')];

        $vehicleRepo = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepo->method('findByUser')->with('user-1')->willReturn($vehicles);

        $service = $this->makeService($this->createMock(VehicleStateClient::class), $vehicleRepo);

        self::assertSame($vehicles, $service->listForUser('user-1'));
    }

    #[Test]
    public function connectivityForUserReturnsStatusesFromTheApi(): void
    {
        $api = $this->createMock(VehicleStateClient::class);
        $api->expects($this->once())
            ->method('fetchConnectivity')
            ->willReturn([
                '5YJ3E1EA7KF000316' => VehicleConnectivityStatus::Online,
            ]);

        $service = $this->makeService($api, $this->createMock(VehicleRepositoryInterface::class));

        self::assertSame(
            ['5YJ3E1EA7KF000316' => VehicleConnectivityStatus::Online],
            $service->connectivityForUser(),
        );
    }

    #[Test]
    #[DataProvider('modelNames')]
    public function modelNameForVinDerivesTheModel(string $vin, string $expected): void
    {
        $service = $this->makeService(
            $this->createMock(VehicleStateClient::class),
            $this->createMock(VehicleRepositoryInterface::class),
        );

        self::assertSame($expected, $service->modelNameForVin(new Vin($vin)));
    }

    /** @return array<string, array{string, string}> */
    public static function modelNames(): array
    {
        return [
            'Model 3' => ['5YJ3E1EA7KF000316', 'Model 3'],
            'Model Y' => ['5YJYGDEE9MF000002', 'Model Y'],
            'Model S' => ['5YJSA1E40MF000003', 'Model S'],
            'Model X' => ['7SAXCBE60PF000004', 'Model X'],
            'Cybertruck' => ['7G2CEHED0RA000005', 'Cybertruck'],
            'unknown line falls back to Tesla' => ['5YJ1E1EA7KF000099', 'Tesla'],
        ];
    }

    private function makeService(
        VehicleStateClient $api,
        VehicleRepositoryInterface $vehicleRepo,
    ): VehicleService {
        return new VehicleService($api, $vehicleRepo);
    }
}
