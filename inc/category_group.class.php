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

class PluginItilcategorygroupsCategory_Group extends CommonDBChild {
   static public $itemtype = "PluginItilcategorygroupsCategory";
   static public $items_id = "plugin_itilcategorygroups_categories_id";

   static function install(Migration $migration) {
      /** @var \DBmysql $DB */
      global $DB;

      $default_charset = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

      $table = getTableForItemType(__CLASS__);
      if (!$DB->tableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
         `id`                                      INT     {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `plugin_itilcategorygroups_categories_id` INT     {$default_key_sign} NOT NULL DEFAULT '0',
         `level`                                   TINYINT NOT NULL DEFAULT '0',
         `itilcategories_id`                       INT     {$default_key_sign} NOT NULL DEFAULT '0',
         `groups_id`                               INT     {$default_key_sign} NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         UNIQUE KEY `group_lvl_unicity` (plugin_itilcategorygroups_categories_id, level, groups_id),
         KEY `plugin_itilcategorygroups_categories_id` (`plugin_itilcategorygroups_categories_id`),
         KEY `level`                                   (`level`),
         KEY `itilcategories_id`                       (`itilcategories_id`),
         KEY `groups_id`                               (`groups_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->doQuery($query);
      }

      $parent_table = "glpi_plugin_itilcategorygroups_categories";

      //we must migrate groups datas in sub table
      if ($DB->fieldExists($parent_table, 'groups_id_levelone')) {
         $all_lvl = $cat_groups = [];

         //foreach old levels
         foreach ([1=>'one', 2=>'two', 3=>'three', 4=>'four'] as $lvl_num => $lvl_str) {
            $query = "SELECT id, itilcategories_id, groups_id_level$lvl_str FROM $parent_table";
            $res = $DB->doQuery($query);
            while ($data = $DB->fetchAssoc($res)) {
               //specific case (all group of this lvl), store it for further treatment
               if ($data["groups_id_level$lvl_str"] == -1) {
                  $all_lvl[$data['itilcategories_id']][$lvl_num] = $lvl_str;
               }

               if ($data["groups_id_level$lvl_str"] > 0) {
                  $cat_groups[] = [
                     'plugin_itilcategorygroups_categories_id' => $data['id'],
                     'level'                                   => $lvl_num,
                     'itilcategories_id'                       => $data['itilcategories_id'],
                     'groups_id'                               => $data["groups_id_level$lvl_str"]];
               }
            }

            //insert "all groups for this lvl'
            foreach ($all_lvl as $itilcategories_id => $lvl) {
               foreach ($lvl as $lvl_num => $lvl_str) {
                  $DB->doQuery("UPDATE $parent_table SET view_all_lvl$lvl_num = 1
                              WHERE itilcategories_id = $itilcategories_id");
               }
            }

            //insert groups in sub table
            foreach ($cat_groups as $cat_groups_data) {
               $DB->updateOrInsert('glpi_plugin_itilcategorygroups_categories_groups', [
                  'plugin_itilcategorygroups_categories_id' => $cat_groups_data['plugin_itilcategorygroups_categories_id'],
                  'level'                                   => $cat_groups_data['level'],
                  'groups_id'                               => $cat_groups_data['groups_id'],
                  'itilcategories_id'                       => $cat_groups_data['itilcategories_id']
               ], [
                  'plugin_itilcategorygroups_categories_id' => $cat_groups_data['plugin_itilcategorygroups_categories_id'],
                  'level'                                   => $cat_groups_data['level'],
                  'groups_id'                               => $cat_groups_data['groups_id'],
               ]);
            }
         }

         //drop migrated fields
         $migration->dropField($parent_table, "groups_id_levelone");
         $migration->dropField($parent_table, "groups_id_leveltwo");
         $migration->dropField($parent_table, "groups_id_levelthree");
         $migration->dropField($parent_table, "groups_id_levelfour");
         $migration->migrationOneTable($parent_table);
      }

      return true;
   }

   static function uninstall() {
      /** @var \DBmysql $DB */
      global $DB;

      $table = getTableForItemType(__CLASS__);
      $DB->doQuery("DROP TABLE IF EXISTS`$table`");
      return true;
   }
}
