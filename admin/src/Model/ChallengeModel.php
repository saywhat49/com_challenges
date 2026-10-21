<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Model;

use Fede\Component\Challenges\Administrator\Service\HistoryService;
use Fede\Component\Challenges\Administrator\Service\Status;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Édition d'un challenge. Le statut n'est jamais modifiable par le formulaire :
 * il ne change que par les transitions du WorkflowService.
 */
class ChallengeModel extends AdminModel
{
    public $typeAlias = 'com_challenges.challenge';

    /** Champs suivis dans l'historique. */
    private const TRACKED = [
        'title', 'description', 'societe_id', 'commune', 'venue', 'secteur_id', 'type_id',
        'start_datetime', 'registration_deadline', 'formula', 'conditions', 'fee', 'max_teams',
        'contact_name', 'contact_email', 'contact_phone',
        'declarant_name', 'declarant_email', 'declarant_phone',
    ];

    /** Champs personnels : on note qu'ils ont changé, jamais leur valeur. */
    private const PERSONAL = [
        'contact_name', 'contact_email', 'contact_phone',
        'declarant_name', 'declarant_email', 'declarant_phone',
    ];

    /** Champs de date : leur modification est tracée à part (« date_changed »). */
    private const DATES = ['start_datetime', 'registration_deadline'];

    protected function canDelete($record)
    {
        if (empty($record->id)) {
            return false;
        }

        // On ne supprime que des annonces jamais publiées : brouillons ou refusées.
        if (!\in_array((int) $record->status, [Status::DRAFT, Status::REFUSED], true)) {
            $this->setError(Text::_('COM_CHALLENGES_ERROR_DELETE_ONLY_DRAFT_OR_REFUSED'));

            return false;
        }

        return parent::canDelete($record);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_challenges.challenge', 'challenge', ['control' => 'jform', 'load_data' => $loadData]);

        return empty($form) ? false : $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_challenges.edit.challenge.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_challenges.challenge', $data);

        return $data;
    }

    protected function prepareTable($table)
    {
        $user = $this->getCurrentUser();

        if (empty($table->id)) {
            $table->created_by = (int) $user->id;
            // Une annonce créée par la Fédération démarre « en attente » ; la publication passe par le workflow.
            $table->status = Status::PENDING;
        } else {
            $table->modified_by = (int) $user->id;
        }
    }

    public function save($data)
    {
        // Le statut ne doit jamais venir du formulaire.
        unset($data['status'], $data['validated_by'], $data['validated_at'], $data['cancelled_at'], $data['archived_at']);

        $id  = (int) ($data['id'] ?? $this->getState($this->getName() . '.id'));
        $old = [];

        if ($id > 0) {
            $table = $this->getTable();

            if ($table->load($id)) {
                $old = $table->getProperties();
            }
        }

        if (!parent::save($data)) {
            return false;
        }

        $newId = (int) $this->getState($this->getName() . '.id');
        $table = $this->getTable();

        if ($newId > 0 && $table->load($newId)) {
            $this->logChanges($newId, $old, $table->getProperties());
        }

        return true;
    }

    private function logChanges(int $id, array $old, array $new): void
    {
        $history = new HistoryService(Factory::getContainer()->get(DatabaseInterface::class));
        $userId  = (int) $this->getCurrentUser()->id;

        if ($old === []) {
            $history->log($id, 'created', $userId);

            return;
        }

        $changes     = [];
        $dateChanged = false;

        foreach (self::TRACKED as $field) {
            $before = $old[$field] ?? null;
            $after  = $new[$field] ?? null;

            if ((string) $before === (string) $after) {
                continue;
            }

            if (\in_array($field, self::PERSONAL, true)) {
                $changes[$field] = ['modifié'];
            } elseif ($field === 'description' || $field === 'conditions' || $field === 'formula') {
                $changes[$field] = ['modifié'];
            } else {
                $changes[$field] = [$before, $after];
            }

            if (\in_array($field, self::DATES, true)) {
                $dateChanged = true;
            }
        }

        if ($changes !== []) {
            $history->log($id, $dateChanged ? 'date_changed' : 'edited', $userId, $changes);
        }
    }
}
