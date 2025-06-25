<?php

/**
 * -------------------------------------------------------------------------
 * ItilCategoryGroups plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of ItilCategoryGroups.
 *
 * ItilCategoryGroups is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ItilCategoryGroups is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ItilCategoryGroups. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2012-2022 by ItilCategoryGroups plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/itilcategorygroups
 * -------------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;

/**
 * -------------------------------------------------------------------------
 * ItilCategoryGroups plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of ItilCategoryGroups.
 *
 * ItilCategoryGroups is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ItilCategoryGroups is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ItilCategoryGroups. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2012-2022 by ItilCategoryGroups plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/itilcategorygroups
 * -------------------------------------------------------------------------
 */

class PluginItilcategorygroupsGroup_Level extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype = 'Group';
    public static $items_id = 'groups_id';

    public static function getIndexName()
    {
        return self::$items_id;
    }

    public static function getTypeName($nb = 0)
    {
        return __('Level attribution', 'itilcategorygroups');
    }

    public static function canView(): bool
    {
        return Session::haveRight('config', READ);
    }

    public static function canCreate(): bool
    {
        return Session::haveRight('config', CREATE);
    }

    public static function canPurge(): bool
    {
        return false;
    }

    public static function install(Migration $migration)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

        $table = getTableForItemType(__CLASS__);

        return $DB->doQuery("CREATE TABLE IF NOT EXISTS `$table` (
         `id`        int {$default_key_sign} NOT NULL auto_increment,
         `groups_id` int {$default_key_sign} NOT NULL,
         `lvl`       int DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY         `groups_id` (`groups_id`),
         KEY         `lvl` (`lvl`)
      ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;");
    }

    public static function uninstall()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $table = getTableForItemType(__CLASS__);

        return $DB->doQuery("DROP TABLE IF EXISTS `$table`");
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            switch ($item->getType()) {
                case 'Group':
                    return self::createTabEntry(__('ItilCategory Groups', 'itilcategorygroups'), 0, $item::getType(), PluginItilcategorygroupsCategory::getIcon());
            }
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Group) {
            self::showForGroup($item);
        }

        return true;
    }

    public function getFormFields(): array
    {
        return ['id', 'lvl'];
    }

    public static function showForGroup(Group $group)
    {
        $ID = $group->getField('id');
        if (!$group->can($ID, READ)) {
            return false;
        }

        $item = new self();
        if (!$item->getFromDB($ID)) {
            $item->getEmpty();
        }

        TemplateRenderer::getInstance()->display(
            '@itilcategorygroups/groupe.html.twig',
            [
                'item'          => $item,
            ],
        );

        return true;
    }

    public static function getAddSearchOptions($itemtype)
    {
        $opt = [];

        if ($itemtype == 'Group') {
            $opt[9978]['table']      = getTableForItemType(__CLASS__);
            $opt[9978]['field']      = 'lvl';
            $opt[9978]['name']       = __('Level attribution', 'itilcategorygroups');
            $opt[9978]['linkfield']  = 'lvl';
            $opt[9978]['joinparams'] = ['jointype' => 'child'];
        }

        return $opt;
    }

    public static function getAllGroupForALevel($level, $entities_id = -1)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if ($entities_id === -1) {
            $entities_id = $_SESSION['glpiactive_entity'];
        }

        $groups_id = [];
        $table = getTableForItemType(__CLASS__);
        $query = [
            'SELECT' => "$table.groups_id",
            'FROM'   => $table,
            'LEFT JOIN' => [
                'glpi_groups' => [
                    'ON' => [
                        $table => 'groups_id',
                        'glpi_groups' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                "$table.lvl" => $level,
            ] + getEntitiesRestrictCriteria(
                'glpi_groups',
                'entities_id',
                $entities_id,
                true,
            ),
        ];
        foreach ($DB->request($query) as $data) {
            $groups_id[] = $data['groups_id'];
        }

        return $groups_id;
    }
}
