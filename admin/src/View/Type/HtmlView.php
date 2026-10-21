<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\View\Type;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HtmlView extends BaseHtmlView
{
    protected $form;

    protected $item;

    protected $state;

    public function display($tpl = null): void
    {
        $model       = $this->getModel();
        $this->form  = $model->getForm();
        $this->item  = $model->getItem();
        $this->state = $model->getState();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $isNew   = empty($this->item->id);
        $canDo   = ContentHelper::getActions('com_challenges');
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_($isNew ? 'COM_CHALLENGES_MANAGER_TYPE_NEW' : 'COM_CHALLENGES_MANAGER_TYPE_EDIT'), 'tags');

        if ($canDo->get('core.edit') || $canDo->get('core.create')) {
            $toolbar->apply('type.apply');

            $saveGroup = $toolbar->dropdownButton('save-group');
            $saveGroup->configure(
                function (Toolbar $childBar) use ($canDo) {
                    $childBar->save('type.save');

                    if ($canDo->get('core.create')) {
                        $childBar->save2new('type.save2new');
                    }
                }
            );
        }

        $toolbar->cancel('type.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
