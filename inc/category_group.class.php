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

class PluginMeteofrancehelpdeskCategory_Group extends CommonDropdown {
   
   public $first_level_menu  = "plugins";
   public $second_level_menu = "meteofrancehelpdesk";
    
   var $dohistory = true;
   
   static function getTypeName($nb=0) {
      global $LANG;
      return $LANG['plugin_meteofrancehelpdesk']['title'][2];
   }

   function canView() {
      return Session::haveRight('config', 'r');
   }
   
   function canCreate() {
      return Session::haveRight('config', 'w');
   }
   
   static function getGroupsForCategory($categories_id, $params = array()) {
      global $DB;
      $groups                  = array();
      $obj                     = new self();
      $table                   = getTableForItemType(__CLASS__);
      $options['entities_id']  = 0;
      $options['is_recursive'] = 0;
      $options['condition']    = "AND `is_incident`='1'";
      foreach ($params as $key => $value) {
         $options[$key] = $value;
      }
      if ($obj->getFromDB($categories_id)) {
         $query = "SELECT *
         FROM `$table`
         WHERE `itilcategories_id`='$categories_id'".$options['condition'];
         $query.= getEntitiesRestrictRequest(" AND ", $table, 'entities_id',
                                             $obj->fields['entities_id'],
                                             $obj->fields['is_recursive']);
          foreach ($DB->request($query) as $data) {
             $groups[] = $data;
          }
      } else {
         return $groups;
      }
   }

