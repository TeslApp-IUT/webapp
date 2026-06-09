<?php
declare(strict_types=1);

namespace Teslapp\Models\Climate;

use Teslapp\Models\Climate\ValueObjects\KeeperMode;
use Teslapp\Models\Climate\ValueObjects\Temperature;
use Teslapp\Models\Shared\TeslaApi\ClimateClient;
use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\Vin;

final readonly class ClimateService
{
    public function __construct(private ClimateClient $client) {}

    public function activate(Vin $vin, AccessToken $token, ?Temperature $temp = null): void
    {
        $this->client->startClimate($vin, $token, $temp);
    }

    public function deactivate(Vin $vin, AccessToken $token): void
    {
        $this->client->stopClimate($vin, $token);
    }

    public function applyKeeperMode(Vin $vin, AccessToken $token, KeeperMode $mode): void
    {
        $this->client->setKeeperMode($vin, $token, $mode);
    }
}
