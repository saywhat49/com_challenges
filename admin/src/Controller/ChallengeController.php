<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Controller;

use Fede\Component\Challenges\Administrator\Service\HistoryService;
use Fede\Component\Challenges\Administrator\Service\WorkflowService;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Fiche d'un challenge : enregistrement (FormController) et transitions du workflow.
 *
 * Les transitions n'enregistrent PAS les modifications du formulaire : il faut enregistrer d'abord.
 * Seul le motif (champ jform[status_reason]) est transmis.
 */
class ChallengeController extends FormController
{
    protected $text_prefix = 'COM_CHALLENGES_CHALLENGE';

    public function validateItem(): void
    {
        $this->transition('validate');
    }

    public function refuseItem(): void
    {
        $this->transition('refuse');
    }

    public function cancelItem(): void
    {
        $this->transition('cancel');
    }

    public function archiveItem(): void
    {
        $this->transition('archive');
    }

    public function resubmitItem(): void
    {
        $this->transition('resubmit');
    }

    private function transition(string $transition): void
    {
        $this->checkToken();

        $jform  = (array) $this->input->get('jform', [], 'array');
        $id     = (int) ($jform['id'] ?? $this->input->getInt('id'));
        $reason = trim((string) ($jform['status_reason'] ?? ''));
        $editUrl = Route::_('index.php?option=com_challenges&view=challenge&layout=edit&id=' . $id, false);

        if ($id < 1) {
            $this->setRedirect(Route::_('index.php?option=com_challenges&view=challenges', false), Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');

            return;
        }

        $db      = Factory::getContainer()->get(DatabaseInterface::class);
        $service = new WorkflowService($db, new HistoryService($db));

        try {
            $service->apply($transition, $id, $this->app->getIdentity(), $reason);
        } catch (\RuntimeException $e) {
            $this->setRedirect($editUrl, $e->getMessage(), 'error');

            return;
        }

        $this->releaseEditId('com_challenges.edit.challenge', $id);
        $this->app->setUserState('com_challenges.edit.challenge.data', null);

        $this->setRedirect(
            Route::_('index.php?option=com_challenges&view=challenges', false),
            Text::_('COM_CHALLENGES_TRANSITION_DONE_' . strtoupper($transition))
        );
    }
}
