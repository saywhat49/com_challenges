<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Service;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Historique minimal : qui a fait quoi, et quand, sur une annonce.
 */
class HistoryService
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * Enregistre une ligne d'historique.
     *
     * @param   int          $challengeId  Identifiant du challenge.
     * @param   string       $action       created, edited, date_changed, published, refused, cancelled, archived, resubmitted…
     * @param   int|null     $userId       Utilisateur Joomla (null = déclarant public).
     * @param   array        $changes      Modifications {champ: [ancien, nouveau]}. Pas de données personnelles en clair.
     * @param   string       $note         Motif ou commentaire.
     */
    public function log(int $challengeId, string $action, ?int $userId, array $changes = [], string $note = ''): void
    {
        $now     = Factory::getDate()->toSql();
        $json    = $changes === [] ? null : json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $noteVal = $note === '' ? null : $note;

        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__challenges_history'))
            ->columns($this->db->quoteName(['challenge_id', 'action', 'user_id', 'changed_at', 'changes', 'note']))
            ->values(':cid, :action, :uid, :at, :changes, :note')
            ->bind(':cid', $challengeId, ParameterType::INTEGER)
            ->bind(':action', $action)
            ->bind(':uid', $userId, $userId === null ? ParameterType::NULL : ParameterType::INTEGER)
            ->bind(':at', $now)
            ->bind(':changes', $json, $json === null ? ParameterType::NULL : ParameterType::STRING)
            ->bind(':note', $noteVal, $noteVal === null ? ParameterType::NULL : ParameterType::STRING);

        $this->db->setQuery($query)->execute();
    }

    /**
     * Historique d'un challenge, du plus récent au plus ancien.
     *
     * @return  object[]
     */
    public function forChallenge(int $challengeId): array
    {
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('h.id'),
                $this->db->quoteName('h.action'),
                $this->db->quoteName('h.user_id'),
                $this->db->quoteName('h.changed_at'),
                $this->db->quoteName('h.changes'),
                $this->db->quoteName('h.note'),
                $this->db->quoteName('u.name', 'user_name'),
            ])
            ->from($this->db->quoteName('#__challenges_history', 'h'))
            ->join('LEFT', $this->db->quoteName('#__users', 'u'), $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('h.user_id'))
            ->where($this->db->quoteName('h.challenge_id') . ' = :cid')
            ->bind(':cid', $challengeId, ParameterType::INTEGER)
            ->order($this->db->quoteName('h.changed_at') . ' DESC, ' . $this->db->quoteName('h.id') . ' DESC');

        return $this->db->setQuery($query)->loadObjectList() ?: [];
    }
}
