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
use function Safe\preg_replace;

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

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public static function getSectorizedDetails(): array
    {
        return ['admin', 'pluginitilcategorygroupsmenu', 'model'];
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

    public function cleanDBonPurge()
    {
        parent::cleanDBonPurge();

        $this->deleteChildrenAndRelationsFromDb([
            PluginItilcategorygroupsCategory_Group::class,
        ]);
    }

    /**
     * Groups linked to this rule, per level, loaded once for the 4 levels of the form
     *
     * @var array<int, array<int, int>>|null
     */
    private ?array $saved_values_cache = null;

    /**
     * Rule id and category the saved values cache was built for
     */
    private ?string $saved_values_cache_key = null;

    /**
     * Groups available per level, loaded once for the 4 levels of the form
     *
     * @var array<int, array<int, string>>|null
     */
    private ?array $level_groups_cache = null;

    public function getSavedValues($level)
    {
        /** @var DBmysql $DB */
        global $DB;

        if ($this->isNewItem()) {
            return [];
        }

        $cache_key = $this->getID() . '-' . $this->fields['itilcategories_id'];
        if ($this->saved_values_cache === null || $this->saved_values_cache_key !== $cache_key) {
            $this->saved_values_cache     = [];
            $this->saved_values_cache_key = $cache_key;
            $res_val = $DB->request([
                'SELECT' => ['groups_id', 'level'],
                'FROM'   => 'glpi_plugin_itilcategorygroups_categories_groups',
                'WHERE'  => [
                    'OR' => [
                        'itilcategories_id' => $this->fields['itilcategories_id'],
                        'plugin_itilcategorygroups_categories_id' => $this->getID(),
                    ],
                    'level' => [1, 2, 3, 4],
                ],
            ]);
            foreach ($res_val as $data_val) {
                $this->saved_values_cache[$data_val['level']][$data_val['groups_id']] = $data_val['groups_id'];
            }
        }

        return $this->saved_values_cache[(int) $level] ?? [];
    }

    public function multipleDropdownGroup($level)
    {
        /** @var DBmysql $DB */
        global $DB;

        if ($this->level_groups_cache === null) {
            $this->level_groups_cache = [];
            $res_val = $DB->request([
                'SELECT' => ['glpi_groups.id', 'glpi_groups.name', 'glpi_plugin_itilcategorygroups_groups_levels.lvl'],
                'FROM'   => 'glpi_groups',
                'INNER JOIN' => [
                    'glpi_plugin_itilcategorygroups_groups_levels' => [
                        'ON' => [
                            'glpi_plugin_itilcategorygroups_groups_levels' => 'groups_id',
                            'glpi_groups' => 'id',
                            [
                                'AND' => [
                                    'glpi_plugin_itilcategorygroups_groups_levels.lvl' => [1, 2, 3, 4],
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
                $this->level_groups_cache[$data_val['lvl']][$data_val['id']] = $data_val['name'];
            }
        }

        return $this->level_groups_cache[intval($level)] ?? [];
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

        // Links are about to change: drop the values memoized for the form
        $this->saved_values_cache = null;

        $cat_group = new PluginItilcategorygroupsCategory_Group();

        // Levels whose groups are listed explicitly (not "all groups of the level")
        $levels = array_values(array_filter(
            [1, 2, 3, 4],
            fn(int $lvl): bool => ($this->input['view_all_lvl' . $lvl] ?? 0) != 1,
        ));
        if ($levels === []) {
            return;
        }

        // Current links of the category for these levels, in a single query
        $current_links = [];
        foreach (
            $cat_group->find([
                'itilcategories_id' => $this->input['itilcategories_id'],
                'level'             => $levels,
            ], ['id']) as $current_cat_group
        ) {
            $current_links[$current_cat_group['level']][] = $current_cat_group;
        }

        foreach ($levels as $lvl) {
            $wanted_groups = [];
            if (isset($this->input['groups_id_level' . $lvl]) && is_array($this->input['groups_id_level' . $lvl])) {
                foreach ($this->input['groups_id_level' . $lvl] as $groups_id) {
                    $wanted_groups[(int) $groups_id] = true;
                }
            }

            // Keep the links of this rule that are still selected, delete the others
            foreach ($current_links[$lvl] ?? [] as $current_cat_group) {
                $groups_id = (int) $current_cat_group['groups_id'];
                if (
                    isset($wanted_groups[$groups_id])
                    && $current_cat_group['plugin_itilcategorygroups_categories_id'] == $this->input['id']
                ) {
                    unset($wanted_groups[$groups_id]);
                    continue;
                }

                $cat_group->delete(['id' => $current_cat_group['id']]);
            }

            // Add only the newly selected groups
            foreach (array_keys($wanted_groups) as $groups_id) {
                $cat_group->add(['plugin_itilcategorygroups_categories_id' => $this->input['id'],
                    'level'                                                => $lvl,
                    'itilcategories_id'                                    => $this->input['itilcategories_id'],
                    'groups_id'                                            => $groups_id,
                ]);
            }
        }
    }

    public static function filterActors(array $params = []): array
    {
        /** @var DBmysql $DB */
        global $DB;

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
                $group_params['condition']   = ' AND ' . ($ticket->fields['type'] == Ticket::DEMAND_TYPE
                    ? "`is_request`='1'" : "`is_incident`='1'");
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
                $table         = getTableForItemType($categoryGroup::class);
                // All groups assigned to the ticket
                $assigned_groups_ids = array_column($ticket->getGroups(2), 'groups_id');
                if ($assigned_groups_ids !== []) {
                    // Level of each assigned group for this category, in a single query
                    // (first link found per group, as the former one query per group did)
                    $groups_levels = [];
                    $levels_iterator = $DB->request([
                        'SELECT' => ['groups_id', 'level'],
                        'FROM'   => $table,
                        'WHERE'  => [
                            'itilcategories_id' => $itilcategories_id,
                            'groups_id'         => $assigned_groups_ids,
                        ],
                        'ORDER'  => 'id',
                    ]);
                    foreach ($levels_iterator as $data) {
                        $groups_levels[$data['groups_id']] ??= (int) $data['level'];
                    }

                    if ($groups_levels !== []) {
                        $level = max($groups_levels);
                    }
                }

                foreach ($assigned_groups_ids as $groupsId) {
                    // Don't display groups already assigned to the ticket in the dropdown
                    $group_params['condition'] .= sprintf(" AND cat_gr.groups_id <> '%s'", $groupsId);
                }

                // No group assigned to the ticket
                // Selects the level min that will be displayed
                if ($level == 0) {
                    $criteria = [
                        'SELECT' => ['MIN' => 'level as level'],
                        'FROM'   => $table,
                        'WHERE'  => [
                            'itilcategories_id' => $itilcategories_id,
                        ],
                    ];
                    $level = self::getFirst($criteria, 'level');
                    $group_params['condition'] .= sprintf(" AND cat_gr.level = '%s'", $level);
                } else {
                    $level_max = $level + 1;
                    $group_params['condition'] .= sprintf(" AND (cat_gr.level = '%s' OR cat_gr.level = '%s')", $level, $level_max);
                }
            }

            $found_groups     = self::getGroupsForCategory($itilcategories_id, $group_params);
            $groups_id_toshow = []; //init, group ids as keys
            if (!empty($found_groups)) {
                $groups_id_tocheck = [];
                for ($lvl = 1; $lvl <= 4; $lvl++) {
                    if (isset($found_groups['groups_id_level' . $lvl])) {
                        if ($found_groups['groups_id_level' . $lvl] === 'all') {
                            // already joined with glpi_groups and restricted to the entity
                            foreach (PluginItilcategorygroupsGroup_Level::getAllGroupForALevel($lvl, $group_params['entities_id']) as $groups_id) {
                                $groups_id_toshow[$groups_id] = true;
                            }
                        } else {
                            foreach ($found_groups['groups_id_level' . $lvl] as $groups_id) {
                                $groups_id_tocheck[$groups_id] = true;
                            }
                        }
                    }
                }

                if ($groups_id_tocheck !== []) {
                    // Keep the groups visible from the ticket entity, in a single query
                    $visible_groups = $DB->request([
                        'SELECT' => 'id',
                        'FROM'   => Group::getTable(),
                        'WHERE'  => [
                            'id' => array_keys($groups_id_tocheck),
                        ] + getEntitiesRestrictCriteria(
                            Group::getTable(),
                            '',
                            $ticket->getEntityID(),
                            $group->maybeRecursive(),
                        ),
                    ]);
                    foreach ($visible_groups as $data) {
                        $groups_id_toshow[$data['id']] = true;
                    }
                }
            }

            foreach ($params['actors'] as $index => &$actor) {
                //remove groups in children nodes
                if (isset($actor['children'])) {
                    foreach ($actor['children'] as $index_child => &$child) {
                        if ($child['itemtype'] == 'Group' && !isset($groups_id_toshow[$child['items_id']])) {
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
                } elseif ($actor['itemtype'] == 'Group' && !isset($groups_id_toshow[$actor['items_id']])) {
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
                'WHERE'  => array_merge(
                    [
                        'cat.itilcategories_id' => $itilcategories_id,
                        'cat.is_active'        => 1,
                    ],
                    $entity_restrict,
                    // extra caller-provided filtering (ticket type, level restriction, already assigned groups)
                    [new QueryExpression(preg_replace('/^\s*AND\s+/i', '', $options['condition']))],
                ),
                'GROUPBY' => 'cat.id',
            ];

            foreach ($DB->request($criteria) as $data) {
                $groups_level = json_decode('[' . $data['groups_level'] . ']', true);

                for ($level = 1; $level <= 4; $level++) {
                    if ($data['view_all_lvl' . $level]) {
                        $groups['groups_id_level' . $level] = 'all';
                    } else {
                        foreach ($groups_level as $current_group_level) {
                            if ($current_group_level['lvl'] == $level) {
                                $groups['groups_id_level' . $level][] = $current_group_level['gr_id'];
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
     * @return mixed
     */
    public static function getFirst(array $criteria, string $selector)
    {
        /** @var DBmysql $DB */
        global $DB;

        $data = $DB->request($criteria);
        if (count($data) > 0) {
            $data = json_decode('[' . $data->current()[$selector] . ']', true);

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
        return [[
            'id'   => 'common',
            'name' => __s('Link ItilCategory - Groups', 'itilcategorygroups'),
        ], [
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
        ], [
            'id'          => 2,
            'table'       => $this->getTable(),
            'field'       => 'is_incident',
            'name'        => __s('Visible for an incident'),
            'datatype'    => 'bool',
            'checktype'   => 'bool',
            'displaytype' => 'bool',
            'injectable'  => true,
        ], [
            'id'          => 3,
            'table'       => $this->getTable(),
            'field'       => 'is_request',
            'name'        => __s('Visible for a request'),
            'datatype'    => 'bool',
            'checktype'   => 'bool',
            'displaytype' => 'bool',
            'injectable'  => true,
        ], [
            'id'          => 4,
            'table'       => 'glpi_itilcategories',
            'field'       => 'name',
            'name'        => __s('Category'),
            'datatype'    => 'itemlink',
            'checktype'   => 'text',
            'displaytype' => 'text',
            'injectable'  => true,
        ], [
            'id'          => 5,
            'table'       => $this->getTable(),
            'field'       => 'is_active',
            'name'        => __s('Active'),
            'datatype'    => 'bool',
            'checktype'   => 'bool',
            'displaytype' => 'bool',
            'injectable'  => true,
        ], [
            'id'          => 16,
            'table'       => $this->getTable(),
            'field'       => 'comment',
            'name'        => __s('Comments'),
            'datatype'    => 'text',
            'checktype'   => 'text',
            'displaytype' => 'multiline_text',
            'injectable'  => true,
        ], [
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
        ], [
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
        ], [
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
        ], [
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
        ], [
            'id'            => 30,
            'table'         => $this->getTable(),
            'field'         => 'id',
            'name'          => __s('ID'),
            'injectable'    => false,
            'massiveaction' => false,
        ], [
            'id'            => 35,
            'table'         => $this->getTable(),
            'field'         => 'date_mod',
            'name'          => __s('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ], [
            'id'            => 80,
            'table'         => 'glpi_entities',
            'field'         => 'completename',
            'name'          => __s('Entity'),
            'injectable'    => false,
            'massiveaction' => false,
        ], [
            'id'          => 86,
            'table'       => $this->getTable(),
            'field'       => 'is_recursive',
            'name'        => __s('Child entities'),
            'datatype'    => 'bool',
            'checktype'   => 'bool',
            'displaytype' => 'bool',
            'injectable'  => true,
        ]];
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
            $query = "CREATE TABLE IF NOT EXISTS `{$table}` (
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
        $DB->doQuery(sprintf('DROP TABLE IF EXISTS `%s`', $table));

        return true;
    }

    public static function getIcon()
    {
        return 'ti ti-users-group';
    }
}
