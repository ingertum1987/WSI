<?php

namespace App\Service\Enum;

class MainMenu
{
    const PROFILE = 'profile';
    const BALANCE = 'balance';
    const PARTNER = 'partner';
    const STATISTICS = 'statistics';
    const DOCUMENTS = 'documents';
    const CONTACTS = 'contacts';
    const TRANSACTIONS = 'transactions';
    const INVESTORS = 'investors';
    const SETTINGS = 'settings';
    const LOGOUT = 'logout';
    const ITEMS = [
        self::PROFILE => [
            'icon' => 'icon-speedometer',
            'route' => 'cabinet_user_profile_index'
        ],
        self::BALANCE => [
            'icon' => 'ti-money',
            'route' => 'cabinet_user_balance_index'
        ],
        self::PARTNER => [
            'icon' => 'icon-people',
            'route' => 'user_partner'
        ],
        self::STATISTICS => [
            'icon' => 'ti-pie-chart',
            'route' => 'user_statistics'
        ],
        self::DOCUMENTS => [
            'icon' => 'ti-files',
            'route' => 'cabinet_user_document_index'
        ],
        self::CONTACTS => [
            'icon' => 'ti-email',
            'route' => 'user_contacts'
        ],
        self::TRANSACTIONS => [
            'icon' => 'ti-settings',
            'route' => 'user_admin_transactions',
            'isAdmin' => true
        ],
        self::INVESTORS => [
            'icon' => 'ti-user',
            'route' => 'user_admin_investors',
            'isAdmin' => true
        ],
        self::SETTINGS => [
            'icon' => 'fa fa-gears',
            'route' => 'user_admin_settings',
            'isAdmin' => true
        ],
        self::LOGOUT => [
            'icon' => 'fa fa-power-off',
            'route' => 'logout'
        ],
    ];
}
