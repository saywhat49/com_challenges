<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Model;

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class TypesModel extends ListModel
{
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'a.id', 'title', 'a.title', 'published', 'a.published', 'ordering', 'a.ordering'];
        }

        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');

        return parent::getStoreId($id);
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.ordering', 'a.published']))
            ->select('(SELECT COUNT(*) FROM ' . $db->quoteName('#__challenges', 'c') . ' WHERE ' . $db->quoteName('c.type_id') . ' = ' . $db->quoteName('a.id') . ') AS ' . $db->quoteName('challenge_count'))
            ->from($db->quoteName('#__challenges_types', 'a'));

        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $publishedInt = (int) $published;
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $publishedInt, ParameterType::INTEGER);
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $query->where($db->quoteName('a.title') . ' LIKE :search')
                ->bind(':search', $like);
        }

        $ordering  = $this->getState('list.ordering', 'a.ordering');
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $query->order($db->quoteName($db->escape($ordering)) . ' ' . $direction);

        return $query;
    }
}
