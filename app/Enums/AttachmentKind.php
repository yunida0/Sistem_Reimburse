<?php

namespace App\Enums;

enum AttachmentKind: string
{
    case Receipt      = 'receipt';
    case PaymentProof = 'payment_proof';

    public function label(): string
    {
        return match($this) {
            self::Receipt      => 'Bukti Transaksi',
            self::PaymentProof => 'Bukti Pembayaran',
        };
    }
}
