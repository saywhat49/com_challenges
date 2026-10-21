<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Field;

use Fede\Component\Challenges\Administrator\Service\ReferenceService;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Liste déroulante des sociétés adhérentes (lecture de la table externe `societes`).
 */
class SocieteField extends ListField
{
    protected $type = 'Societe';

    protected function getOptions()
    {
        $service = new ReferenceService($this->getDatabase());
        $options = [];

        foreach ($service->societeOptions() as $id => $libelle) {
            $options[] = HTMLHelper::_('select.option', (string) $id, $libelle);
        }

        return array_merge(parent::getOptions(), $options);
    }
}
