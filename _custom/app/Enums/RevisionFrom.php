<?php

namespace App\Enums;

enum RevisionFrom: string
{
    case Approver = 'approver';
    case Finance  = 'finance';

    public function label(): string
    {
        return match($this) {
            self::Approver => 'Atasan',
            self::Finance  => 'Finance',
        };
    }
}
