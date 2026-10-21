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
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Actions sur la liste : validation et archivage en lot (les refus et annulations exigent un motif
 * et se font depuis la fiche).
 */
class ChallengesController extends AdminController
{
    protected $text_prefix = 'COM_CHALLENGES_CHALLENGES';

    public function getModel($name = 'Challenge', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function validateSelected(): void
    {
        $this->transition('validate');
    }

    public function archiveSelected(): void
    {
        $this->transition('archive');
    }

    private function transition(string $transition): void
    {
        $this->checkToken();

        $ids  = ArrayHelper::toInteger((array) $this->input->get('cid', [], 'array'));
        $ids  = array_filter($ids);
        $user = $this->app->getIdentity();

        if ($ids === []) {
            $this->setMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        } else {
            $db      = Factory::getContainer()->get(DatabaseInterface::class);
            $service = new WorkflowService($db, new HistoryService($db));
            $done    = 0;
            $errors  = [];

            foreach ($ids as $id) {
                try {
                    $service->apply($transition, $id, $user);
                    $done++;
                } catch (\RuntimeException $e) {
                    $errors[] = '#' . $id . ' : ' . $e->getMessage();
                }
            }

            if ($done > 0) {
                $this->setMessage(Text::plural('COM_CHALLENGES_N_ITEMS_TRANSITIONED', $done));
            }

            foreach ($errors as $error) {
                $this->app->enqueueMessage($error, 'warning');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_challenges&view=challenges', false));
    }
}
