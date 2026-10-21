<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Service;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Statuts d'un challenge (colonne `status`).
 */
final class Status
{
    public const DRAFT     = 0;
    public const PENDING   = 1;
    public const PUBLISHED = 2;
    public const REFUSED   = 3;
    public const CANCELLED = 4;
    public const ARCHIVED  = 5;

    /** Clé de langue et classe de badge Bootstrap par statut. */
    private const META = [
        self::DRAFT     => ['COM_CHALLENGES_STATUS_DRAFT', 'bg-secondary'],
        self::PENDING   => ['COM_CHALLENGES_STATUS_PENDING', 'bg-warning text-dark'],
        self::PUBLISHED => ['COM_CHALLENGES_STATUS_PUBLISHED', 'bg-success'],
        self::REFUSED   => ['COM_CHALLENGES_STATUS_REFUSED', 'bg-danger'],
        self::CANCELLED => ['COM_CHALLENGES_STATUS_CANCELLED', 'bg-dark'],
        self::ARCHIVED  => ['COM_CHALLENGES_STATUS_ARCHIVED', 'bg-info text-dark'],
    ];

    /** @return int[] */
    public static function all(): array
    {
        return array_keys(self::META);
    }

    public static function isValid(int $status): bool
    {
        return isset(self::META[$status]);
    }

    public static function langKey(int $status): string
    {
        return self::META[$status][0] ?? 'COM_CHALLENGES_STATUS_UNKNOWN';
    }

    public static function badgeClass(int $status): string
    {
        return self::META[$status][1] ?? 'bg-secondary';
    }
}
