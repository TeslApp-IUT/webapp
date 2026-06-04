<?php

declare(strict_types=1);

namespace Teslapp\Models\Vehicle;

interface TeslaModelRepositoryInterface
{
    /** @return TeslaModel[] */
    public function findAll(): array;

    public function findById(string $id): ?TeslaModel;
}
