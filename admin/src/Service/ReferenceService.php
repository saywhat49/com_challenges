<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fede\Component\Challenges\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Accès EN LECTURE SEULE aux tables du site `secteurs` et `societes`.
 *
 * Les noms de tables et de colonnes viennent des options du composant (valeurs par défaut ci-dessous).
 * Ils sont validés (lettres, chiffres, souligné) avant d'être utilisés dans une requête.
 * Ce service n'exécute jamais d'INSERT, d'UPDATE ni de DELETE.
 */
class ReferenceService
{
    /** Valeurs par défaut des options « Tables de référence ». */
    public const DEFAULTS = [
        'ref_table_secteurs'        => 'secteurs',
        'ref_col_secteur_id'       => 'id',
        'ref_col_secteur_code'     => 'num_secteur',
        'ref_col_secteur_libelle'   => 'libelle_secteur',
        'ref_col_secteur_email'     => 'courriel_du_responsable',
        'ref_col_secteur_email_adjoint' => 'courriel_adjoint',
        'ref_col_secteur_nom'       => 'responsable_de_secteur',
        'ref_table_societes'        => 'societes',
        'ref_col_societe_nom'       => 'societe',
        'ref_col_societe_commune'   => 'commune',
        'ref_col_societe_cp'        => 'cp',
        'ref_col_societe_secteur'   => 'secteur',
        'ref_col_societe_adhesion'  => 'adhesion',
        'ref_adhesion_value'        => 'Oui,1',
    ];

    private readonly Registry $params;

    /** @var array<int,string>|null Cache des libellés de secteurs, par id. */
    private ?array $secteurLabels = null;

    public function __construct(private readonly DatabaseInterface $db, ?Registry $params = null)
    {
        $this->params = $params ?? ComponentHelper::getParams('com_challenges');
    }

