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

function plugin_itilcategorygroups_install()
{
    $dir = Plugin::getPhpDir('itilcategorygroups');

    $migration = new Migration('0.84');

    //order is important for install
    include_once($dir . '/inc/category.class.php');
    include_once($dir . '/inc/category_group.class.php');
    include_once($dir . '/inc/group_level.class.php');
    PluginItilcategorygroupsCategory::install($migration);
    PluginItilcategorygroupsCategory_Group::install($migration);
    PluginItilcategorygroupsGroup_Level::install($migration);

    return true;
}

function plugin_itilcategorygroups_uninstall()
{
    $dir = Plugin::getPhpDir('itilcategorygroups');

    include_once($dir . '/inc/category_group.class.php');
    include_once($dir . '/inc/category.class.php');
    include_once($dir . '/inc/group_level.class.php');
    PluginItilcategorygroupsCategory_Group::uninstall();
    PluginItilcategorygroupsCategory::uninstall();
    PluginItilcategorygroupsGroup_Level::uninstall();

    return true;
}

function plugin_itilcategorygroups_getAddSearchOptions($itemtype)
{
    if (isset($_SESSION['glpiactiveentities'])) {
        return PluginItilcategorygroupsGroup_Level::getAddSearchOptions($itemtype);
    } else {
        return null;
    }
}

function plugin_itilcategorygroups_giveItem($type, $ID, $data, $num)
{
    $searchopt = &Search::getOptions($type);
    $table     = $searchopt[$ID]['table'];
    $field     = $searchopt[$ID]['field'];
    $value     = $data['raw']['ITEM_' . $num];

    if ($table . '.' . $field === 'glpi_plugin_itilcategorygroups_groups_levels.lvl') {
        switch ($value) {
            case 1:
            case 2:
            case 3:
            case 4:
                return __s('Level ' . $value, 'itilcategorygroups');
        }
    }

    return '';
}

// Display specific massive actions for plugin fields
function plugin_itilcategorygroups_MassiveActionsFieldsDisplay($options = [])
{
    $table     = $options['options']['table'];
    $field     = $options['options']['field'];

    if ($table . '.' . $field === 'glpi_plugin_itilcategorygroups_groups_levels.lvl') {
        Dropdown::showFromArray(
            'lvl',
            [
                null => '---',
                1 => __s('Level 1', 'itilcategorygroups'),
                2 => __s('Level 2', 'itilcategorygroups'),
                3 => __s('Level 3', 'itilcategorygroups'),
                4 => __s('Level 4', 'itilcategorygroups'),
            ],
        );
        return true;
    }

    // Need to return false on non display item
    return false;
}


// Hook done on update item case
function plugin_pre_item_update_itilcategorygroups($item)
{
    if (isset($_REQUEST['massiveaction'])
        && isset($_REQUEST['lvl'])
        && $item instanceof Group) {
        $group_level = new PluginItilcategorygroupsGroup_Level();
        if (!$group_level->getFromDB($item->fields['id'])) {
            $group_level->add(['groups_id' => $item->fields['id'],
                'lvl'                      => $_REQUEST['lvl']]);
        } else {
            $group_level->update(['groups_id' => $item->fields['id'],
                'lvl'                         => $_REQUEST['lvl']]);
        }
    }

    return $item;
}
