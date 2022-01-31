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

$AJAX_INCLUDE = 1;

include ("../../../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();

$tickets_id = (int) $_REQUEST['ticket_id'] ?? 0;
$ticket = new Ticket;
$ticket->getFromDB($tickets_id);

if (!isset($_REQUEST['itilcategories_id'])) {
   $_REQUEST['itilcategories_id'] = $ticket->fields['itilcategories_id'];
}
if (!isset($_REQUEST['type'])) {
   $_REQUEST['type'] = $ticket->fields['type'];
}

$condition = PluginItilcategorygroupsCategory::getSQLCondition(
   $tickets_id,
   intval($_REQUEST['itilcategories_id']),
   $_REQUEST['type']
);

if (empty($condition)) {
   $condition = [
      'glpi_groups.is_assign' => 1,
   ] + getEntitiesRestrictCriteria("", "entities_id", $_SESSION['glpiactive_entity'], 1);
}

if (! empty($condition)) {
   $_POST['display_emptychoice'] = true;
   $_POST['itemtype'] = 'Group';
   $_POST['condition'] = Dropdown::addNewCondition($condition);

   require "../../../ajax/getDropdownValue.php";
}
