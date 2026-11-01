<?php

namespace App\Modules\Tenancy\Enums;

enum CompanyStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => $target === self::Active,
            self::Active => $target === self::Suspended,
            self::Suspended => $target === self::Active,
        };
    }
}
