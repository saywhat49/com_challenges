<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\View\Challenge;

use Fede\Component\Challenges\Administrator\Service\HistoryService;
use Fede\Component\Challenges\Administrator\Service\Status;
use Fede\Component\Challenges\Administrator\Service\WorkflowService;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HtmlView extends BaseHtmlView
{
    protected $form;

    protected $item;

    protected $state;

    protected $canDo;

    /** @var object[] Historique du challenge. */
    protected $history = [];

    public function display($tpl = null): void
    {
        $model       = $this->getModel();
        $this->form  = $model->getForm();
        $this->item  = $model->getItem();
        $this->state = $model->getState();
        $this->canDo = ContentHelper::getActions('com_challenges');

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        if (!empty($this->item->id)) {
            $db            = Factory::getContainer()->get(DatabaseInterface::class);
            $this->history = (new HistoryService($db))->forChallenge((int) $this->item->id);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $user    = $this->getCurrentUser();
        $isNew   = empty($this->item->id);
        $canDo   = $this->canDo;
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(
            Text::_($isNew ? 'COM_CHALLENGES_MANAGER_CHALLENGE_NEW' : 'COM_CHALLENGES_MANAGER_CHALLENGE_EDIT'),
            'calendar'
        );

        if ($canDo->get('core.edit') || $canDo->get('core.create')) {
            $toolbar->apply('challenge.apply');

            $saveGroup = $toolbar->dropdownButton('save-group');
            $saveGroup->configure(
                function (Toolbar $childBar) use ($canDo) {
                    $childBar->save('challenge.save');

                    if ($canDo->get('core.create')) {
                        $childBar->save2new('challenge.save2new');
                    }
                }
            );
        }

        // Boutons du workflow, selon le statut courant et les droits de l'utilisateur.
        if (!$isNew) {
            $db       = Factory::getContainer()->get(DatabaseInterface::class);
            $workflow = new WorkflowService($db, new HistoryService($db));
            $status   = (int) $this->item->status;

            $buttons = [
                'validate' => ['COM_CHALLENGES_ACTION_VALIDATE', 'challenge.validateItem', 'icon-check', 'btn btn-success'],
                'refuse'   => ['COM_CHALLENGES_ACTION_REFUSE', 'challenge.refuseItem', 'icon-times', 'btn btn-danger'],
                'cancel'   => ['COM_CHALLENGES_ACTION_CANCEL', 'challenge.cancelItem', 'icon-ban', 'btn btn-danger'],
                'archive'  => ['COM_CHALLENGES_ACTION_ARCHIVE', 'challenge.archiveItem', 'icon-folder', 'btn btn-secondary'],
                'resubmit' => ['COM_CHALLENGES_ACTION_RESUBMIT', 'challenge.resubmitItem', 'icon-redo', 'btn btn-secondary'],
            ];

            foreach ($buttons as $transition => [$text, $task, $icon, $class]) {
                if ($workflow->canApply($transition, $user, $status)) {
                    $toolbar->standardButton($transition, $text, $task)
                        ->icon($icon)
                        ->buttonClass($class);
                }
            }
        }

        $toolbar->cancel('challenge.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }

    /** Libellé et badge du statut pour les gabarits. */
    protected function statusBadge(int $status): string
    {
        return '<span class="badge ' . Status::badgeClass($status) . '">' . $this->escape(Text::_(Status::langKey($status))) . '</span>';
    }
}
