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

class PluginItilcategorygroupsCategory extends CommonDropdown {

   public $first_level_menu      = "plugins";
   public $second_level_menu     = "itilcategorygroups";
   public $display_dropdowntitle = false;

   static $rightname         = 'config';

   var $dohistory = true;

   static function getTypeName($nb = 0) {
      return __('Link ItilCategory - Groups', 'itilcategorygroups');
   }

   static function canCreate() {
      return static::canUpdate();
   }

   static function canPurge() {
      return static::canUpdate();
   }

   function showForm($id, $options = []) {

      if (! $this->can($id, READ)) {
         return false;
      }

      $this->initForm($id);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td><label>".__('Name')." :</label></td>";
      echo "<td style='width:30%'>";
      echo Html::input(
         'name',
         [
            'value' => $this->fields['name'],
         ]
      );
      echo "</td>";

      $rand = mt_rand();
      echo "<td><label for='dropdown_is_active$rand'>".__('Active')." :</label></td>";
      echo "<td style='width:30%'>";
      Dropdown::showYesNo('is_active', $this->fields['is_active'], -1, ['rand' => $rand]);
      echo "</td></tr>";

      $rand = mt_rand();
      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_itilcategories_id$rand'>".__('Category')." :</label></td>";
      echo "<td>";
      Dropdown::show('ITILCategory', [
         'value' => $this->fields['itilcategories_id'],
         'rand' => $rand]);
      echo "</td>";

      // Groups restriction
      $rand = mt_rand();
      echo "<td><label for='dropdown_is_groups_restriction$rand'>".__('Display only the groups of the next level')." :</label></td>";
      echo "<td style='width:30%'>";
      Dropdown::showYesNo('is_groups_restriction', $this->fields['is_groups_restriction'], -1, ['rand' => $rand]);
      echo "</td></tr>";

      $rand = mt_rand();
      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_is_incident$rand'>".__('Visible for an incident')." :</label></td>";
      echo "<td>";
      Dropdown::showYesNo('is_incident', $this->fields['is_incident'], -1, ['rand' => $rand]);
      echo "</td>";

      $rand = mt_rand();
      echo "<td><label for='dropdown_is_request$rand'>".__('Visible for a request')." :</label></td>";
      echo "<td>";
      Dropdown::showYesNo('is_request', $this->fields['is_request'], -1, ['rand' => $rand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='comment'>".__('Comments') . " : </label></td>";
      echo "<td align='left'>";
      echo "<textarea name='comment' id='comment' style='width:100%; height:70px;'>";
      echo $this->fields["comment"] . "</textarea>";
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4'><hr></td></tr>";

      echo "<tr class='tab_bg_1'><td><label for='groups_id_level1[]'>".ucfirst(__('Level 1', 'itilcategorygroups'))." :</label></td>";
      echo "<td>";
      $this->multipleDropdownGroup(1);
      echo "</td>";
      echo "<td><label for='groups_id_level2[]'>".ucfirst(__('Level 2', 'itilcategorygroups'))." :</label></td>";
      echo "<td>";
      $this->multipleDropdownGroup(2);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td><label for='groups_id_level3[]'>".ucfirst(__('Level 3', 'itilcategorygroups'))." :</label></td>";
      echo "<td>";
      $this->multipleDropdownGroup(3);
      echo "</td>";
      echo "<td><label for='groups_id_level4[]'>".ucfirst(__('Level 4', 'itilcategorygroups'))." :</label></td>";
      echo "<td>";
      $this->multipleDropdownGroup(4);
      echo "</td></tr>";

      $this->showFormButtons($options);
      Html::closeForm();

   }

   function multipleDropdownGroup($level) {
      global $DB;

      // find current values for this select
      $values = [];
      if (!$this->isNewItem()) {
         $it = $DB->request([
            'SELECT' => ['groups_id'],
            'FROM'   => 'glpi_plugin_itilcategorygroups_categories_groups',
            'WHERE' => [
               'OR' => [
                   'itilcategories_id' => $this->fields['itilcategories_id'],
                   'plugin_itilcategorygroups_categories_id' => $this->getID()
               ],
               'level' => $level,
            ]
         ]);
         foreach ($it as $data_val) {
            $values[] = $data_val['groups_id'];
         }
      }

      // find possible values for this select
      $join_criteria = [
         'gr_lvl.lvl' => (int) $level,
      ];
      $entity_restrict = getEntitiesRestrictCriteria('gr', '', $_SESSION['glpiactiveentities'], true);
      if (!empty($entity_restrict)) {
          $join_criteria += $entity_restrict;
      }
      $it = $DB->request([
         'SELECT' => ['gr.id', 'gr.name'],
         'FROM'   => 'glpi_groups AS gr',
         'INNER JOIN' => [
            'glpi_plugin_itilcategorygroups_groups_levels AS gr_lvl' => [
               'ON' => [
                  'gr_lvl' => 'groups_id',
                  'gr'     => 'id',
                   $join_criteria
               ]
            ]
         ]
      ]);

      if ($this->fields["view_all_lvl$level"] == 1) {
         $checked = "checked='checked'";
         $disabled = "disabled='disabled'";
      } else {
         $checked = "";
         $disabled = "";
      }

      echo "<span id='select_level_$level'>";
      echo "<select name='groups_id_level".$level."[]' id='groups_id_level".$level."[]' $disabled multiple='multiple' class='chzn-select' data-placeholder='-----' style='width:160px;'>";
      foreach ($it as $data_gr) {
         if (in_array($data_gr['id'], $values)) {
            $selected = "selected";
         } else {
            $selected = "";
         }
         echo "<option value='".$data_gr['id']."' $selected>".$data_gr['name']."</option>";
      }
      echo "</select>";
      echo "</span>";
      echo '<script>$("#select_level_'.$level.' select").select2();</script>';
      echo "<input type='hidden' name='view_all_lvl$level' value='0'>";
      echo "&nbsp;<label for='view_all_lvl$level'>".__('All')." ?&nbsp;</label>".
           "<input type='checkbox' name='view_all_lvl$level' id='view_all_lvl$level' $checked onclick='toggleSelect($level)'/>";
   }

   function prepareInputForAdd($input) {
      $cat = new self();
      $found_cat = $cat->find(['itilcategories_id' => $this->input["itilcategories_id"]]);
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

   function post_updateItem($history = 1) {

      // quick fix :
      if (isset($_REQUEST['massiveaction'])) {
         return;
      }

      $cat_group = new PluginItilcategorygroupsCategory_Group();

      for ($lvl=1; $lvl <= 4; $lvl++) {

         if ($this->input["view_all_lvl$lvl"] != 1) {

            //delete old groups values
            $found_cat_groups = $cat_group->find(
               [
                  'itilcategories_id' => $this->input["itilcategories_id"],
                  'level' => $lvl
               ]
            );
            foreach ($found_cat_groups as $id => $current_cat_group) {
               $cat_group->delete(['id' => $current_cat_group['id']]);
            }

            //insert new saved
            if (isset($this->input["groups_id_level$lvl"])) {
               foreach ($this->input["groups_id_level$lvl"] as $groups_id) {
                  $cat_group->add(['plugin_itilcategorygroups_categories_id' => $this->input["id"],
                                   'level'                                   => $lvl,
                                   'itilcategories_id'                       => $this->input["itilcategories_id"],
                                   'groups_id'                               => $groups_id]);
               }
            }
         }
      }

   }


    static function filterActors(array $params = []): array
    {
        $itemtype = $params['params']['itemtype'];
        $items_id = $params['params']['items_id'];

        if ($itemtype == 'Ticket' && $params['params']['actortype'] == 'assign') {
            $ticket = new Ticket;
            $group  = new Group();

            $group_params = [
                'entities_id'  => $_SESSION['glpiactive_entity'],
                'is_recursive' => 1,
            ];

            $type = (int) ($params['params']['item']['type'] ?? Ticket::INCIDENT_TYPE);

            if (!empty($items_id) && $ticket->getFromDB($items_id)) {
                // == UPDATE EXISTING TICKET ==
                $group_params['entities_id'] = $ticket->fields['entities_id'];
                if ((int) $ticket->fields['type'] === Ticket::DEMAND_TYPE) {
                    $group_params['condition'] = ['is_request' => 1];
                } else {
                    $group_params['condition'] = ['is_incident' => 1];
                }
            } else {
                if ($type === Ticket::DEMAND_TYPE) {
                    $group_params['condition'] = ['is_request' => 1];
                } else {
                    $group_params['condition'] = ['is_incident' => 1];
                }
            }

            $itilcategories_id = $params['params']['item']['itilcategories_id'] ?? $ticket->fields['itilcategories_id'];

            $current_setup = new self;
            $current_setup->getFromDBByCrit([
                'itilcategories_id' => $itilcategories_id
            ]);

            if ($current_setup->fields['is_groups_restriction'] ?? false) {
                // == CHECKS FOR LEVEL VISIBILITY ==
                $level = 0;
                $categoryGroup = new PluginItilcategorygroupsCategory_Group();
                $table = getTableForItemType(get_class($categoryGroup));
                // All groups assigned to the ticket
                foreach ($ticket->getGroups(2) as $element) {
                    $groupsId = $element['groups_id'];
                    $data_level = self::getFirst([
                       'SELECT' => ['level'],
                       'FROM'   => $table,
                       'WHERE' => [
                          'itilcategories_id' => $itilcategories_id,
                          'groups_id'         => $groupsId
                       ]
                    ], 'level');
                    if (!empty($data_level)) {
                        $level = $data_level > $level ? $data_level : $level;
                    }
                    // Don't display groups already assigned to the ticket in the dropdown
                    $group_params['condition'][] = ['cat_gr.groups_id' => ['<>', $groupsId]];
                }
                // No group assigned to the ticket
                // Selects the level min that will be displayed
                if ($level == 0) {
                    $level = self::getFirst([
                       'SELECT' => [new QueryExpression('MIN(level) AS level')],
                       'FROM'   => $table,
                       'WHERE' => [
                          'itilcategories_id' => $itilcategories_id
                       ]
                    ], 'level');
                    $group_params['condition'][] = ['cat_gr.level' => $level];
                } else {
                    $level_max = $level + 1;
                    $group_params['condition'][] = ['cat_gr.level' => [$level, $level_max]]; // If level is $level or $level_max
                }
            }

            $found_groups = self::getGroupsForCategory($itilcategories_id, $group_params, $type);
            $groups_id_toshow = []; //init
            if (!empty($found_groups)) {
                for ($lvl = 1; $lvl <= 4; $lvl++) {
                    if (isset($found_groups['groups_id_level' . $lvl])) {
                        if ($found_groups['groups_id_level' . $lvl] === "all") {
                            foreach (PluginItilcategorygroupsGroup_Level::getAllGroupForALevel($lvl, $group_params['entities_id']) as $groups_id) {
                                if ($group->getFromDB($groups_id)) {
                                    $groups_id_toshow[] = $group->getID();
                                }
                            }
                        } else {
                            foreach ($found_groups['groups_id_level' . $lvl] as $groups_id) {
                                if (countElementsInTableForEntity(
                                    "glpi_groups",
                                    $ticket->getEntityID(),
                                    ['id' => $groups_id]
                                ) > 0) {
                                    $group->getFromDB($groups_id);
                                    $groups_id_toshow[] = $group->getID();
                                }
                            }
                        }
                    }
                }
            }

            foreach ($params['actors'] as $index => &$actor) {
                //remove groups in children nodes
                if (isset($actor['children'])) {
                    foreach ($actor['children'] as $index_child => &$child) {
                        if ($child['itemtype'] == "Group" && !in_array($child['items_id'], $groups_id_toshow)) {
                            unset($actor['children'][$index_child]);
                        }
                    }

                    if (count($actor['children']) > 0) {
                        // reindex correctly children (to avoid select2 fails)
                        $actor['children'] = array_values($actor['children']);
                    } else {
                        // otherwise remove empty parent
                        unset($params['actors'][$index]);
                    }
                } else {
                    // remove direct groups (don't sure this exists)
                    if ($actor['itemtype'] == "Group" && !in_array($actor['items_id'], $groups_id_toshow)) {
                        unset($params['actors'][$index]);
                    }
                }
            }
        }

        return $params;
    }

   /**
    * get groups for category
    * @param int $itilcategories_id
    * @param array $params
    * @return array
    */
   static function getGroupsForCategory($itilcategories_id, $params = []) {
      global $DB;

      //define default options
      $options['entities_id']  = 0;
      $options['is_recursive'] = 0;
      $options['condition']    = ['cat.is_incident' => 1];

      // override default options with params
      foreach ($params as $key => $value) {
         $options[$key] = $value;
      }

      $groups   = [];
      $category = new ITILCategory();
      $table    = getTableForItemType(__CLASS__);

      if ($category->getFromDB($itilcategories_id)) {
         // increase size of group concat to avoid errors
         $DB->query("SET SESSION group_concat_max_len = 1000000");

         // retrieve all groups associated to this cat
         $criteria = [
            'SELECT' => [
               'cat.*',
               new QueryExpression("GROUP_CONCAT(\"{\\\"gr_id\\\":\",cat_gr.groups_id,\", \\\"lvl\\\": \",cat_gr.level,\"}\") as groups_level")
            ],
            'FROM' => "{$table} AS cat",
            'LEFT JOIN' => [
               'glpi_plugin_itilcategorygroups_categories_groups AS cat_gr' => [
                  'ON' => [
                     'cat' => 'id',
                     'cat_gr' => 'plugin_itilcategorygroups_categories_id'
                  ]
               ]
            ],
            'WHERE' => [
               'cat.itilcategories_id' => $itilcategories_id,
               'cat.is_active'         => 1,
            ],
            'ORDER' => ['cat.entities_id DESC'],
         ];
         if (is_array($options['condition'])) {
            $criteria['WHERE'] = array_merge($criteria['WHERE'], $options['condition']);
         } else {
            $criteria['WHERE'][] = new QueryExpression($options['condition']);
         }
         $criteria['WHERE'] += getEntitiesRestrictCriteria('cat', 'entities_id', $options['entities_id'], $options['is_recursive']);
         $it = $DB->request($criteria);

         foreach ($it as $data) {
            $groups_level = json_decode("[".$data['groups_level']."]", true);

            for ($level = 1; $level <= 4; $level++) {
               if ($data["view_all_lvl$level"]) {
                  $groups["groups_id_level$level"] = "all";
               } else {
                  foreach ($groups_level as $current_group_level) {
                     if ($current_group_level['lvl'] == $level) {
                        $groups["groups_id_level$level"][] = $current_group_level['gr_id'];
                     }
                  }
               }
            }
         }
      }

      return $groups;
   }
   /**
    * Helper to make a database request and extract the first element
    * @param array $criteria
    * @param string $selector
    * @return mixed
    */
   public static function getFirst($criteria, $selector) {
      global $DB;
      $data = $DB->request($criteria);
      if (count($data)) {
         $data = json_decode("[" . $data->current()["$selector"] . "]", true);
         return array_shift($data);
      }
      return null;
   }
   /**
    * Method used to check if the default filter must be applied
    * @param string $itilcategories_id
    * @return bool
    */
   public static function canApplyFilter($itilcategories_id) {
      global $DB;
      $category = new ITILCategory();
      if ($category->getFromDB($itilcategories_id)) {
         $it = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => getTableForItemType(__CLASS__),
            'WHERE'  => [
               'itilcategories_id' => $itilcategories_id,
               'is_active'         => 1,
               'is_groups_restriction' => 1
            ],
            'LIMIT'  => 1
         ]);
         // A category rule exist for this ticket
         if (count($it)) {
            return true;
         }
      }
      return false;
   }


   static function getOthersGroupsID($level = 0) {
      global $DB;

      $it = $DB->request([
         'SELECT' => ['gr.id'],
         'FROM' => 'glpi_groups AS gr',
         'LEFT JOIN' => [
            'glpi_plugin_itilcategorygroups_groups_levels AS gl' => [
               'ON' => [
                  'gl' => 'groups_id',
                  'gr' => 'id'
               ]
            ]
         ],
         'WHERE' => [
            'OR' => [
               'AND' => [
                  'gl.lvl' => ['<>', $level],
                  'gr.is_assign' => 1
               ],
               'gl.lvl' => null
            ]
         ]
      ]);
      $groups_id = [];
      foreach ($it as $row) {
         $groups_id[$row['id']] = $row['id'];
      }

      return $groups_id;
   }

   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'               => 'common',
         'name'             => __('Link ItilCategory - Groups', 'itilcategorygroups'),
      ];

