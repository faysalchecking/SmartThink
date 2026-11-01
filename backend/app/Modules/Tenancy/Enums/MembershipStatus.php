<?php

namespace App\Modules\Tenancy\Enums;

enum MembershipStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Deactivated = 'deactivated';
}
