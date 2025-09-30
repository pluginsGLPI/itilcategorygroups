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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;

use function Safe\json_decode;

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

class PluginItilcategorygroupsCategory extends CommonDropdown
{
    public $first_level_menu      = 'plugins';
    public $second_level_menu     = 'itilcategorygroups';
    public $display_dropdowntitle = false;
    public $can_be_translated = false;

    public static $rightname = 'config';

    public $dohistory = true;

    public static function getTypeName($nb = 0)
    {
        return __s('Link ItilCategory - Groups', 'itilcategorygroups');
    }

    public static function canCreate(): bool
    {
        return static::canUpdate();
    }

    public static function canPurge(): bool
    {
        return static::canUpdate();
    }

    public function showForm($id, $options = [])
    {
        if (!$this->can($id, READ)) {
            return false;
        }

        TemplateRenderer::getInstance()->display(
            '@itilcategorygroups/category.html.twig',
            [
                'action'        => $this->getFormURL(),
                'item'          => $this,
                'show_level'    => (int) $this->fields['itilcategories_id'],
            ],
        );

        return true;
    }

    public function getSavedValues($level)
    {
        /** @var DBmysql $DB */
        global $DB;

        $values = [];
        if (!$this->isNewItem()) {
            $res_val = $DB->request([
                'SELECT' => 'groups_id',
                'FROM'   => 'glpi_plugin_itilcategorygroups_categories_groups',
                'WHERE'  => [
                    'OR' => [
                        'itilcategories_id' => $this->fields['itilcategories_id'],
                        'plugin_itilcategorygroups_categories_id' => $this->getID(),
                    ],
                    'level' => $level,
                ],
            ]);
            foreach ($res_val as $data_val) {
                $values[$data_val['groups_id']] = $data_val['groups_id'];
            }
        }

        return $values;
    }

