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

function plugin_meteofrancehelpdesk_install() {
   $migration = new Migration("0.83");
   
   include_once(GLPI_ROOT."/plugins/meteofrancehelpdesk/inc/category_group.class.php");
   include_once(GLPI_ROOT."/plugins/meteofrancehelpdesk/inc/group_level.class.php");
   PluginMeteofrancehelpdeskCategory_Group::install($migration);
   PluginMeteofrancehelpdeskGroup_Level::install($migration);
   return true;
}

function plugin_meteofrancehelpdesk_uninstall() {
   include_once(GLPI_ROOT."/plugins/meteofrancehelpdesk/inc/category_group.class.php");
   include_once(GLPI_ROOT."/plugins/meteofrancehelpdesk/inc/group_level.class.php");
   PluginMeteofrancehelpdeskCategory_Group::uninstall();
   PluginMeteofrancehelpdeskGroup_Level::uninstall();
   return true;
}

function plugin_meteofrancehelpdesk_getAddSearchOptions($itemtype) {
   if (isset($_SESSION['glpiactiveentities'])) {
      $options = PluginMeteofrancehelpdeskGroup_Level::getAddSearchOptions($itemtype);
      return $options;
   } else {
      return NULL;
   }
}

function plugin_meteofrancehelpdesk_giveItem($type,$ID,$data,$num) {
   global $LANG;

   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$ID]["table"];
   $field = $searchopt[$ID]["field"];
   $value = $data["ITEM_$num"];

   switch ($table.'.'.$field) {
      case "glpi_plugin_meteofrancehelpdesk_groups_levels.level" :
         if (!empty($value)) {
            return $LANG['plugin_meteofrancehelpdesk']['title'][3+$value];
         }
   }
   return "";
}


// Display specific massive actions for plugin fields
function plugin_meteofrancehelpdesk_MassiveActionsFieldsDisplay($options=array()) {
   global $LANG;

   $table     = $options['options']['table'];
   $field     = $options['options']['field'];
   $linkfield = $options['options']['linkfield'];


   // Table fields
   switch ($table.".".$field) {
      case "glpi_plugin_meteofrancehelpdesk_groups_levels.level" :
         Dropdown::showFromArray('level', 
                                 array(NULL => "---",
                                       1    => $LANG['plugin_meteofrancehelpdesk']['title'][4],
                                       2    => $LANG['plugin_meteofrancehelpdesk']['title'][5],
                                       3    => $LANG['plugin_meteofrancehelpdesk']['title'][6],
                                       4    => $LANG['plugin_meteofrancehelpdesk']['title'][7]));
         return true;
   }

   // Need to return false on non display item
   return false;
}


// Hook done on update item case
function plugin_pre_item_update_meteofrancehelpdesk($item) {
   if (isset($_REQUEST['massiveaction']) && isset($_REQUEST['level'])) {
      $group_level = new PluginMeteofrancehelpdeskGroup_Level;
      foreach($_REQUEST['item'] as $groups_id => $val) {
         if(!$group_level->getFromDB($groups_id)) {
            $group_level->add(array('groups_id'=> $groups_id, 
                                    'level'    => $_REQUEST['level']));
         } else {
            $group_level->update(array('groups_id'=> $groups_id, 
                                       'level'    => $_REQUEST['level']));
         }
      }
   }   
   return true;
}
?>