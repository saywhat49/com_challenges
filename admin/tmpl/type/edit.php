<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Fede\Component\Challenges\Administrator\View\Type\HtmlView $this */

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_challenges&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="type-form" aria-label="<?php echo Text::_(empty($this->item->id) ? 'COM_CHALLENGES_MANAGER_TYPE_NEW' : 'COM_CHALLENGES_MANAGER_TYPE_EDIT', true); ?>" class="form-validate">
    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <div class="row">
            <div class="col-lg-9">
                <?php
                echo $this->form->renderField('description');
                echo $this->form->renderField('ordering');
                echo $this->form->renderField('published');
                ?>
            </div>
        </div>
    </div>

    <?php echo $this->form->renderField('id'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
