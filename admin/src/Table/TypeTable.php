<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Table;

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

class TypeTable extends Table
{
    protected $_supportNullValue = true;

    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__challenges_types', 'id', $db, $dispatcher);
    }

    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        $this->title = trim((string) $this->title);

        if ($this->title === '') {
            $this->setError(Text::_('COM_CHALLENGES_ERROR_TITLE_REQUIRED'));

            return false;
        }

        $alias = ApplicationHelper::stringURLSafe((string) $this->alias, '');

        if ($alias === '') {
            $alias = ApplicationHelper::stringURLSafe($this->title, '');
        }

        if ($alias === '') {
            $alias = 'type';
        }

        $candidate = $alias;
        $i         = 1;
        $id        = (int) $this->id;

        while (true) {
            $query = $this->_db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->_db->quoteName($this->_tbl))
                ->where($this->_db->quoteName('alias') . ' = :alias')
                ->where($this->_db->quoteName('id') . ' <> :id')
                ->bind(':alias', $candidate)
                ->bind(':id', $id, ParameterType::INTEGER);

            if ((int) $this->_db->setQuery($query)->loadResult() === 0) {
                break;
            }

            $i++;
            $candidate = $alias . '-' . $i;
        }

        $this->alias = $candidate;

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
}
