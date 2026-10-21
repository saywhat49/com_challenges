<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Service;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Workflow de validation : transitions autorisées, permissions requises, traçabilité.
 *
 * La transition est appliquée par un UPDATE conditionnel (statut d'origine dans la clause WHERE),
 * ce qui évite qu'un double clic ou deux contrôleurs simultanés appliquent deux fois la même action.
 */
class WorkflowService
{
    /**
     * from       : statuts d'origine autorisés
     * to         : statut cible
     * permission : action ACL requise (com_challenges)
     * reason     : motif obligatoire
     * history    : action enregistrée dans l'historique
     */
    public const TRANSITIONS = [
        'validate' => [
            'from' => [Status::PENDING],
            'to' => Status::PUBLISHED,
            'permission' => 'challenges.validate',
            'reason' => false,
            'history' => 'published',
        ],
        'refuse' => [
            'from' => [Status::PENDING],
            'to' => Status::REFUSED,
            'permission' => 'challenges.validate',
            'reason' => true,
            'history' => 'refused',
        ],
        'cancel' => [
            'from' => [Status::PUBLISHED],
            'to' => Status::CANCELLED,
            'permission' => 'core.edit.state',
            'reason' => true,
            'history' => 'cancelled',
        ],
        'archive' => [
            'from' => [Status::PUBLISHED, Status::CANCELLED],
            'to' => Status::ARCHIVED,
            'permission' => 'core.edit.state',
            'reason' => false,
            'history' => 'archived',
        ],
        'resubmit' => [
            'from' => [Status::DRAFT, Status::REFUSED],
            'to' => Status::PENDING,
            'permission' => 'core.edit',
            'reason' => false,
            'history' => 'resubmitted',
        ],
    ];

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly HistoryService $history
    ) {
    }

    public static function exists(string $transition): bool
    {
        return isset(self::TRANSITIONS[$transition]);
    }

    public static function requiresReason(string $transition): bool
    {
        return (bool) (self::TRANSITIONS[$transition]['reason'] ?? false);
    }

    /**
     * Indique si l'utilisateur peut appliquer la transition à un challenge dans ce statut.
     */
    public function canApply(string $transition, User $user, int $currentStatus): bool
    {
        $def = self::TRANSITIONS[$transition] ?? null;

        if ($def === null || !\in_array($currentStatus, $def['from'], true)) {
            return false;
        }

        return (bool) $user->authorise($def['permission'], 'com_challenges');
    }

    /**
     * Applique une transition.
     *
     * @throws  \InvalidArgumentException  Transition inconnue.
     * @throws  \RuntimeException          Droit insuffisant, motif manquant ou statut incompatible.
     */
    public function apply(string $transition, int $id, User $user, string $reason = ''): void
    {
        $def = self::TRANSITIONS[$transition] ?? null;

        if ($def === null) {
            throw new \InvalidArgumentException('Transition inconnue : ' . $transition);
        }

        if (!$user->authorise($def['permission'], 'com_challenges')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'));
        }

        $reason = trim($reason);

        if ($def['reason'] && $reason === '') {
            throw new \RuntimeException(Text::_('COM_CHALLENGES_ERROR_REASON_REQUIRED'));
        }

        $now    = Factory::getDate()->toSql();
        $userId = (int) $user->id;
        $to     = (int) $def['to'];

        // Valeurs liées (par référence dans la boucle ci-dessous).
        $values = [
            'status'      => [$to, ParameterType::INTEGER],
            'modified'    => [$now, ParameterType::STRING],
            'modified_by' => [$userId, ParameterType::INTEGER],
        ];

        if ($transition === 'validate') {
            $values['validated_by'] = [$userId, ParameterType::INTEGER];
            $values['validated_at'] = [$now, ParameterType::STRING];
        } elseif ($transition === 'cancel') {
            $values['cancelled_at'] = [$now, ParameterType::STRING];
        } elseif ($transition === 'archive') {
            $values['archived_at'] = [$now, ParameterType::STRING];
        }

        if ($def['reason']) {
            $values['status_reason'] = [$reason, ParameterType::STRING];
        }

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__challenges'));

        foreach ($values as $column => &$pair) {
            $query->set($this->db->quoteName($column) . ' = :' . $column)
                ->bind(':' . $column, $pair[0], $pair[1]);
        }

        $query->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER)
            ->whereIn($this->db->quoteName('status'), $def['from']);

        $this->db->setQuery($query)->execute();
        unset($pair);

        if ($this->db->getAffectedRows() < 1) {
            throw new \RuntimeException(Text::_('COM_CHALLENGES_ERROR_TRANSITION_NOT_ALLOWED'));
        }

        $this->history->log($id, $def['history'], $userId, [], $reason);
    }
}