    public function multipleDropdownGroup($level)
    {
        /** @var DBmysql $DB */
        global $DB;

        $values = [];
        $res_val = $DB->request([
            'SELECT' => ['glpi_groups.id', 'glpi_groups.name'],
            'FROM'   => 'glpi_groups',
            'INNER JOIN' => [
                'glpi_plugin_itilcategorygroups_groups_levels' => [
                    'ON' => [
                        'glpi_plugin_itilcategorygroups_groups_levels' => 'groups_id',
                        'glpi_groups' => 'id',
                        [
                            'AND' => [
                                'glpi_plugin_itilcategorygroups_groups_levels.lvl' => intval($level),
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE' => getEntitiesRestrictCriteria(
                'glpi_groups',
                '',
                $_SESSION['glpiactiveentities'],
                true,
            ),
        ]);

        foreach ($res_val as $data_val) {
            $values[$data_val['id']] = $data_val['name'];
        }

        return $values;
    }

    public function prepareInputForAdd($input)
    {
        $cat       = new self();
        $found_cat = $cat->find(['itilcategories_id' => $this->input['itilcategories_id']]);
        if (count($found_cat) > 0) {
            Session::addMessageAfterRedirect(__s('A link with this category already exists', 'itilcategorygroups'));

            return false;
        }

        return $this->prepareInputForUpdate($input);
    }

    public function prepareInputForUpdate($input)
    {
        foreach ($input as &$value) {
            if ($value === 'on') {
                $value = 1;
            }
        }

        return $input;
    }

    public function post_addItem()
    {
        $this->input['id'] = $this->fields['id'];
        $this->post_updateItem();
    }

    public function post_updateItem($history = true)
    {
        // quick fix :
        if (isset($_REQUEST['massiveaction'])) {
            return;
        }

        $cat_group = new PluginItilcategorygroupsCategory_Group();

        for ($lvl = 1; $lvl <= 4; $lvl++) {
            if ($this->input["view_all_lvl$lvl"] != 1) {
                //delete old groups values
                $found_cat_groups = $cat_group->find(
                    [
                        'itilcategories_id' => $this->input['itilcategories_id'],
                        'level'             => $lvl,
                    ],
                );

                foreach ($found_cat_groups as $id => $current_cat_group) {
                    $cat_group->delete(['id' => $current_cat_group['id']]);
                }

                //insert new saved
                if (isset($this->input["groups_id_level$lvl"]) && is_array($this->input["groups_id_level$lvl"])) {
                    foreach ($this->input["groups_id_level$lvl"] as $groups_id) {
                        $cat_group->add(['plugin_itilcategorygroups_categories_id' => $this->input['id'],
                            'level'                                                => $lvl,
                            'itilcategories_id'                                    => $this->input['itilcategories_id'],
                            'groups_id'                                            => $groups_id,
                        ]);
                    }
                }
            }
        }
    }

    public static function filterActors(array $params = []): array
    {
        $itemtype = $params['params']['itemtype'];
        $items_id = $params['params']['items_id'];

        if ($itemtype == 'Ticket' && $params['params']['actortype'] == 'assign') {
            $ticket = new Ticket();
            $group  = new Group();

            $group_params = [
                'entities_id'  => $_SESSION['glpiactive_entity'],
                'is_recursive' => 1,
            ];

            $type = $params['params']['item']['type'] ?? Ticket::INCIDENT_TYPE;

            if (!empty($items_id) && $ticket->getFromDB($items_id)) {
                // == UPDATE EXISTING TICKET ==
                $group_params['entities_id'] = $ticket->fields['entities_id'];
                $group_params['condition']   = ' AND ' . ($ticket->fields['type'] == Ticket::DEMAND_TYPE ?
                    "`is_request`='1'" : "`is_incident`='1'");
            } elseif ($type == Ticket::DEMAND_TYPE) {
                $group_params['condition'] = " AND `is_request` ='1'";
            } else {
                $group_params['condition'] = " AND `is_incident` = '1'";
            }

            $itilcategories_id = $params['params']['item']['itilcategories_id'] ?? $ticket->fields['itilcategories_id'];

            $current_setup = new self();
            $current_setup->getFromDBByCrit([
                'itilcategories_id' => $itilcategories_id,
            ]);

            if ($current_setup->fields['is_groups_restriction'] ?? false) {
                // == CHECKS FOR LEVEL VISIBILITY ==
                $level         = 0;
                $categoryGroup = new PluginItilcategorygroupsCategory_Group();
                $table         = getTableForItemType(get_class($categoryGroup));
                // All groups assigned to the ticket
                foreach ($ticket->getGroups(2) as $element) {
                    $groupsId   = $element['groups_id'];
                    $criteria = [
                        'SELECT' => 'level',
                        'FROM'   => $table,
                        'WHERE'  => [
                            'itilcategories_id' => $itilcategories_id,
                            'groups_id'         => $groupsId,
                        ],
                    ];
                    $data_level = self::getFirst($criteria, 'level');
                    if (!empty($data_level)) {
                        $level = $data_level > $level ? $data_level : $level;
                    }
                    // Don't display groups already assigned to the ticket in the dropdown
                    $group_params['condition'] .= " AND cat_gr.groups_id <> '$groupsId'";
                }
                // No group assigned to the ticket
                // Selects the level min that will be displayed
                if ($level == 0) {
                    $criteria = [
                        'SELECT' => 'MIN(level) as level',
                        'FROM'   => $table,
                        'WHERE'  => [
                            'itilcategories_id' => $itilcategories_id,
                        ],
                    ];
                    $level = self::getFirst($criteria, 'level');
                    $group_params['condition'] .= " AND cat_gr.level = '$level'";
                } else {
                    $level_max = $level + 1;
                    $group_params['condition'] .= " AND (cat_gr.level = '$level' OR cat_gr.level = '$level_max')";
                }
            }

            $found_groups     = self::getGroupsForCategory($itilcategories_id, $group_params);
            $groups_id_toshow = []; //init
            if (!empty($found_groups)) {
                for ($lvl = 1; $lvl <= 4; $lvl++) {
                    if (isset($found_groups['groups_id_level' . $lvl])) {
                        if ($found_groups['groups_id_level' . $lvl] === 'all') {
                            foreach (PluginItilcategorygroupsGroup_Level::getAllGroupForALevel($lvl, $group_params['entities_id']) as $groups_id) {
                                if ($group->getFromDB($groups_id)) {
                                    $groups_id_toshow[] = $group->getID();
                                }
                            }
                        } else {
                            foreach ($found_groups['groups_id_level' . $lvl] as $groups_id) {
                                if (
                                    countElementsInTableForEntity(
                                        'glpi_groups',
                                        $ticket->getEntityID(),
                                        ['id' => $groups_id],
                                    ) > 0
                                ) {
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
                        if ($child['itemtype'] == 'Group' && !in_array($child['items_id'], $groups_id_toshow)) {
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
                } elseif ($actor['itemtype'] == 'Group' && !in_array($actor['items_id'], $groups_id_toshow)) {
                    // remove direct groups (don't sure this exists)
                    unset($params['actors'][$index]);
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
    public static function getGroupsForCategory($itilcategories_id, $params = [])
    {
        /** @var DBmysql $DB */
        global $DB;

        //define default options
        $options['entities_id']  = 0;
        $options['is_recursive'] = 0;
        $options['condition']    = " AND cat.is_incident = '1'";

        // override default options with params
        foreach ($params as $key => $value) {
            $options[$key] = $value;
        }

        $groups   = [];
        $category = new ITILCategory();
        $table    = getTableForItemType(self::class);

        if ($category->getFromDB($itilcategories_id)) {
            $entity_restrict[] = getEntitiesRestrictRequest(
                '',
                'cat',
                'entities_id',
                $options['entities_id'],
                $options['is_recursive'],
            );

            // increase size of group concat to avoid errors
            $DB->doQuery('SET SESSION group_concat_max_len = 1000000');

            // retrieve all groups associated to this cat
            $criteria = [
                'SELECT' => [
                    'cat.*',
                    QueryFunction::groupConcat(
                        expression: new QueryExpression(
                            "\"{\\\"gr_id\\\":\", cat_gr.groups_id, \", \\\"lvl\\\": \", cat_gr.level, \"}\"",
                        ),
                        distinct: false,
                        alias: 'groups_level',
                    ),
                ],
                'FROM'   => $table . ' AS cat',
                'LEFT JOIN' => [
                    'glpi_plugin_itilcategorygroups_categories_groups AS cat_gr' => [
                        'FKEY' => [
                            'cat' => 'id',
                            'cat_gr' => 'plugin_itilcategorygroups_categories_id',
                        ],
                    ],
                ],
                'WHERE'  => [
                    'cat.itilcategories_id' => $itilcategories_id,
                    'cat.is_active'        => 1,
                ] + $entity_restrict,
                'GROUPBY' => 'cat.id',
            ];

            foreach ($DB->request($criteria) as $data) {
                $groups_level = json_decode('[' . $data['groups_level'] . ']', true);

                for ($level = 1; $level <= 4; $level++) {
                    if ($data["view_all_lvl$level"]) {
                        $groups["groups_id_level$level"] = 'all';
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
    public static function getFirst(array $criteria, string $selector)
    {
        /** @var DBmysql $DB */
        global $DB;

        $data = $DB->request($criteria);
        if (count($data) > 0) {
            $data = json_decode('[' . $data->current()["$selector"] . ']', true);

            return array_shift($data);
        }

        return null;
    }

    /**
     * Method used to check if the default filter must be applied
     * @param string|int $itilcategories_id
     * @return bool
     */
    public static function canApplyFilter($itilcategories_id)
    {
        /** @var DBmysql $DB */
        global $DB;

        $category = new ITILCategory();
        if ($category->getFromDB($itilcategories_id)) {
            $table = getTableForItemType(self::class);
            $data  = $DB->request([
                'SELECT' => 'is_active',
                'FROM'   => $table,
                'WHERE'  => [
                    'itilcategories_id' => $itilcategories_id,
                    'is_active'        => 1,
                    'is_groups_restriction' => 1,
                ],
            ]);
            // A category rule exist for this ticket
            if (count($data) > 0) {
                return true;
            }
        }

        return false;
    }

    public static function getOthersGroupsID($level = 0)
    {
        /** @var DBmysql $DB */
        global $DB;

        $res = $DB->request([
            'SELECT' => 'glpi_groups.id',
            'FROM'   => 'glpi_groups',
            'LEFT JOIN' => [
                'glpi_plugin_itilcategorygroups_groups_levels' => [
                    'ON' => [
                        'glpi_plugin_itilcategorygroups_groups_levels' => 'groups_id',
                        'glpi_groups' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                'OR' => [
                    [
                        'NOT' => ['glpi_plugin_itilcategorygroups_groups_levels.lvl' => $level],
                        'glpi_groups.is_assign' => 1,
                    ],
                    'glpi_plugin_itilcategorygroups_groups_levels.lvl' => null,
                ],
            ],
        ]);

        $groups_id = [];
        foreach ($res as $row) {
            $groups_id[$row['id']] = $row['id'];
        }

        return $groups_id;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => __s('Link ItilCategory - Groups', 'itilcategorygroups'),
        ];

        $tab[] = [
            'id'            => 1,
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __s('Name'),
            'datatype'      => 'itemlink',
            'checktype'     => 'text',
            'displaytype'   => 'text',
            'injectable'    => true,
            'massiveaction' => false,
            'autocomplete'  => true,
        ];

        $tab[] = [
            'id'          => 2,
            'table'       => $this->getTable(),
            'field'       => 'is_incident',
            'name'        => __s('Visible for an incident'),
            'datatype'    => 'bool',
            'checktype'   => 'bool',
            'displaytype' => 'bool',
            'injectable'  => true,
        ];

        $tab[] = [
            'id'          => 3,
            'table'       => $this->getTable(),
            'field'       => 'is_request',
            'name'        => __s('Visible for a request'),
            'datatype'    => 'bool',
            'checktype'   => 'bool',
            'displaytype' => 'bool',
            'injectable'  => true,
        ];

        $tab[] = [
            'id'          => 4,
            'table'       => 'glpi_itilcategories',
            'field'       => 'name',
            'name'        => __s('Category'),
            'datatype'    => 'itemlink',
            'checktype'   => 'text',
            'displaytype' => 'text',
            'injectable'  => true,
        ];

        $tab[] = [
            'id'          => 5,
            'table'       => $this->getTable(),
            'field'       => 'is_active',
            'name'        => __s('Active'),
            'datatype'    => 'bool',
            'checktype'   => 'bool',
            'displaytype' => 'bool',
            'injectable'  => true,
        ];

        $tab[] = [
            'id'          => 16,
            'table'       => $this->getTable(),
            'field'       => 'comment',
            'name'        => __s('Comments'),
            'datatype'    => 'text',
            'checktype'   => 'text',
            'displaytype' => 'multiline_text',
            'injectable'  => true,
        ];

        $tab[] = [
            'id'           => 26,
            'table'        => 'glpi_groups',
            'field'        => 'name',
            'name'         => __s('Level 1', 'itilcategorygroups'),
            'forcegroupby' => true,
            'joinparams'   => [
                'beforejoin' => [
                    'table'      => 'glpi_plugin_itilcategorygroups_categories_groups',
                    'joinparams' => [
                        'condition'  => 'AND NEWTABLE.level = 1',
                        'jointype'   => 'child',
                        'beforejoin' => [
                            'table'      => 'glpi_plugin_itilcategorygroups_categories',
                            'joinparams' => [
                                'jointype' => 'child',
                            ],
                        ],
                    ],
                ],
            ],
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'           => 27,
            'table'        => 'glpi_groups',
            'field'        => 'name',
            'name'         => __s('Level 2', 'itilcategorygroups'),
            'forcegroupby' => true,
            'joinparams'   => [
                'beforejoin' => [
                    'table'      => 'glpi_plugin_itilcategorygroups_categories_groups',
                    'joinparams' => [
                        'condition'  => 'AND NEWTABLE.level = 2',
                        'jointype'   => 'child',
                        'beforejoin' => [
                            'table'      => 'glpi_plugin_itilcategorygroups_categories',
                            'joinparams' => [
                                'jointype' => 'child',
                            ],
                        ],
                    ],
                ],
            ],
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'           => 28,
            'table'        => 'glpi_groups',
            'field'        => 'name',
            'name'         => __s('Level 3', 'itilcategorygroups'),
            'forcegroupby' => true,
            'joinparams'   => [
                'beforejoin' => [
                    'table'      => 'glpi_plugin_itilcategorygroups_categories_groups',
                    'joinparams' => [
                        'condition'  => 'AND NEWTABLE.level = 3',
                        'jointype'   => 'child',
                        'beforejoin' => [
                            'table'      => 'glpi_plugin_itilcategorygroups_categories',
                            'joinparams' => [
                                'jointype' => 'child',
                            ],
                        ],
                    ],
                ],
            ],
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'           => 29,
            'table'        => 'glpi_groups',
            'field'        => 'name',
            'name'         => __s('Level 4', 'itilcategorygroups'),
            'forcegroupby' => true,
            'joinparams'   => [
                'beforejoin' => [
                    'table'      => 'glpi_plugin_itilcategorygroups_categories_groups',
                    'joinparams' => [
                        'condition'  => 'AND NEWTABLE.level = 4',
                        'jointype'   => 'child',
                        'beforejoin' => [
                            'table'      => 'glpi_plugin_itilcategorygroups_categories',
                            'joinparams' => [
                                'jointype' => 'child',
                            ],
                        ],
                    ],
                ],
            ],
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => 30,
            'table'         => $this->getTable(),
            'field'         => 'id',
            'name'          => __s('ID'),
            'injectable'    => false,
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => 35,
            'table'         => $this->getTable(),
            'field'         => 'date_mod',
            'name'          => __s('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => 80,
            'table'         => 'glpi_entities',
            'field'         => 'completename',
            'name'          => __s('Entity'),
            'injectable'    => false,
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'          => 86,
            'table'       => $this->getTable(),
            'field'       => 'is_recursive',
            'name'        => __s('Child entities'),
            'datatype'    => 'bool',
            'checktype'   => 'bool',
            'displaytype' => 'bool',
            'injectable'  => true,
        ];

        return $tab;
    }

    //----------------------------- Install process --------------------------//
    public static function install(Migration $migration)
    {
        /** @var DBmysql $DB */
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

        $table = getTableForItemType(self::class);

        if (
            $DB->tableExists('glpi_plugin_itilcategorygroups_categories_groups')
            && $DB->fieldExists('glpi_plugin_itilcategorygroups_categories_groups', 'is_active')
        ) {
            $migration->renameTable('glpi_plugin_itilcategorygroups_categories_groups', $table);
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
            $DB->doQuery($query);
        }

        if (!$DB->fieldExists($table, 'view_all_lvl1')) {
            $migration->addField(
                $table,
                'view_all_lvl1',
                "TINYINT NOT NULL DEFAULT '0'",
                ['after' => 'itilcategories_id'],
            );
            $migration->addField(
                $table,
                'view_all_lvl2',
                "TINYINT NOT NULL DEFAULT '0'",
                ['after' => 'itilcategories_id'],
            );
            $migration->addField(
                $table,
                'view_all_lvl3',
                "TINYINT NOT NULL DEFAULT '0'",
                ['after' => 'itilcategories_id'],
            );
            $migration->addField(
                $table,
                'view_all_lvl4',
                "TINYINT NOT NULL DEFAULT '0'",
                ['after' => 'itilcategories_id'],
            );
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, 'is_groups_restriction')) {
            $migration->addField(
                $table,
                'is_groups_restriction',
                "TINYINT NOT NULL DEFAULT '0'",
                ['after' => 'itilcategories_id'],
            );
            $migration->migrationOneTable($table);
        }

        return true;
    }

    public static function uninstall()
    {
        /** @var DBmysql $DB */
        global $DB;
        $table = getTableForItemType(self::class);
        $DB->doQuery("DROP TABLE IF EXISTS`$table`");

        return true;
    }

    public static function getIcon()
    {
        return 'ti ti-users-group';
    }
}
