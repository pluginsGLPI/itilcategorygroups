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

use Glpi\Plugin\Hooks;

define('PLUGIN_ITILCATEGORYGROUPS_VERSION', '2.6.0-beta2');
define('PLUGIN_ITILCATEGORYGROUPS_MIN_GLPI', '11.0.0');
define('PLUGIN_ITILCATEGORYGROUPS_MAX_GLPI', '11.0.99');

function plugin_init_itilcategorygroups()
{
    /**
     * @var array $CFG_GLPI
     * @var array $PLUGIN_HOOKS
     */
    global $CFG_GLPI, $PLUGIN_HOOKS;

    /**
         * @var array $CFG_GLPI
         */
    global $CFG_GLPI;

    $PLUGIN_HOOKS['csrf_compliant']['itilcategorygroups'] = true;

    if (Plugin::isPluginActive('itilcategorygroups')) {
        if (Session::haveRight('config', UPDATE)) {
            $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['itilcategorygroups'] = 'front/category.php';
        }

        Plugin::registerClass(PluginItilcategorygroupsCategory::class, ['forwardentityfrom' => ITILCategory::class]);
        Plugin::registerClass(PluginItilcategorygroupsGroup_Level::class, ['addtabon' => 'Group']);

        if (Session::haveRight('config', READ)) {
            // add to 'Admin' menu :
            $PLUGIN_HOOKS[Hooks::MENU_TOADD]['itilcategorygroups'] = ['admin' => PluginItilcategorygroupsMenu::class];

            // other hook :
            $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['itilcategorygroups'] = [Group::class => 'plugin_pre_item_update_itilcategorygroups'];
        }
        if (Session::haveRight('config', UPDATE)) {
            $PLUGIN_HOOKS['submenu_entry']['itilcategorygroups']['options']['PluginItilcategorygroupsCategory']['links']['add']
               = '/' . $CFG_GLPI['root_doc'] . '/plugins/itilcategorygroups/front/category.form.php';
        }

        $PLUGIN_HOOKS[hooks::ADD_JAVASCRIPT]['itilcategorygroups'] = ['scripts/multiple_group.js'];

        $PLUGIN_HOOKS[Hooks::FILTER_ACTORS]['itilcategorygroups'] = [
            PluginItilcategorygroupsCategory::class, 'filterActors',
        ];
    }
}

function plugin_itilcategorygroups_check_prerequisites()
{
    if (!is_readable(__DIR__ . '/vendor/autoload.php') || !is_file(__DIR__ . '/vendor/autoload.php')) {
        echo "Run composer install --no-dev in the plugin directory<br>";
        return false;
    }

    return true;
}

// Get the name and the version of the plugin - Needed
function plugin_version_itilcategorygroups()
{
    return [
        'name'         => __('ItilCategory Groups', 'itilcategorygroups'),
        'version'      => PLUGIN_ITILCATEGORYGROUPS_VERSION,
        'author'       => "<a href='http://www.teclib.com'>TECLIB'</a>",
        'homepage'     => 'https://github.com/pluginsGLPI/itilcategorygroups',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_ITILCATEGORYGROUPS_MIN_GLPI,
                'max' => PLUGIN_ITILCATEGORYGROUPS_MAX_GLPI,
            ],
        ],
    ];
}
