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

class PluginItilcategorygroupsCategory_Group extends CommonDropdown {
   
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
   
   static function getGroupsForCategory($categories_id, $params = array()) {
      global $DB;
      
      $groups                  = array();
      $category                = new ITILCategory();
      $table                   = getTableForItemType(__CLASS__);
      $options['entities_id']  = 0;
      $options['is_recursive'] = 0;
      $options['condition']    = " AND `is_incident`='1'";
      foreach ($params as $key => $value) {
         $options[$key] = $value;
      }

      if ($category->getFromDB($categories_id)) {
         $query = "SELECT *
         FROM `$table`
         WHERE `itilcategories_id`='$categories_id' ".$options['condition'];
         $query.= getEntitiesRestrictRequest(" AND ", $table, 'entities_id',
                                             $options['entities_id'],
                                             $options['is_recursive']);
         $query.= " AND `is_active`='1' ORDER BY `entities_id` DESC LIMIT 1";
         foreach ($DB->request($query) as $data) {
            $groups = $data;
            break;
         }
         if (empty($groups) && !empty($category->fields['itilcategories_id'])) {
            return self::getGroupsForCategory($category->fields['itilcategories_id'], $params);
         }
      }
      return $groups;
   }

   
   function showForm($id, $options = array()) {

      if (!$this->can($id, 'r')) {
         return false;
      }

      $this->showTabs($options);

      $this->showFormHeader($options);
      
      echo "<tr>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Active')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "</td></tr>";

      echo "<tr>";
      echo "<td>".__('Category')."</td>";
      echo "<td>";
      Dropdown::show('ITILCategory', array('value' => $this->fields['itilcategories_id']));
      echo "</td><td colspan='2'></td></tr>";
      
      
      echo "<tr>";
      echo "<td>".__('Visible for an incident')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_incident', $this->fields['is_incident']);
      echo "</td>";
      echo "<td>".__('Visible for a request')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_request', $this->fields['is_request']);
      echo "</td></tr>";
      
      echo "<tr><td>".ucfirst(__('Level 1','itilcategorygroups'))."</td>";
      echo "<td>";
      Dropdown::show('Group', array('name'      => 'groups_id_levelone',
                                    'condition' => "`is_assign`='1'",
                                    'value'     => $this->fields['groups_id_levelone'], 
                                    'toadd'     => array('all' => __('All')), 
                                    'used' => self::getOthersGroupsID(1)));
      echo "</td>";
      echo "<td>".ucfirst(__('Level 2','itilcategorygroups'))."</td>";
      echo "<td>";
      Dropdown::show('Group', array('name'      => 'groups_id_leveltwo',
                                    'condition' => "`is_assign`='1'",
                                    'value'     => $this->fields['groups_id_leveltwo'], 
                                    'toadd'     => array('all' => __('All')), 
                                    'used' => self::getOthersGroupsID(2)));
      echo "</td></tr>";
      
      echo "<tr><td>".ucfirst(__('Level 3','itilcategorygroups'))."</td>";
      echo "<td>";
      Dropdown::show('Group', array('name'      => 'groups_id_levelthree',
                                    'condition' => "`is_assign`='1'",
                                    'value'     => $this->fields['groups_id_levelthree'], 
                                    'toadd'     => array('all' => __('All')), 
                                    'used' => self::getOthersGroupsID(3)));
      echo "</td>";
      echo "<td>".ucfirst(__('Level 4','itilcategorygroups'))."</td>";
      echo "<td>";
      Dropdown::show('Group', array('name'      => 'groups_id_levelfour',
                                    'condition' => "`is_assign`='1'",
                                    'value'     => $this->fields['groups_id_levelfour'], 
                                    'toadd'     => array('all' => __('All')), 
                                    'used' => self::getOthersGroupsID(4)));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>". __('Comments') . ":  </td>";
      echo "<td colspan='3' align='center'>";
      echo "<textarea cols='50' rows='5' name='comment'>" . $this->fields["comment"] .
        "</textarea>";
      echo "</td></tr>";

      $this->showFormButtons($options);
      
      Html::closeForm();
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
      $tab[26]['linkfield']     = 'groups_id_levelone';
      $tab[26]['name']          = __('Level 1','itilcategorygroups');

      $tab[27]['table']         = 'glpi_groups';
      $tab[27]['field']         = 'name';
      $tab[27]['linkfield']     = 'groups_id_leveltwo';
      $tab[27]['name']          = __('Level 2','itilcategorygroups');
      
      $tab[28]['table']         = 'glpi_groups';
      $tab[28]['field']         = 'name';
      $tab[28]['linkfield']     = 'groups_id_levelthree';
      $tab[28]['name']          = __('Level 3','itilcategorygroups');
  
      $tab[29]['table']         = 'glpi_groups';
      $tab[29]['field']         = 'name';
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
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `is_active` tinyint(1) NOT NULL DEFAULT '0',
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
         `comment` text COLLATE utf8_unicode_ci,
         `date_mod` date default NULL,
         `itilcategories_id` int(11) NOT NULL DEFAULT '0',
         `groups_id_levelone` int(11) NOT NULL DEFAULT '0',
         `groups_id_leveltwo` int(11) NOT NULL DEFAULT '0',
         `groups_id_levelthree` int(11) NOT NULL DEFAULT '0',
         `groups_id_levelfour` int(11) NOT NULL DEFAULT '0',
         `entities_id` int(11) NOT NULL DEFAULT '0',
         `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
         `is_incident` tinyint(1) NOT NULL DEFAULT '0',
         `is_request` tinyint(1) NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `itilcategories_id` (`itilcategories_id`),
         KEY `is_incident` (`is_incident`),
         KEY `is_request` (`is_request`),
         KEY `is_recursive` (`is_recursive`),
         KEY date_mod (date_mod)
         ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";
         $DB->query($query);
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