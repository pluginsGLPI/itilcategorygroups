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

class PluginItilcategorygroupsCategory extends CommonDropdown {
   
   public $first_level_menu  = "plugins";
   public $second_level_menu = "itilcategorygroups";
    
   var $dohistory = true;
   
   static function getTypeName($nb=0) {
      return __('Link ItilCategory - Groups','itilcategorygroups');
   }

   static function canView() {
      return Session::haveRight('config', 'r');
   }
   
   static function canCreate() {
      return Session::haveRight('config', 'w');
   }
   function showForm($id, $options = array()) {

      if (!$this->can($id, 'r')) {
         return false;
      }

      $this->showTabs($options);

      $this->showFormHeader($options);
      
      echo "<tr>";
      echo "<td>".__('Name')." :</td>";
      echo "<td style='width:30%'>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Active')." :</td>";
      echo "<td style='width:30%'>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "</td></tr>";

      echo "<tr>";
      echo "<td>".__('Category')." :</td>";
      echo "<td>";
      Dropdown::show('ITILCategory', array('value' => $this->fields['itilcategories_id']));
      echo "</td><td colspan='2'></td></tr>";
        
      echo "<tr>";
      echo "<td>".__('Visible for an incident')." :</td>";
      echo "<td>";
      Dropdown::showYesNo('is_incident', $this->fields['is_incident']);
      echo "</td>";
      echo "<td>".__('Visible for a request')." :</td>";
      echo "<td>";
      Dropdown::showYesNo('is_request', $this->fields['is_request']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Comments') . ":  </td>";
      echo "<td align='left'>";
      echo "<textarea style='width:100%; height:70px;' name='comment'>" . $this->fields["comment"] .
        "</textarea>";
      echo "</td></tr>";

      echo "<tr><td colspan='4'><hr></td></tr>";
      
      echo "<tr><td>".ucfirst(__('Level 1','itilcategorygroups'))." :</td>";
      echo "<td>";
      self::multipleDropdownGroup(1, $this->fields['itilcategories_id'], $this->fields['view_all_lvl1']);
      echo "</td>";
      echo "<td>".ucfirst(__('Level 2','itilcategorygroups'))." :</td>";
      echo "<td>";
      self::multipleDropdownGroup(2, $this->fields['itilcategories_id'], $this->fields['view_all_lvl2']);
      echo "</td></tr>";
      
      echo "<tr><td>".ucfirst(__('Level 3','itilcategorygroups'))." :</td>";
      echo "<td>";
      self::multipleDropdownGroup(3, $this->fields['itilcategories_id'], $this->fields['view_all_lvl3']);
      echo "</td>";
      echo "<td>".ucfirst(__('Level 4','itilcategorygroups'))." :</td>";
      echo "<td>";
      self::multipleDropdownGroup(4, $this->fields['itilcategories_id'], $this->fields['view_all_lvl4']);
      echo "</td></tr>";

      $this->showFormButtons($options);
      Html::closeForm();

      //enable chosen for multiple selector
      if(!preg_match('/(?i)msie [1-8]/',$_SERVER['HTTP_USER_AGENT'])) {
         echo "<script type='text/javascript'>
            initChosen();
         </script>";
      }
   }

   static function multipleDropdownGroup($level, $itilcategories_id, $all) {
      global $DB;

      // find current values for this select
      $values = array();
      if (!empty($itilcategories_id)) {
         $query_val = "SELECT groups_id
                       FROM glpi_plugin_itilcategorygroups_categories_groups
                       WHERE itilcategories_id = $itilcategories_id
                        AND level = $level";
         $res_val = $DB->query($query_val);    
         while ($data_val = $DB->fetch_assoc($res_val)) {
            $values[] = $data_val['groups_id'];
         }
      }

      // find possible values for this select
      $query_gr = "SELECT gr.id, gr.name 
                FROM glpi_groups gr
                INNER JOIN glpi_plugin_itilcategorygroups_groups_levels gr_lvl
                  ON gr_lvl.groups_id = gr.id
                  AND gr_lvl.lvl = ".intval($level);
      $res_gr = $DB->query($query_gr);

      $checked = $disabled = "";
      if ($all == 1) {
         $checked = "checked='checked'";
         $disabled = "disabled='disabled'";
      }
      
      echo "<span id='select_level_$level'>";
      echo "<select name='groups_id_level".$level."[]' $disabled multiple='multiple' class='chzn-select' data-placeholder='-----'>";
      while ($data_gr = $DB->fetch_assoc($res_gr)) {
         $selected = "";
         if (in_array($data_gr['id'], $values)) {
            $selected = "selected";
         }
         echo "<option value='".$data_gr['id']."' $selected>".$data_gr['name']."</option>";
      }
      echo "</select>";
      echo "</span>";
      echo "<input type='hidden' name='view_all_lvl$level' value='0'>";
      echo "&nbsp;".__('All')." ?&nbsp;".
           "<input type='checkbox' name='view_all_lvl$level' $checked onclick='toggleSelect($level)'/>";
   }

   function prepareInputForAdd($input) {
      $cat = new self;
      $found_cat = $cat->find("itilcategories_id = ".$this->input["itilcategories_id"]);
      if (count($found_cat) > 0) {
         Session::addMessageAfterRedirect(__("A link with this category already exists", "itilcategorygroups"));
         return false;
      }
     
      return $this->prepareInputForUpdate($input);
   }

   function prepareInputForUpdate($input) {
      foreach ($input as &$value) {
         if ($value === "on") {
            $value = 1;
         } 
      }
      return $input;
   }

   function post_addItem() {
      $this->input["id"] = $this->fields["id"];
      $this->post_updateItem();
   }

   function post_updateItem($history=1) {    
      $cat_group = new PluginItilcategorygroupsCategory_Group;
     
      for ($lvl = 1; $lvl <= 4; $lvl++) {

         if ($this->input["view_all_lvl$lvl"] != 1) {
            //delete old groups values
            $found_cat_groups = $cat_group->find("itilcategories_id = ".$this->input["itilcategories_id"].
                                                 " AND level = $lvl");
            foreach ($found_cat_groups as $id => $current_cat_group) {
               $cat_group->delete(array('id' => $current_cat_group['id']));
            }

             //insert new saved
            if (isset($this->input["groups_id_level$lvl"])) {
               foreach ($this->input["groups_id_level$lvl"] as $groups_id) {
                  $cat_group->add(array('plugin_itilcategorygroups_categories_id' => $this->input["id"],
                                        'level'                                   => $lvl,
                                        'itilcategories_id'                       => $this->input["itilcategories_id"],
                                        'groups_id'                               => $groups_id));
               }
            }
         }
      }

   }

   static function filteredDropdownAssignGroups($tickets_id, $itilcategories_id) {
      $group  = new Group();
      $ticket = new Ticket();
      $params = array('entities_id' => $_SESSION['glpiactive_entity'],
                      'is_recursive' => 1);

      echo "<option value='0'>".Dropdown::EMPTY_VALUE."</option>";

      if (!empty($tickets_id) && $ticket->getFromDB($tickets_id)) {
         // == UPDATE EXISTING TICKET ==
         $params['entities_id'] = $ticket->fields['entities_id'];

         if ($ticket->fields['type'] == Ticket::DEMAND_TYPE) {
            $params['condition'] = " AND `is_request`='1'";
         } else {
            $params['condition'] = " AND `is_incident`='1'";
         }
      } 

      $found_groups = self::getGroupsForCategory($itilcategories_id, $params);
      if (!empty($found_groups)) {
         for ($lvl = 1; $lvl <= 4; $lvl++) {
            if (isset($found_groups['groups_id_level'.$lvl])) {
               if ($found_groups['groups_id_level'.$lvl] === "all") {
                  foreach (PluginItilcategorygroupsGroup_Level::getAllGroupForALevel($lvl, $params['entities_id']) as $groups_id) {
                     if ($group->getFromDB($groups_id)) {
                        echo "<option value='".$group->getID()."'>".$group->getName()."</option>";
                     }
                  }

               } else {
                  foreach ($found_groups['groups_id_level'.$lvl] as $groups_id) {
                     $group->getFromDB($groups_id);
                     echo "<option value='".$group->getID()."'>".$group->getName()."</option>";
                  }
               }
            }
         }
      }
   }

   static function getGroupsForCategory($itilcategories_id, $params = array()) {
      global $DB;
      
      $groups   = array();
      $category = new ITILCategory();
      $table    = getTableForItemType(__CLASS__);

      //define default options
      $options['entities_id']  = 0;
      $options['is_recursive'] = 0;
      $options['condition']    = " AND cat.is_incident = '1'";

      // override default options with params
      foreach ($params as $key => $value) {
         $options[$key] = $value;
      }

      if ($category->getFromDB($itilcategories_id)) {
         $entity_restrict = getEntitiesRestrictRequest(" AND ", "cat", "entities_id",
                                                       $options['entities_id'],
                                                       $options['is_recursive']);
         $query = "SELECT 
                     cat.*, 
                     GROUP_CONCAT(\"{'groups_id':\", 
                                  cat_gr.groups_id, 
                                  \", 'level': \",  
                                  cat_gr.level, 
                                  \"}\") as groups_level
                   FROM `$table` cat
                   LEFT JOIN glpi_plugin_itilcategorygroups_categories_groups cat_gr
                     ON cat_gr.plugin_itilcategorygroups_categories_id = cat.id
                   WHERE cat.itilcategories_id = '$itilcategories_id' ".
                   $options['condition'].$entity_restrict.
                   " AND cat.is_active = '1' 
                   ORDER BY cat.entities_id DESC";
         foreach ($DB->request($query) as $data) {
            $groups_level = json_decode("[".$data['groups_level']."]", true);
            
            for ($level = 1; $level <= 4; $level++) {
               if ($data["view_all_lvl$level"]) {
                  $groups["groups_id_level$level"] = "all";
               } else {
                  foreach ($groups_level as $current_group_level) {
                     if ($current_group_level['level'] == $level) {
                        $groups["groups_id_level$level"][] = $current_group_level['groups_id'];
                     }
                  }
               }
            }
         }
      }

      return $groups;
   }

   
   static function getOthersGroupsID($level = 0) {
      global $DB;

      $groups_id = array();
      $res = $DB->query("SELECT gr.id 
      FROM glpi_groups gr
      LEFT JOIN glpi_plugin_itilcategorygroups_groups_levels gl
         ON gl.groups_id = gr.id
      WHERE gl.lvl != $level
      OR gl.lvl IS NULL");
      while ($row = $DB->fetch_assoc($res)) {
         $groups_id[$row['id']] = $row['id'];
      }

      return $groups_id;
   }

   function getSearchOptions() {
      $tab = array();
   
      $tab['common'] = __('Link ItilCategory - Groups','itilcategorygroups');
   
      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['checktype']     = 'text';
      $tab[1]['displaytype']   = 'text';
      $tab[1]['injectable']    = true;
      $tab[1]['massiveaction'] = false;
      
      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'is_incident';
      $tab[2]['name']          = __('Visible for an incident');
      $tab[2]['datatype']      = 'bool';
      $tab[2]['checktype']     = 'bool';
      $tab[2]['displaytype']   = 'bool';
      $tab[2]['injectable']    = true;
   
      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'is_request';
      $tab[3]['name']          = __('Visible for a request');
      $tab[3]['datatype']      = 'bool';
      $tab[3]['checktype']     = 'bool';
      $tab[3]['displaytype']   = 'bool';
      $tab[3]['injectable']    = true;

      $tab[4]['table']         = 'glpi_itilcategories';
      $tab[4]['field']         = 'name';
      $tab[4]['name']          = __('Category');
      $tab[4]['datatype']      = 'itemlink';
      $tab[4]['checktype']     = 'text';
      $tab[4]['displaytype']   = 'text';
      $tab[4]['injectable']    = true;
      
      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'is_active';
      $tab[5]['name']          = __('Active');
      $tab[5]['datatype']      = 'bool';
      $tab[5]['checktype']     = 'bool';
      $tab[5]['displaytype']   = 'bool';
      $tab[5]['injectable']    = true;
      
      $tab[16]['table']         = $this->getTable();
      $tab[16]['field']         = 'comment';
      $tab[16]['name']          = __('Comments');
      $tab[16]['datatype']      = 'text';
      $tab[16]['checktype']     = 'text';
      $tab[16]['displaytype']   = 'multiline_text';
      $tab[16]['injectable']    = true;
   
      $tab[26]['table']         = 'glpi_groups';
      $tab[26]['field']         = 'name';
      $tab[26]['datatype']      = 'dropdown';
      $tab[26]['linkfield']     = 'groups_id_levelone';
      $tab[26]['name']          = __('Level 1','itilcategorygroups');

      $tab[27]['table']         = 'glpi_groups';
      $tab[27]['field']         = 'name';
      $tab[27]['datatype']      = 'dropdown';
      $tab[27]['linkfield']     = 'groups_id_leveltwo';
      $tab[27]['name']          = __('Level 2','itilcategorygroups');
      
      $tab[28]['table']         = 'glpi_groups';
      $tab[28]['field']         = 'name';
      $tab[28]['datatype']      = 'dropdown';
      $tab[28]['linkfield']     = 'groups_id_levelthree';
      $tab[28]['name']          = __('Level 3','itilcategorygroups');
  
      $tab[29]['table']         = 'glpi_groups';
      $tab[29]['field']         = 'name';
      $tab[29]['datatype']      = 'dropdown';
      $tab[29]['linkfield']     = 'groups_id_levelfour';
      $tab[29]['name']          = __('Level 4','itilcategorygroups');
      
      /* id */
      $tab[30]['table']         = $this->getTable();
      $tab[30]['field']         = 'id';
      $tab[30]['name']          = __('ID');
      $tab[30]['injectable']    = false;
      $tab[30]['massiveaction'] = false;
   
      $tab[35]['table']          = $this->getTable();
      $tab[35]['field']          = 'date_mod';
      $tab[35]['massiveaction']  = false;
      $tab[35]['name']           = __('Last update');
      $tab[35]['datatype']       = 'datetime';
      $tab[35]['massiveaction']  = false;
   
      /* entity */
      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = __('Entity');
      $tab[80]['injectable']    = false;
      $tab[80]['massiveaction'] = false;
   
      $tab[86]['table']         = $this->getTable();
      $tab[86]['field']         = 'is_recursive';
      $tab[86]['name']          = __('Child entities');
      $tab[86]['datatype']      = 'bool';
      $tab[86]['checktype']     = 'bool';
      $tab[86]['displaytype']   = 'bool';
      $tab[86]['injectable']    = true;
   
      return $tab;
   }
    
   //----------------------------- Install process --------------------------//
   static function install(Migration $migration) {
      global $DB;
      
      $table = getTableForItemType(__CLASS__);

      if (TableExists("glpi_plugin_itilcategorygroups_categories_groups")
          && FieldExists("glpi_plugin_itilcategorygroups_categories_groups", 'is_active')) {
         $migration->renameTable("glpi_plugin_itilcategorygroups_categories_groups", $table);
      }

      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
         `id` INT(11) NOT NULL AUTO_INCREMENT,
         `is_active` TINYINT(1) NOT NULL DEFAULT '0',
         `name` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT '',
         `comment` TEXT COLLATE utf8_unicode_ci,
         `date_mod` DATE default NULL,
         `itilcategories_id` INT(11) NOT NULL DEFAULT '0',
         `view_all_lvl1` TINYINT(1) NOT NULL DEFAULT '0',
         `view_all_lvl2` TINYINT(1) NOT NULL DEFAULT '0',
         `view_all_lvl3` TINYINT(1) NOT NULL DEFAULT '0',
         `view_all_lvl4` TINYINT(1) NOT NULL DEFAULT '0',
         `entities_id` INT(11) NOT NULL DEFAULT '0',
         `is_recursive` TINYINT(1) NOT NULL DEFAULT '0',
         `is_incident` TINYINT(1) NOT NULL DEFAULT '0',
         `is_request` TINYINT(1) NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `itilcategories_id` (`itilcategories_id`),
         KEY `is_incident` (`is_incident`),
         KEY `is_request` (`is_request`),
         KEY `is_recursive` (`is_recursive`),
         KEY date_mod (date_mod)
         ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
         $DB->query($query);
      }

      if (!FieldExists($table, 'view_all_lvl1')) {
         $migration->addField($table, 'view_all_lvl1', "TINYINT(1) NOT NULL DEFAULT '0'", 
                              array('after' => 'itilcategories_id'));
         $migration->addField($table, 'view_all_lvl2', "TINYINT(1) NOT NULL DEFAULT '0'", 
                              array('after' => 'itilcategories_id'));
         $migration->addField($table, 'view_all_lvl3', "TINYINT(1) NOT NULL DEFAULT '0'", 
                              array('after' => 'itilcategories_id'));
         $migration->addField($table, 'view_all_lvl4', "TINYINT(1) NOT NULL DEFAULT '0'", 
                              array('after' => 'itilcategories_id'));
         $migration->migrationOneTable($table);
      }

      
      
      return true;
   }
   
   static function uninstall() {
      global $DB;
      $table = getTableForItemType(__CLASS__);
      $DB->query("DROP TABLE IF EXISTS`$table`");
      return true;
   }
}