    /**
     * Libellés des secteurs : [id du secteur => « 09 - ALLONNES »].
     *
     * @return  array<int,string>
     */
    public function secteurLabels(): array
    {
        if ($this->secteurLabels !== null) {
            return $this->secteurLabels;
        }

        $id      = $this->ident('ref_col_secteur_id');
        $code    = $this->ident('ref_col_secteur_code');
        $libelle = $this->ident('ref_col_secteur_libelle');

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName($id, 'sid'),
                $this->db->quoteName($code, 'code'),
                $this->db->quoteName($libelle, 'libelle'),
            ])
            ->from($this->db->quoteName($this->ident('ref_table_secteurs')))
            ->where($this->db->quoteName($id) . ' IS NOT NULL')
            ->order($this->db->quoteName($code) . ', ' . $this->db->quoteName($id));

        $labels = [];

        foreach ($this->db->setQuery($query)->loadObjectList() ?: [] as $row) {
            $codeTxt    = trim((string) $row->code);
            $libelleTxt = trim((string) $row->libelle);
            $text       = trim(($codeTxt !== '' ? $codeTxt . ' - ' : '') . $libelleTxt, ' -');

            $labels[(int) $row->sid] = $text !== '' ? $text : (string) (int) $row->sid;
        }

        return $this->secteurLabels = $labels;
    }

    /**
     * Sociétés adhérentes proposées dans la liste déroulante : [id => « Nom - Commune »].
     *
     * @return  array<int,string>
     */
    public function societeOptions(): array
    {
        $nom      = $this->ident('ref_col_societe_nom');
        $commune  = $this->ident('ref_col_societe_commune');
        $cp       = $this->ident('ref_col_societe_cp');
        $adhesion = $this->ident('ref_col_societe_adhesion');
        $values   = array_values($this->adhesionValues());

        // Colonnes lues : id, société, code postal, commune. L'adhésion ne sert que de filtre.
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('id'),
                $this->db->quoteName($nom, 'nom'),
                $this->db->quoteName($cp, 'cp'),
                $this->db->quoteName($commune, 'commune'),
            ])
            ->from($this->db->quoteName($this->ident('ref_table_societes')))
            ->order($this->db->quoteName($nom) . ', ' . $this->db->quoteName($commune));

        $query->where($this->adhesionCondition($query, $adhesion, $values));

        $options = [];

        foreach ($this->db->setQuery($query)->loadObjectList() ?: [] as $row) {
            $nomTxt = trim((string) $row->nom);

            if ($nomTxt === '') {
                continue;
            }

            $cpTxt = trim((string) $row->cp);

            $options[(int) $row->id] = $nomTxt . ' - ' . trim((string) $row->commune) . ($cpTxt !== '' ? ' (' . $cpTxt . ')' : '');
        }

        return $options;
    }

    /**
     * Société adhérente par identifiant, ou null si elle n'existe pas ou n'est pas adhérente.
     *
     * @return  object|null  Propriétés : id, nom, cp, commune, et secteur_id = secteurs.id (clé de rattachement, non affichée ni copiée).
     */
    public function getSociete(int $id): ?object
    {
        $nom      = $this->ident('ref_col_societe_nom');
        $commune  = $this->ident('ref_col_societe_commune');
        $cp       = $this->ident('ref_col_societe_cp');
        $secteur  = $this->ident('ref_col_societe_secteur');
        $adhesion = $this->ident('ref_col_societe_adhesion');
        $values   = array_values($this->adhesionValues());

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('id'),
                $this->db->quoteName($nom, 'nom'),
                $this->db->quoteName($cp, 'cp'),
                $this->db->quoteName($commune, 'commune'),
                $this->db->quoteName($secteur, 'secteur_id'),
            ])
            ->from($this->db->quoteName($this->ident('ref_table_societes')))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $query->where($this->adhesionCondition($query, $adhesion, $values));

        $row = $this->db->setQuery($query)->loadObject();

        if (!$row || (int) $row->secteur_id < 1) {
            return null;
        }

        $row->nom         = trim((string) $row->nom);
        $row->commune     = trim((string) $row->commune);
        $row->cp          = trim((string) $row->cp);
        $row->secteur_id = (int) $row->secteur_id;

        return $row;
    }

    /**
     * Adresses de notification d'un secteur (responsable). Données privées : usage interne uniquement.
     *
     * @return  string[]  Adresses valides, sans doublon.
     */
    public function secteurRecipients(int $secteurId): array
    {
        $id      = $this->ident('ref_col_secteur_id');
        $email   = $this->ident('ref_col_secteur_email');
        $adjoint = $this->ident('ref_col_secteur_email_adjoint');

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName($email, 'responsable'),
                $this->db->quoteName($adjoint, 'adjoint'),
            ])
            ->from($this->db->quoteName($this->ident('ref_table_secteurs')))
            ->where($this->db->quoteName($id) . ' = :sid')
            ->bind(':sid', $secteurId, ParameterType::INTEGER);

        $emails = [];

        // Responsable de secteur, puis adjoint si une adresse valide est renseignée.
        foreach ($this->db->setQuery($query)->loadObjectList() ?: [] as $row) {
            foreach ([$row->responsable, $row->adjoint] as $address) {
                $address = trim((string) $address);

                if ($address !== '' && filter_var($address, FILTER_VALIDATE_EMAIL)) {
                    $emails[strtolower($address)] = $address;
                }
            }
        }

        return array_values($emails);
    }

    /**
     * Valeurs d'adhésion acceptées (liste séparée par des virgules, casse et espaces ignorés).
     *
     * @return  string[]
     */
    private function adhesionValues(): array
    {
        $values = array_filter(array_map(
            static fn ($v) => strtolower(trim($v)),
            explode(',', $this->param('ref_adhesion_value'))
        ), static fn ($v) => $v !== '');

        return $values ?: ['oui', '1'];
    }

    /**
     * Condition SQL « adhésion acceptée ». Les valeurs sont liées par référence : $values doit rester en vie
     * jusqu'à l'exécution de la requête.
     *
     * @param   string[]  $values
     */
    private function adhesionCondition(\Joomla\Database\QueryInterface $query, string $column, array &$values): string
    {
        $placeholders = [];

        foreach (array_keys($values) as $i) {
            $name = ':adh' . $i;
            $query->bind($name, $values[$i], ParameterType::STRING);
            $placeholders[] = $name;
        }

        return 'LOWER(TRIM(' . $this->db->quoteName($column) . ')) IN (' . implode(',', $placeholders) . ')';
    }

    private function param(string $key): string
    {
        $value = trim((string) $this->params->get($key, self::DEFAULTS[$key] ?? ''));

        return $value !== '' ? $value : (self::DEFAULTS[$key] ?? '');
    }

    /** Nom de table ou de colonne validé. */
    private function ident(string $key): string
    {
        $value = $this->param($key);

        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $value)) {
            throw new \RuntimeException('Option « ' . $key . ' » invalide : lettres, chiffres et _ uniquement.');
        }

        return $value;
    }
}
