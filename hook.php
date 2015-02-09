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

function plugin_itilcategorygroups_install() {
   $migration = new Migration("0.84");
   
   //order is important for install
   include_once(GLPI_ROOT."/plugins/itilcategorygroups/inc/category.class.php");
   include_once(GLPI_ROOT."/plugins/itilcategorygroups/inc/category_group.class.php");
   include_once(GLPI_ROOT."/plugins/itilcategorygroups/inc/group_level.class.php");
   PluginItilcategorygroupsCategory::install($migration);
   PluginItilcategorygroupsCategory_Group::install($migration);
   PluginItilcategorygroupsGroup_Level::install($migration);
   return true;
}

function plugin_itilcategorygroups_uninstall() {
   include_once(GLPI_ROOT."/plugins/itilcategorygroups/inc/category_group.class.php");
   include_once(GLPI_ROOT."/plugins/itilcategorygroups/inc/category.class.php");
   include_once(GLPI_ROOT."/plugins/itilcategorygroups/inc/group_level.class.php");
   PluginItilcategorygroupsCategory_Group::uninstall();
   PluginItilcategorygroupsCategory::uninstall();
   PluginItilcategorygroupsGroup_Level::uninstall();
   return true;
}

function plugin_itilcategorygroups_getAddSearchOptions($itemtype) {
   if (isset($_SESSION['glpiactiveentities'])) {
      $options = PluginItilcategorygroupsGroup_Level::getAddSearchOptions($itemtype);
      return $options;
   } else {
      return NULL;
   }
}

function plugin_itilcategorygroups_giveItem($type,$ID,$data,$num) {

   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$ID]["table"];
   $field = $searchopt[$ID]["field"];
   $value = $data["ITEM_$num"];

   switch ($table.'.'.$field) {
      case "glpi_plugin_itilcategorygroups_groups_levels.lvl" :
         if (! empty($value)) {
            switch($value) {
               case 1: return __('Level 1', 'itilcategorygroups'); break;
               case 2: return __('Level 2', 'itilcategorygroups'); break;
               case 3: return __('Level 3', 'itilcategorygroups'); break;
               case 4: return __('Level 4', 'itilcategorygroups'); break;
            }
         }
   }
   return "";
}

// Display specific massive actions for plugin fields
function plugin_itilcategorygroups_MassiveActionsFieldsDisplay($options=array()) {

   $table     = $options['options']['table'];
   $field     = $options['options']['field'];
   $linkfield = $options['options']['linkfield'];

   // Table fields
   switch ($table.".".$field) {
      case "glpi_plugin_itilcategorygroups_groups_levels.lvl" :
         Dropdown::showFromArray('lvl', 
                                 array(NULL => "---",
                                       1    => __('Level 1', 'itilcategorygroups'),
                                       2    => __('Level 2', 'itilcategorygroups'),
                                       3    => __('Level 3', 'itilcategorygroups'),
                                       4    => __('Level 4', 'itilcategorygroups')));
         return true;
   }

   // Need to return false on non display item
   return false;
}


// Hook done on update item case
function plugin_pre_item_update_itilcategorygroups($item) {
   if (isset($_REQUEST['massiveaction']) && isset($_REQUEST['lvl'])) {
      $group_level = new PluginItilcategorygroupsGroup_Level();
      foreach($_REQUEST['item'] as $groups_id => $val) {
         if(! $group_level->getFromDB($groups_id)) {
            $group_level->add(array('groups_id'=> $groups_id, 
                                    'lvl'    => $_REQUEST['lvl']));
         } else {
            $group_level->update(array('groups_id'=> $groups_id, 
                                       'lvl'    => $_REQUEST['lvl']));
         }
      }
   }   
   return $item;
}
?>