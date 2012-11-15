<?php
/*
 * @version $Id: setup.php 19 2012-06-27 09:19:05Z walid $
 LICENSE

  This file is part of the meteofrancehelpdesk plugin.

 Order plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Order plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; along with meteofrancehelpdesk. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   meteofrancehelpdesk
 @author    the meteofrancehelpdesk plugin team
 @copyright Copyright (c) 2010-2011 meteofrancehelpdesk plugin team
 @license   GPLv2+
            http://www.gnu.org/licenses/gpl.txt
 @link      https://forge.indepnet.net/projects/meteofrancehelpdesk
 @link      http://www.glpi-project.org/
 @since     2009
 ---------------------------------------------------------------------- */

define ("GMAO_ENTITY_TAG_PATTERN", "/_GMAO$/");

function plugin_init_meteofrancehelpdesk() {
   global $PLUGIN_HOOKS, $LANG;
    
   $PLUGIN_HOOKS['csrf_compliant']['meteofrancehelpdesk'] = true;
   
   $plugin = new Plugin();
   if ($plugin->isInstalled('meteofrancehelpdesk') && $plugin->isActivated('meteofrancehelpdesk')) {
      Plugin::registerClass('PluginMeteofrancehelpdeskCategory_Group',
                            array('addtabon'         => 'ITILCategory',
                                  'forwardentityfrom' => 'ITILCategory'));
      if (Session::haveRight('config', 'r')) {
         $PLUGIN_HOOKS['menu_entry']['meteofrancehelpdesk'] = 'front/category_group.php';
            $PLUGIN_HOOKS['submenu_entry']['meteofrancehelpdesk']['options']['PluginMeteofrancehelpdeskCategory_Group']['title']
            = $LANG['plugin_meteofrancehelpdesk']['title'][2];
            $PLUGIN_HOOKS['submenu_entry']['meteofrancehelpdesk']['options']['PluginMeteofrancehelpdeskCategory_Group']['page']
            = '/plugins/meteofrancehelpdesk/front/category_group.php';
            $PLUGIN_HOOKS['submenu_entry']['meteofrancehelpdesk']['options']['PluginMeteofrancehelpdeskCategory_Group']['links']['search']
            = '/plugins/meteofrancehelpdesk/front/category_group.php';
      }
      if (Session::haveRight('config', 'w')) {
         $PLUGIN_HOOKS['submenu_entry']['meteofrancehelpdesk']['options']['PluginMeteofrancehelpdeskCategory_Group']['links']['add']
         = '/plugins/meteofrancehelpdesk/front/category_group.form.php';
          
      }
   }
}

// Get the name and the version of the plugin - Needed
function plugin_version_meteofrancehelpdesk() {
   global $LANG;

   $author = "<a href='www.teclib.com'>TECLIB'</a>";
   return array ('name'           => $LANG['plugin_meteofrancehelpdesk']['title'][1],
                   'version'        => '0.83',
                   'author'         => $author,
                   'homepage'       => 'www.teclib.com',
                   'minGlpiVersion' => '0.83.3');
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_meteofrancehelpdesk_check_prerequisites() {
   if (version_compare(GLPI_VERSION,'0.83.3','lt') || version_compare(GLPI_VERSION,'0.84','ge')) {
      echo "This plugin requires GLPI 0.83.3+";
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_meteofrancehelpdesk_check_config() {
      return true;
}