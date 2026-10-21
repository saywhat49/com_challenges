<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class DisplayController extends BaseController
{
    protected $default_view = 'challenges';

    /** Vues d'édition protégées : on ne les ouvre que via la tâche « edit » (identifiant mémorisé en session). */
    private const EDIT_VIEWS = ['challenge', 'type'];

    public function display($cachable = false, $urlparams = [])
    {
        $view   = $this->input->get('view', $this->default_view);
        $layout = $this->input->get('layout', 'default');
        $id     = $this->input->getInt('id');

        if (\in_array($view, self::EDIT_VIEWS, true) && $layout === 'edit' && !$this->checkEditId('com_challenges.edit.' . $view, $id)) {
            if (!\count($this->app->getMessageQueue())) {
                $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            }

            $this->setRedirect(Route::_('index.php?option=com_challenges&view=' . $view . 's', false));

            return false;
        }

        return parent::display($cachable, $urlparams);
    }
}