      $tab[] = [
         'id'               => 1,
         'table'            => $this->getTable(),
         'field'            => 'name',
         'name'             => __('Name'),
         'datatype'         => 'itemlink',
         'checktype'        => 'text',
         'displaytype'      => 'text',
         'injectable'       => true,
         'massiveaction'    => false,
         'autocomplete'     => true,
      ];

      $tab[] = [
         'id'               => 2,
         'table'            => $this->getTable(),
         'field'            => 'is_incident',
         'name'             => __('Visible for an incident'),
         'datatype'         => 'bool',
         'checktype'        => 'bool',
         'displaytype'      => 'bool',
         'injectable'       => true,
      ];

      $tab[] = [
         'id'               => 3,
         'table'            => $this->getTable(),
         'field'            => 'is_request',
         'name'             => __('Visible for a request'),
         'datatype'         => 'bool',
         'checktype'        => 'bool',
         'displaytype'      => 'bool',
         'injectable'       => true,
      ];

      $tab[] = [
         'id'               => 4,
         'table'            => 'glpi_itilcategories',
         'field'            => 'name',
         'name'             => __('Category'),
         'datatype'         => 'itemlink',
         'checktype'        => 'text',
         'displaytype'      => 'text',
         'injectable'       => true,
      ];

      $tab[] = [
         'id'               => 5,
         'table'            => $this->getTable(),
         'field'            => 'is_active',
         'name'             => __('Active'),
         'datatype'         => 'bool',
         'checktype'        => 'bool',
         'displaytype'      => 'bool',
         'injectable'       => true,
      ];

