<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Table;

use Fede\Component\Challenges\Administrator\Service\ReferenceService;
use Fede\Component\Challenges\Administrator\Service\Status;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class ChallengeTable extends Table
{
    protected $_supportNullValue = true;

    /** Champs facultatifs : une chaîne vide est enregistrée NULL. */
    private const NULLABLE = [
        'venue', 'registration_deadline', 'formula', 'conditions', 'fee', 'max_teams',
        'contact_email', 'contact_phone', 'declarant_email', 'declarant_phone', 'description',
    ];

    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__challenges', 'id', $db, $dispatcher);
    }

    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        // La société (liste déroulante des associations fédérées) impose l'organisateur et le secteur.
        $societeId = (int) $this->societe_id;

        if ($societeId < 1) {
            $this->setError(Text::_('COM_CHALLENGES_ERROR_SOCIETE_REQUIRED'));

            return false;
        }

        try {
            $societe = (new ReferenceService($this->_db))->getSociete($societeId);
        } catch (\RuntimeException $e) {
            $this->setError($e->getMessage());

            return false;
        }

        if (!$societe) {
            $this->setError(Text::_('COM_CHALLENGES_ERROR_SOCIETE_NOT_FOUND'));

            return false;
        }

        $this->organizer  = $societe->nom;
        $this->secteur_id = (int) $societe->secteur_id;

        if (trim((string) $this->commune) === '') {
            $this->commune = $societe->commune;
        }

        foreach (['title', 'organizer', 'commune', 'contact_name', 'declarant_name'] as $field) {
            $this->$field = trim((string) $this->$field);

            if ($this->$field === '') {
                $this->setError(Text::sprintf('COM_CHALLENGES_ERROR_FIELD_REQUIRED', Text::_('COM_CHALLENGES_FIELD_' . strtoupper($field) . '_LABEL')));

                return false;
            }
        }

        foreach (self::NULLABLE as $field) {
            if (isset($this->$field) && \is_string($this->$field) && trim($this->$field) === '') {
                $this->$field = null;
            }
        }

        if ((int) $this->secteur_id < 1 || (int) $this->type_id < 1) {
            $this->setError(Text::_('COM_CHALLENGES_ERROR_SECTEUR_TYPE_REQUIRED'));

            return false;
        }

        if (empty($this->start_datetime)) {
            $this->setError(Text::_('COM_CHALLENGES_ERROR_START_REQUIRED'));

            return false;
        }

        if (!empty($this->registration_deadline) && $this->registration_deadline > $this->start_datetime) {
            $this->setError(Text::_('COM_CHALLENGES_ERROR_DEADLINE_AFTER_START'));

            return false;
        }

        foreach (['contact_email', 'declarant_email'] as $field) {
            if (!empty($this->$field) && !filter_var($this->$field, FILTER_VALIDATE_EMAIL)) {
                $this->setError(Text::_('COM_CHALLENGES_ERROR_EMAIL_INVALID'));

                return false;
            }
        }

        if ($this->fee !== null && (!is_numeric($this->fee) || (float) $this->fee < 0)) {
            $this->setError(Text::_('COM_CHALLENGES_ERROR_FEE_INVALID'));

            return false;
        }

        if ($this->max_teams !== null && (!is_numeric($this->max_teams) || (int) $this->max_teams < 1)) {
            $this->setError(Text::_('COM_CHALLENGES_ERROR_MAX_TEAMS_INVALID'));

            return false;
        }

        if (!Status::isValid((int) $this->status)) {
            $this->status = Status::PENDING;
        }

        // Alias d'URL : dérivé du titre si vide, unique dans la table.
        $alias = ApplicationHelper::stringURLSafe((string) $this->alias, '');

        if ($alias === '') {
            $alias = ApplicationHelper::stringURLSafe($this->title, '');
        }

        if ($alias === '') {
            $alias = 'challenge-' . Factory::getDate($this->start_datetime)->format('Y-m-d');
        }

        $this->alias = $this->uniqueAlias($alias);

        return true;
    }

    public function store($updateNulls = true)
    {
        $date = Factory::getDate()->toSql();

        if (empty($this->id)) {
            if (empty($this->created)) {
                $this->created = $date;
            }
        } else {
            $this->modified = $date;
        }

        return parent::store($updateNulls);
    }

    /**
     * Supprime le challenge et son historique (aucune donnée orpheline).
     */
    public function delete($pk = null)
    {
        $pk = $pk ?? $this->{$this->_tbl_key};

        if (!parent::delete($pk)) {
            return false;
        }

        $id    = (int) $pk;
        $query = $this->_db->getQuery(true)
            ->delete($this->_db->quoteName('#__challenges_history'))
            ->where($this->_db->quoteName('challenge_id') . ' = :cid')
            ->bind(':cid', $id, ParameterType::INTEGER);

        $this->_db->setQuery($query)->execute();

        return true;
    }

    private function uniqueAlias(string $alias): string
    {
        $base      = $alias;
        $id        = (int) $this->id;
        $candidate = $alias;
        $i         = 1;

        while (true) {
            $query = $this->_db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->_db->quoteName('#__challenges'))
                ->where($this->_db->quoteName('alias') . ' = :alias')
                ->where($this->_db->quoteName('id') . ' <> :id')
                ->bind(':alias', $candidate)
                ->bind(':id', $id, ParameterType::INTEGER);

            if ((int) $this->_db->setQuery($query)->loadResult() === 0) {
                return $candidate;
            }

            $i++;
            $candidate = $base . '-' . $i;
        }
    }
}
