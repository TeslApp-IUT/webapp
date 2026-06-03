<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Vehicle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\TeslaApi\AccessTokenProviderInterface;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\TeslaModel;
use Teslapp\Models\Vehicle\TeslaModelRepositoryInterface;
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
            Vehicle::fromTeslaResponse(['vin' => '5YJ3E1EA7KF000316', 'display_name' => 'Ma Model 3']),
        ]);

        $vehicleRepo = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepo->method('findByUser')->willReturn([]); // database empty
        $vehicleRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Vehicle $v): bool =>
                $v->vin->value === '5YJ3E1EA7KF000316'
                && $v->userId === 'user-1'
                && $v->modelId === 'model-3-id'
                && $v->name === 'Ma Model 3'));
        $vehicleRepo->expects($this->never())->method('deleteByVin');

        $modelRepo = $this->createMock(TeslaModelRepositoryInterface::class);
        $modelRepo->method('findAll')->willReturn([new TeslaModel('model-3-id', 'Model 3')]);

        $this->makeService($api, $vehicleRepo, $modelRepo)->syncUserVehicles('user-1');
    }

    #[Test]
    public function deletesVehiclesInDatabaseButGoneFromTheApi(): void
    {
        $api = $this->createMock(VehicleStateClient::class);
        $api->method('listVehicles')->willReturn([]); // API empty

        $existing = new Vehicle(new Vin('5YJ3E1EA7KF000316'), 'user-1', 'Ma Model 3', 'model-3-id');
        $vehicleRepo = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepo->method('findByUser')->willReturn([$existing]);
        $vehicleRepo->expects($this->once())
            ->method('deleteByVin')
            ->with($this->callback(static fn (Vin $vin): bool => $vin->value === '5YJ3E1EA7KF000316'));
        $vehicleRepo->expects($this->never())->method('save');

        $modelRepo = $this->createMock(TeslaModelRepositoryInterface::class);

        $this->makeService($api, $vehicleRepo, $modelRepo)->syncUserVehicles('user-1');
    }

    #[Test]
    public function listForUserReturnsTheUsersVehicles(): void
    {
        $vehicles = [new Vehicle(new Vin('5YJ3E1EA7KF000316'), 'user-1', 'Ma Model 3', 'model-3-id')];

        $vehicleRepo = $this->createMock(VehicleRepositoryInterface::class);
        $vehicleRepo->method('findByUser')->with('user-1')->willReturn($vehicles);

        $service = $this->makeService(
            $this->createMock(VehicleStateClient::class),
            $vehicleRepo,
            $this->createMock(TeslaModelRepositoryInterface::class),
        );

        self::assertSame($vehicles, $service->listForUser('user-1'));
    }

    private function makeService(
        VehicleStateClient $api,
        VehicleRepositoryInterface $vehicleRepo,
        TeslaModelRepositoryInterface $modelRepo,
    ): VehicleService {
        $token = $this->createMock(AccessTokenProviderInterface::class);
        $token->method('getValidAccessToken')->willReturn(new AccessToken('tok'));

        return new VehicleService($api, $token, $vehicleRepo, $modelRepo);
    }
}
