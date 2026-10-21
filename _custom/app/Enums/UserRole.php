<?php

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case Approver = 'approver';
    case Finance  = 'finance';
    case Cashier  = 'cashier';
    case Admin    = 'admin';

    public function label(): string
    {
        return match($this) {
            self::Employee => 'Pegawai',
            self::Approver => 'Atasan',
            self::Finance  => 'Finance',
            self::Cashier  => 'Kasir',
            self::Admin    => 'Admin',
        };
    }
}
