<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Model;

use Fede\Component\Challenges\Administrator\Service\ReferenceService;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Liste des challenges (administration) avec filtres statut, secteur, type et recherche.
 */
class ChallengesModel extends ListModel
{
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'organizer', 'a.organizer',
                'commune', 'a.commune',
                'status', 'a.status',
                'start_datetime', 'a.start_datetime',
                'created', 'a.created',
                'secteur_id', 'a.secteur_id',
                'type_id', 'a.type_id',
            ];
        }

        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = 'a.start_datetime', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.status');
        $id .= ':' . $this->getState('filter.secteur_id');
        $id .= ':' . $this->getState('filter.type_id');

        return parent::getStoreId($id);
    }

    public function getItems()
    {
        $items = parent::getItems();

        if (empty($items)) {
            return $items;
        }

        // Libellés des secteurs : lecture de la table externe `secteurs` (jamais de jointure sur une autre collation).
        try {
            $labels = (new ReferenceService($this->getDatabase()))->secteurLabels();
        } catch (\RuntimeException) {
            $labels = [];
        }

        foreach ($items as $item) {
            $item->secteur_title = $labels[(int) $item->secteur_id] ?? '';
        }

        return $items;
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(
            $db->quoteName(
                [
                    'a.id', 'a.title', 'a.alias', 'a.organizer', 'a.commune', 'a.venue',
                    'a.secteur_id', 'a.type_id', 'a.start_datetime', 'a.status',
                    'a.contact_name', 'a.created', 'a.modified',
                ]
            )
        )
            ->select($db->quoteName('t.title', 'type_title'))
            ->from($db->quoteName('#__challenges', 'a'))
            ->join('LEFT', $db->quoteName('#__challenges_types', 't'), $db->quoteName('t.id') . ' = ' . $db->quoteName('a.type_id'));

        // Filtre par statut.
        $status = (string) $this->getState('filter.status');

        if (is_numeric($status)) {
            $statusInt = (int) $status;
            $query->where($db->quoteName('a.status') . ' = :status')
                ->bind(':status', $statusInt, ParameterType::INTEGER);
        }

        // Filtre par secteur.
        if ($secteur = (int) $this->getState('filter.secteur_id')) {
            $query->where($db->quoteName('a.secteur_id') . ' = :secteur')
                ->bind(':secteur', $secteur, ParameterType::INTEGER);
        }

        // Filtre par type de concours.
        if ($type = (int) $this->getState('filter.type_id')) {
            $query->where($db->quoteName('a.type_id') . ' = :type')
                ->bind(':type', $type, ParameterType::INTEGER);
        }

        // Recherche : titre, société, commune, contact ; « id:123 » pour un identifiant.
        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $searchId = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :searchid')
                    ->bind(':searchid', $searchId, ParameterType::INTEGER);
            } else {
                $like = '%' . str_replace(' ', '%', $search) . '%';
                $query->where(
                    '(' . $db->quoteName('a.title') . ' LIKE :s1'
                    . ' OR ' . $db->quoteName('a.organizer') . ' LIKE :s2'
                    . ' OR ' . $db->quoteName('a.commune') . ' LIKE :s3'
                    . ' OR ' . $db->quoteName('a.contact_name') . ' LIKE :s4)'
                )
                    ->bind([':s1', ':s2', ':s3', ':s4'], $like);
            }
        }

        $ordering  = $this->getState('list.ordering', 'a.start_datetime');
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $query->order($db->quoteName($db->escape($ordering)) . ' ' . $direction);

        return $query;
    }
}
