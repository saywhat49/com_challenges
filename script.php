<?php

/**
 * @package     Fede.Challenges
 * @copyright   (C) 2026 Fédération Française de Boule de Fort
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;

/**
 * Script d'installation. À la désinstallation, les tables ne sont supprimées que si
 * l'option « Supprimer les données à la désinstallation » est activée dans la configuration.
 */
return new class () implements InstallerScriptInterface {
    private const TABLES = [
        '#__challenges_history',
        '#__challenges_digest_log',
        '#__challenges',
        '#__challenges_types',
    ];

    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        $params = ComponentHelper::getParams('com_challenges');

        if ((int) $params->get('delete_data_on_uninstall', 0) !== 1) {
            // Données conservées : rien n'est supprimé.
            return true;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach (self::TABLES as $table) {
            $db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName($table))->execute();
        }

        return true;
    }

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            return false;
        }

        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        return true;
    }
};
