<?php

namespace App\Modules\Tenancy\Enums;

enum CompanyRole: string
{
    case CompanyAdmin = 'company_admin';
    case Employee = 'employee';

    public function isCompanyAdmin(): bool
    {
        return $this === self::CompanyAdmin;
    }
}