      $tab[] = [
         'id'               => 16,
         'table'            => $this->getTable(),
         'field'            => 'comment',
         'name'             => __('Comments'),
         'datatype'         => 'text',
         'checktype'        => 'text',
         'displaytype'      => 'multiline_text',
         'injectable'       => true,
      ];

      $tab[] = [
         'id'               => 26,
         'table'            => 'glpi_groups',
         'field'            => 'name',
         'name'             => __('Level 1', 'itilcategorygroups'),
         'forcegroupby'     => true,
         'joinparams'       => [
            'beforejoin' => [
               'table'      => 'glpi_plugin_itilcategorygroups_categories_groups',
               'joinparams' => [
                  'condition'  => ['NEWTABLE.level' => 1],
                  'jointype'   => 'child',
                  'beforejoin' => [
                     'table'      => 'glpi_plugin_itilcategorygroups_categories',
                     'joinparams' => [
                        'jointype'  => 'child'
                     ]
                  ]
               ]
            ]
         ],
         'massiveaction'    => false,
      ];

      $tab[] = [
         'id'               => 27,
         'table'            => 'glpi_groups',
         'field'            => 'name',
         'name'             => __('Level 2', 'itilcategorygroups'),
         'forcegroupby'     => true,
         'joinparams'       => [
            'beforejoin' => [
               'table'      => 'glpi_plugin_itilcategorygroups_categories_groups',
               'joinparams' => [
                  'condition'  => ['NEWTABLE.level' => 2],
                  'jointype'   => 'child',
                  'beforejoin' => [
                     'table'      => 'glpi_plugin_itilcategorygroups_categories',
                     'joinparams' => [
                        'jointype'  => 'child'
                     ]
                  ]
               ]
            ]
         ],
         'massiveaction'    => false,
      ];

