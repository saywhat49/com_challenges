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

/** @var \Fede\Component\Challenges\Administrator\View\Challenge\HtmlView $this */

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

$isNew = empty($this->item->id);
?>

<form action="<?php echo Route::_('index.php?option=com_challenges&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="challenge-form" aria-label="<?php echo Text::_($isNew ? 'COM_CHALLENGES_MANAGER_CHALLENGE_NEW' : 'COM_CHALLENGES_MANAGER_CHALLENGE_EDIT', true); ?>" class="form-validate">

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'challengeTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'challengeTab', 'general', Text::_('COM_CHALLENGES_TAB_GENERAL')); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php if (!$isNew) : ?>
                    <p>
                        <strong><?php echo Text::_('JSTATUS'); ?> :</strong>
                        <?php echo $this->statusBadge((int) $this->item->status); ?>
                    </p>
                <?php endif; ?>
                <?php
                echo $this->form->renderField('societe_id');
                echo $this->form->renderField('commune');
                echo $this->form->renderField('venue');
                echo $this->form->renderField('type_id');
                echo $this->form->renderField('start_datetime');
                echo $this->form->renderField('registration_deadline');
                echo $this->form->renderField('description');
                echo $this->form->renderField('formula');
                echo $this->form->renderField('conditions');
                echo $this->form->renderField('fee');
                echo $this->form->renderField('max_teams');
                ?>
            </div>
            <div class="col-lg-3">
                <fieldset class="form-vertical">
                    <legend class="visually-hidden"><?php echo Text::_('COM_CHALLENGES_TAB_FOLLOWUP'); ?></legend>
                    <?php echo $this->form->renderField('status_reason'); ?>
                    <p class="small text-muted"><?php echo Text::_('COM_CHALLENGES_REASON_HELP'); ?></p>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'challengeTab', 'contacts', Text::_('COM_CHALLENGES_TAB_CONTACTS')); ?>
        <div class="row">
            <div class="col-lg-6">
                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_CHALLENGES_FIELDSET_PUBLIC_CONTACT'); ?></legend>
                    <?php
                    echo $this->form->renderField('contact_name');
                    echo $this->form->renderField('contact_email');
                    echo $this->form->renderField('contact_phone');
                    ?>
                </fieldset>
            </div>
            <div class="col-lg-6">
                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_CHALLENGES_FIELDSET_DECLARANT'); ?></legend>
                    <p class="small text-muted"><?php echo Text::_('COM_CHALLENGES_DECLARANT_HELP'); ?></p>
                    <?php
                    echo $this->form->renderField('declarant_name');
                    echo $this->form->renderField('declarant_email');
                    echo $this->form->renderField('declarant_phone');
                    ?>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php if (!$isNew) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'challengeTab', 'history', Text::_('COM_CHALLENGES_TAB_HISTORY')); ?>
            <?php if (empty($this->history)) : ?>
                <div class="alert alert-info"><?php echo Text::_('COM_CHALLENGES_HISTORY_EMPTY'); ?></div>
            <?php else : ?>
                <table class="table">
                    <caption class="visually-hidden"><?php echo Text::_('COM_CHALLENGES_TAB_HISTORY'); ?></caption>
                    <thead>
                        <tr>
                            <th scope="col"><?php echo Text::_('COM_CHALLENGES_HISTORY_DATE'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_CHALLENGES_HISTORY_ACTION'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_CHALLENGES_HISTORY_USER'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_CHALLENGES_HISTORY_DETAILS'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->history as $row) : ?>
                            <?php $changes = $row->changes ? json_decode($row->changes, true) : []; ?>
                            <tr>
                                <td><?php echo HTMLHelper::_('date', $row->changed_at, 'd/m/Y H:i'); ?></td>
                                <td><?php echo $this->escape(Text::_('COM_CHALLENGES_HISTORY_ACTION_' . strtoupper($row->action))); ?></td>
                                <td>
                                    <?php echo $row->user_id === null
                                        ? $this->escape(Text::_('COM_CHALLENGES_HISTORY_PUBLIC_DECLARANT'))
                                        : $this->escape((string) ($row->user_name ?? '#' . (int) $row->user_id)); ?>
                                </td>
                                <td class="small">
                                    <?php if (!empty($row->note)) : ?>
                                        <div><?php echo $this->escape($row->note); ?></div>
                                    <?php endif; ?>
                                    <?php if (\is_array($changes)) : ?>
                                        <?php foreach ($changes as $field => $pair) : ?>
                                            <div>
                                                <strong><?php echo $this->escape(Text::_('COM_CHALLENGES_FIELD_' . strtoupper((string) $field) . '_LABEL')); ?></strong> :
                                                <?php
                                                if (\is_array($pair) && \count($pair) === 2) {
                                                    echo $this->escape((string) $pair[0]) . ' → ' . $this->escape((string) $pair[1]);
                                                } else {
                                                    echo $this->escape(implode(', ', array_map('strval', (array) $pair)));
                                                }
                                                ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <?php echo $this->form->renderField('id'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
