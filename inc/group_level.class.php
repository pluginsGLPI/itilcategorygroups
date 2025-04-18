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

    public static function canView()
    {
        return Session::haveRight('config', READ);
    }

    public static function canCreate()
    {
        return Session::haveRight('config', CREATE);
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
                    return __('ItilCategory Groups', 'itilcategorygroups');
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

    public static function showForGroup(Group $group)
    {
        $ID = $group->getField('id');
        if (!$group->can($ID, READ)) {
            return false;
        }

        $canedit = $group->can($ID, UPDATE);
        // Get data
        $item = new self();
        if (!$item->getFromDB($ID)) {
            $item->getEmpty();
        }

        $rand = mt_rand();
        echo "<form name='group_level_form$rand' id='group_level_form$rand' method='post'
             action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
        echo "<input type='hidden' name='" . self::$items_id . "' value='$ID' />";

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th>" . __('Level attribution', 'itilcategorygroups') . '</th></tr>';

        echo "<tr class='tab_bg_2'><td class='center'>";
        Dropdown::showFromArray(
            'lvl',
            [null => '---',
                1 => __('Level 1', 'itilcategorygroups'),
                2 => __('Level 2', 'itilcategorygroups'),
                3 => __('Level 3', 'itilcategorygroups'),
                4 => __('Level 4', 'itilcategorygroups')],
            ['value' => $item->fields['lvl']],
        );
        echo '</td></tr>';

        if ($canedit) {
            echo "<tr class='tab_bg_1'><td class='center'>";
            if ($item->fields['id']) {
                echo "<input type='hidden' name='id' value='" . $item->fields['id'] . "'>";
                echo "<input type='submit' name='update' value=\"" . __('Save') . "\"
                   class='submit'>";
            } else {
                echo "<input type='submit' name='add' value=\"" . __('Save') . "\" class='submit'>";
            }
            echo '</td></tr>';
        }
        echo '</table></div>';
        Html::closeForm();
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
        $query = [
            'SELECT' => 'gl.groups_id',
            'FROM'   => getTableForItemType(__CLASS__) . 'AS gl',
            'LEFT JOIN' => [
                'glpi_groups gr' => [
                    'ON' => 'gl.groups_id = gr.id',
                ],
            ],
            'WHERE' => [
                'gl.lvl' => $level,
            ] + getEntitiesRestrictCriteria(
                'gr',
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
