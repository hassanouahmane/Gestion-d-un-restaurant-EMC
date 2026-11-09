<?php

namespace App\Service;

final class Unites
{
    private const UNITES = [
        'g' => ['masse', 1], 'kg' => ['masse', 1000],
        'ml' => ['volume', 1], 'cl' => ['volume', 10], 'l' => ['volume', 1000],
        'piece' => ['piece', 1], 'portion' => ['portion', 1],
    ];

    public static function convertir(float $quantite, string $depart, string $arrivee): float
    {
        $normaliser = static fn (string $unite): string => strtr(mb_strtolower(trim($unite)), ['pièce' => 'piece', 'litre' => 'l']);
        $source = self::UNITES[$normaliser($depart)] ?? null;
        $cible = self::UNITES[$normaliser($arrivee)] ?? null;
        if ($source === null || $cible === null || $source[0] !== $cible[0]) {
            throw new \DomainException("Unités incompatibles ($depart, $arrivee).");
        }

        return $quantite * $source[1] / $cible[1];
    }

    public static function decimal(float $nombre, int $precision = 4): string
    {
        return number_format($nombre, $precision, '.', '');
    }
}
