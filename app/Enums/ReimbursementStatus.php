<?php

namespace App\Enums;

enum ReimbursementStatus: string
{
    case Draft     = 'DRAFT';
    case Submitted = 'SUBMITTED';
    case Revision  = 'REVISION';
    case Approved  = 'APPROVED';
    case Verified  = 'VERIFIED';
    case Paid      = 'PAID';
    case Rejected  = 'REJECTED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Draf',
            self::Submitted => 'Menunggu Atasan',
            self::Revision  => 'Perlu Revisi',
            self::Approved  => 'Menunggu Finance',
            self::Verified  => 'Menunggu Pembayaran',
            self::Paid      => 'Dibayar',
            self::Rejected  => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'slate',
            self::Submitted => 'blue',
            self::Revision  => 'amber',
            self::Approved  => 'indigo',
            self::Verified  => 'cyan',
            self::Paid      => 'emerald',
            self::Rejected  => 'red',
            self::Cancelled => 'gray',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Rejected, self::Cancelled]);
    }

    public function showAging(): bool
    {
        return in_array($this, [self::Submitted, self::Approved, self::Verified, self::Revision]);
    }
}
