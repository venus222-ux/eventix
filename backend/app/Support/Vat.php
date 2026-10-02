<?php
namespace App\Support;

final class Vat
{
    public static function split(int $grossCents, float $ratePercent): array
    {
        $vat = (int) round($grossCents - $grossCents / (1 + $ratePercent / 100));

        return ['subtotal_cents' => $grossCents - $vat, 'vat_cents' => $vat];
    }
}