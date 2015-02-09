<?php
/*
 * @version $Id: setup.php 19 2012-06-27 09:19:05Z walid $
 LICENSE

  This file is part of the itilcategorygroups plugin.

 Order plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Order plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; along with itilcategorygroups. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   itilcategorygroups
 @author    the itilcategorygroups plugin team
 @copyright Copyright (c) 2010-2011 itilcategorygroups plugin team
 @license   GPLv2+
            http://www.gnu.org/licenses/gpl.txt
 @link      https://forge.indepnet.net/projects/itilcategorygroups
 @link      http://www.glpi-project.org/
 @since     2009
 ---------------------------------------------------------------------- */

function plugin_init_itilcategorygroups() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['itilcategorygroups'] = true;
   
   $plugin = new Plugin();

   if ($plugin->isInstalled('itilcategorygroups') && $plugin->isActivated('itilcategorygroups')) {

      Plugin::registerClass('PluginItilcategorygroupsCategory',
                            array('forwardentityfrom' => 'ITILCategory'));
      Plugin::registerClass('PluginItilcategorygroupsGroup_Level',
                            array('addtabon' => 'Group'));

      if (Session::haveRight('config', 'r')) {

         $PLUGIN_HOOKS['menu_entry']['itilcategorygroups'] = 'front/category.php';

         $PLUGIN_HOOKS['submenu_entry']['itilcategorygroups']['options']['PluginItilcategorygroupsCategory']['title']
            = __('Link ItilCategory - Groups','itilcategorygroups');

         $PLUGIN_HOOKS['submenu_entry']['itilcategorygroups']['options']['PluginItilcategorygroupsCategory']['page']
            = '/plugins/itilcategorygroups/front/category.php';

         $PLUGIN_HOOKS['submenu_entry']['itilcategorygroups']['options']['PluginItilcategorygroupsCategory']['links']['search']
            = '/plugins/itilcategorygroups/front/category.php';

         $PLUGIN_HOOKS['pre_item_update']['itilcategorygroups'] = array('Group' => 'plugin_pre_item_update_itilcategorygroups');
      }
      if (Session::haveRight('config', 'w')) {
         $PLUGIN_HOOKS['submenu_entry']['itilcategorygroups']['options']['PluginItilcategorygroupsCategory']['links']['add']
            = '/plugins/itilcategorygroups/front/category.form.php';
         
      }

      $PLUGIN_HOOKS['add_javascript']['itilcategorygroups'][] = 'scripts/filtergroups.js.php';
      $PLUGIN_HOOKS['add_javascript']['itilcategorygroups'][] = 'scripts/multiple_group.js';
      $PLUGIN_HOOKS['add_javascript']['itilcategorygroups'][] = "lib/chosen/chosen.native.js";
      $PLUGIN_HOOKS['add_css']['itilcategorygroups'][] = "lib/chosen/chosen.css";
   }
}

// Get the name and the version of the plugin - Needed
function plugin_version_itilcategorygroups() {
   return array('name'           => __('ItilCategory Groups', 'itilcategorygroups'),
                'version'        => '0.85+1.2',
                'author'         => "<a href='http://www.teclib.com'>TECLIB'</a>",
                'homepage'       => 'http://www.teclib.com');
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_itilcategorygroups_check_prerequisites() {
   if (version_compare(GLPI_VERSION,'0.85', 'lt') || version_compare(GLPI_VERSION,'0.86', 'ge')) {
      echo "This plugin requires GLPI 0.85";
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_itilcategorygroups_check_config() {
      return true;
}