   static function configExistsForEntity($categories_id, $entities_id,
                                           $condition = "AND `is_incident`='1'") {
      $query  = "SELECT *
                 FROM `$table`
                 WHERE `itilcategories_id`='$categories_id'".$condition;
      $query .= getEntitiesRestrictRequest(" AND ", $table, 'entities_id', $entities_id, false);
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         return true;
      } else {
         return false;
      }
   }
   
   static function showForCategory(ItilCategory $item) {
      global $LANG;
      $obj = new self();
      
      echo "<form name='categories_groups' method='post'>";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";


      echo "<tr><td class='tab_bg_2 center' colspan='4'>";
      echo "<input type='submit' class='submit' value='".$LANG['buttons'][2]."' name='update'>";
      echo "</td></tr>";
      echo "</table></div>";
      Html::closeForm();
      
   }
   
   function showForm($id, $options = array()) {
      global $LANG;
      if (!$this->can($id, 'r')) {
         return false;
      }
      $this->showTabs($options);
      $this->showFormHeader($options);
      
      echo "<tr>";
      echo "<td>".$LANG['common'][16]."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".$LANG['common'][36]."</td>";
      echo "<td>";
      Dropdown::show('ITILCategory', array('value' => $this->fields['itilcategories_id']));
      echo "</td></tr>";
      
      echo "<tr>";
      echo "<td>".$LANG['job'][70]."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_incident', $this->fields['is_incident']);
      echo "</td>";
      echo "<td>".$LANG['job'][71]."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_request', $this->fields['is_request']);
      echo "</td></tr>";
      
      echo "<tr><td>".$LANG['plugin_meteofrancehelpdesk']['title'][4]."</td>";
      echo "<td>";
      Dropdown::show('Group', array('name'     => 'groups_id_levelone',
                                    'condition' => "`is_assign`='1'",
                                     'value'    => $this->fields['groups_id_levelone']));
      echo "</td>";
      echo "<td>".$LANG['plugin_meteofrancehelpdesk']['title'][5]."</td>";
      echo "<td>";
      Dropdown::show('Group', array('name'     => 'groups_id_leveltwo',
                                    'condition' => "`is_assign`='1'",
                                    'value'    => $this->fields['groups_id_leveltwo']));
      echo "</td></tr>";
      
      echo "<tr><td>".$LANG['plugin_meteofrancehelpdesk']['title'][6]."</td>";
      echo "<td>";
      Dropdown::show('Group', array('name'     => 'groups_id_levelthree',
                                    'condition' => "`is_assign`='1'",
                                     'value'    => $this->fields['groups_id_levelthree']));
      echo "</td>";
      echo "<td>".$LANG['plugin_meteofrancehelpdesk']['title'][7]."</td>";
      echo "<td>";
      Dropdown::show('Group', array('name'     => 'groups_id_levelfour',
                                    'condition' => "`is_assign`='1'",
                                     'value'    => $this->fields['groups_id_levelfour']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][25] . ":  </td>";
      echo "<td colspan='3' align='center'>";
      echo "<textarea cols='50' rows='5' name='comment'>" . $this->fields["comment"] .
        "</textarea>";
      echo "</td></tr>";
      $this->showFormButtons($options);
      Html::closeForm();
   }

   function getSearchOptions() {
      global $LANG;
   
      $tab = array();
   
      $tab['common'] = $LANG['plugin_meteofrancehelpdesk']['title'][2];
   
      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['checktype']     = 'text';
      $tab[1]['displaytype']   = 'text';
      $tab[1]['injectable']    = true;
      $tab[1]['massiveaction'] = false;
      
      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'is_incident';
      $tab[2]['name']         = $LANG['job'][70];
      $tab[2]['datatype']      = 'bool';
      $tab[2]['checktype']     = 'bool';
      $tab[2]['displaytype']   = 'bool';
      $tab[2]['injectable']    = true;
   
      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'is_request';
      $tab[3]['name']         = $LANG['job'][71];
      $tab[3]['datatype']      = 'bool';
      $tab[3]['checktype']     = 'bool';
      $tab[3]['displaytype']   = 'bool';
      $tab[3]['injectable']    = true;

      $tab[4]['table']         = 'glpi_itilcategories';
      $tab[4]['field']         = 'name';
      $tab[4]['name']          = $LANG['common'][36];
      $tab[4]['datatype']      = 'itemlink';
      $tab[4]['checktype']     = 'text';
      $tab[4]['displaytype']   = 'text';
      $tab[4]['injectable']    = true;
      
      $tab[16]['table']         = $this->getTable();
      $tab[16]['field']         = 'comment';
      $tab[16]['name']          = $LANG['common'][25];
      $tab[16]['datatype']      = 'text';
      $tab[16]['checktype']     = 'text';
      $tab[16]['displaytype']   = 'multiline_text';
      $tab[16]['injectable']    = true;
   
      $tab[26]['table']         = 'glpi_groups';
      $tab[26]['field']         = 'name';
      $tab[26]['linkfield']     = 'groups_id_levelone';
      $tab[26]['name']          = $LANG['plugin_meteofrancehelpdesk']['title'][4];

      $tab[27]['table']         = 'glpi_groups';
      $tab[27]['field']         = 'name';
      $tab[27]['linkfield']     = 'groups_id_leveltwo';
      $tab[27]['name']          = $LANG['plugin_meteofrancehelpdesk']['title'][5];
      
      $tab[28]['table']         = 'glpi_groups';
      $tab[28]['field']         = 'name';
      $tab[28]['linkfield']     = 'groups_id_levelthree';
      $tab[28]['name']          = $LANG['plugin_meteofrancehelpdesk']['title'][6];
  
      $tab[29]['table']         = 'glpi_groups';
      $tab[29]['field']         = 'name';
      $tab[29]['linkfield']     = 'groups_id_levelfour';
      $tab[29]['name']          = $LANG['plugin_meteofrancehelpdesk']['title'][7];
      
      /* id */
      $tab[30]['table']         = $this->getTable();
      $tab[30]['field']         = 'id';
      $tab[30]['name']          = $LANG['common'][2];
      $tab[30]['injectable']    = false;
      $tab[30]['massiveaction'] = false;
   
      $tab[35]['table']          = $this->getTable();
      $tab[35]['field']          = 'date_mod';
      $tab[35]['massiveaction']  = false;
      $tab[35]['name']           = $LANG['common'][26];
      $tab[35]['datatype']       = 'datetime';
      $tab[35]['massiveaction']  = false;
   
      /* entity */
      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = $LANG['entity'][0];
      $tab[80]['injectable']    = false;
      $tab[80]['massiveaction'] = false;
   
      $tab[86]['table']         = $this->getTable();
      $tab[86]['field']         = 'is_recursive';
      $tab[86]['name']          = $LANG['entity'][9];
      $tab[86]['datatype']      = 'bool';
      $tab[86]['checktype']     = 'bool';
      $tab[86]['displaytype']   = 'bool';
      $tab[86]['injectable']    = true;
   
      return $tab;
   }
    
   //----------------------------- Tabs management --------------------------//
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;
   
      switch ($item->getType()) {
         case 'ITILCategory' :
            if (Session::haveRight('config', 'r')) {
               return $LANG['plugin_meteofrancehelpdesk']['title'][3];
            }
            break;
      }
      return '';
   }
   
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
   
      if ($item->getType()=='ITILCategory') {
         self::showForCategory($item);
      }
      return true;
   }
   

   //----------------------------- Install process --------------------------//
   static function install(Migration $migration) {
      global $DB;
      
      $table = getTableForItemType(__CLASS__);
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
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
      $DB->query("DROP TABLE `$table`");
      return true;
   }
}