      $tab[] = [
         'id'               => 28,
         'table'            => 'glpi_groups',
         'field'            => 'name',
         'name'             => __('Level 3', 'itilcategorygroups'),
         'forcegroupby'     => true,
         'joinparams'       => [
            'beforejoin' => [
               'table'      => 'glpi_plugin_itilcategorygroups_categories_groups',
               'joinparams' => [
                  'condition'  => ['NEWTABLE.level' => 3],
                  'jointype'   => 'child',
                  'beforejoin' => [
                     'table'      => 'glpi_plugin_itilcategorygroups_categories',
                     'joinparams' => [
                        'jointype'  => 'child'
                     ]
                  ]
               ]
            ]
         ],
         'massiveaction'    => false,
      ];

      $tab[] = [
         'id'               => 29,
         'table'            => 'glpi_groups',
         'field'            => 'name',
         'name'             => __('Level 4', 'itilcategorygroups'),
         'forcegroupby'     => true,
         'joinparams'       => [
            'beforejoin' => [
               'table'      => 'glpi_plugin_itilcategorygroups_categories_groups',
               'joinparams' => [
                  'condition'  => ['NEWTABLE.level' => 4],
                  'jointype'   => 'child',
                  'beforejoin' => [
                     'table'      => 'glpi_plugin_itilcategorygroups_categories',
                     'joinparams' => [
                        'jointype'  => 'child'
                     ]
                  ]
               ]
            ]
         ],
         'massiveaction'    => false,
      ];

