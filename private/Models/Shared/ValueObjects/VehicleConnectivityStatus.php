<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\ValueObjects;

/**
 * Live connectivity state of a vehicle, from the Tesla API `state` field.
 */
enum VehicleConnectivityStatus: string
{
    case Online = 'online';
    case Asleep = 'asleep';
    case Offline = 'offline';
    case Unknown = 'unknown';

    public static function fromApiState(string $state): self
    {
        return self::tryFrom(strtolower($state)) ?? self::Unknown;
    }

    public function label(): string
    {
        return match ($this) {
            self::Online => 'En ligne',
            self::Asleep => 'En veille',
            self::Offline => 'Hors ligne',
            self::Unknown => 'Statut inconnu',
        };
    }
}
