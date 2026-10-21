<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\View\Challenges;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HtmlView extends BaseHtmlView
{
    public $filterForm;

    public $activeFilters = [];

    protected $items = [];

    protected $pagination;

    protected $state;

    public function display($tpl = null): void
    {
        $model = $this->getModel();
        $model->setUseExceptions(true);

        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $canDo   = ContentHelper::getActions('com_challenges');
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_CHALLENGES_MANAGER_CHALLENGES'), 'calendar');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('challenge.add');
        }

        if ($canDo->get('challenges.validate') || $canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group', 'JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            if ($canDo->get('challenges.validate')) {
                $childBar->standardButton('validate', 'COM_CHALLENGES_ACTION_VALIDATE', 'challenges.validateSelected')
                    ->icon('icon-check')
                    ->listCheck(true);
            }

            if ($canDo->get('core.edit.state')) {
                $childBar->standardButton('archive', 'COM_CHALLENGES_ACTION_ARCHIVE', 'challenges.archiveSelected')
                    ->icon('icon-folder')
                    ->listCheck(true);
            }
        }

        if ($canDo->get('core.delete')) {
            $toolbar->delete('challenges.delete', 'JTOOLBAR_DELETE')
                ->message('COM_CHALLENGES_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->preferences('com_challenges');
        }
    }
}
