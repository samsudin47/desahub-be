<?php

namespace Shared\Constants;

class AvailableRoleConstantsHelper
{
    const SUPERADMIN = 'SUPERADMIN';
    const SUPERADMIN_DESC = 'Super admin, can be do anything';

    const ADMIN = 'ADMIN';
    const ADMIN_DESC = 'Admin, can be do anything for human capital';

    const WARGA = 'WARGA';
    const WARGA_DESC = 'Warga, can be do anything for feed';

    const USER = 'USER';
    const USER_DESC = 'User, can be do anything for info';

    const MAPPING_AVAILABLE_ROLE_AVA_ITMS = [
        'Superadmin' => self::SUPERADMIN,
        'Admin' => self::ADMIN,
        'Warga' => self::WARGA,
        'User' => self::USER,
    ];
}
