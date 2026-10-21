<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class TypeModel extends AdminModel
{
    public $typeAlias = 'com_challenges.type';

    /**
     * Un type utilisé par au moins un challenge ne peut pas être supprimé (on peut le dépublier).
     */
    protected function canDelete($record)
    {
        if (empty($record->id)) {
            return false;
        }

        $db  = $this->getDatabase();
        $id  = (int) $record->id;
        $sql = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__challenges'))
            ->where($db->quoteName('type_id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        if ((int) $db->setQuery($sql)->loadResult() > 0) {
            $this->setError(Text::sprintf('COM_CHALLENGES_ERROR_TYPE_IN_USE', $record->title));

            return false;
        }

        return parent::canDelete($record);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_challenges.type', 'type', ['control' => 'jform', 'load_data' => $loadData]);

        return empty($form) ? false : $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_challenges.edit.type.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_challenges.type', $data);

        return $data;
    }
}