      $tab[] = [
         'id'               => 30,
         'table'            => $this->getTable(),
         'field'            => 'id',
         'name'             => __('ID'),
         'injectable'       => false,
         'massiveaction'    => false,
      ];

      $tab[] = [
         'id'               => 35,
         'table'            => $this->getTable(),
         'field'            => 'date_mod',
         'name'             => __('Last update'),
         'datatype'         => 'datetime',
         'massiveaction'    => false,
      ];

      $tab[] = [
         'id'               => 80,
         'table'            => 'glpi_entities',
         'field'            => 'completename',
         'name'             => __('Entity'),
         'injectable'       => false,
         'massiveaction'    => false,
      ];

      $tab[] = [
         'id'               => 86,
         'table'            => $this->getTable(),
         'field'            => 'is_recursive',
         'name'             => __('Child entities'),
         'datatype'         => 'bool',
         'checktype'        => 'bool',
         'displaytype'      => 'bool',
         'injectable'       => true,
      ];

      return $tab;
   }

   //----------------------------- Install process --------------------------//
   static function install(Migration $migration) {
      global $DB;

      $default_charset = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

      $table = getTableForItemType(__CLASS__);

      if ($DB->tableExists("glpi_plugin_itilcategorygroups_categories_groups")
          && $DB->fieldExists("glpi_plugin_itilcategorygroups_categories_groups", 'is_active')) {
         $migration->renameTable("glpi_plugin_itilcategorygroups_categories_groups", $table);
      }

      if (!$DB->tableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
         `id` INT {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `is_active` TINYINT NOT NULL DEFAULT '0',
         `name` VARCHAR(255) DEFAULT '',
         `comment` TEXT,
         `date_mod` DATE default NULL,
         `itilcategories_id` INT {$default_key_sign} NOT NULL DEFAULT '0',
         `view_all_lvl1` TINYINT NOT NULL DEFAULT '0',
         `view_all_lvl2` TINYINT NOT NULL DEFAULT '0',
         `view_all_lvl3` TINYINT NOT NULL DEFAULT '0',
         `view_all_lvl4` TINYINT NOT NULL DEFAULT '0',
         `entities_id` INT {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` TINYINT NOT NULL DEFAULT '1',
         `is_incident` TINYINT NOT NULL DEFAULT '1',
         `is_request` TINYINT NOT NULL DEFAULT '1',
         `is_groups_restriction` TINYINT NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `itilcategories_id` (`itilcategories_id`),
         KEY `is_incident` (`is_incident`),
         KEY `is_request` (`is_request`),
         KEY `is_recursive` (`is_recursive`),
         KEY date_mod (date_mod)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->query($query);
      }

      if (!$DB->fieldExists($table, 'view_all_lvl1')) {
         $migration->addField($table, 'view_all_lvl1', "TINYINT NOT NULL DEFAULT '0'",
                              ['after' => 'itilcategories_id']);
         $migration->addField($table, 'view_all_lvl2', "TINYINT NOT NULL DEFAULT '0'",
                              ['after' => 'itilcategories_id']);
         $migration->addField($table, 'view_all_lvl3', "TINYINT NOT NULL DEFAULT '0'",
                              ['after' => 'itilcategories_id']);
         $migration->addField($table, 'view_all_lvl4', "TINYINT NOT NULL DEFAULT '0'",
                              ['after' => 'itilcategories_id']);
         $migration->migrationOneTable($table);
      }

      if (!$DB->fieldExists($table, 'is_groups_restriction')) {
         $migration->addField($table, 'is_groups_restriction', "TINYINT NOT NULL DEFAULT '0'",
                              ['after' => 'itilcategories_id']);
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

   static function getIcon() {
      return "fas fa-users";
   }
}

