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
define('GLPI_ROOT', '../../..');
$AJAX_INCLUDE = 1;

include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();
if (!isset($_POST['ticket_id']) || !isset($_POST['cat_id'])) {
   return ;
}
$ticket   = new Ticket();
$ticket->getFromDB($_POST['ticket_id']);
$category = $_POST['cat_id'];
$params   = array('entities_id' => $ticket->fields['entities_id'], 'is_recursive' => 1);
if ($ticket->fields['type'] == Ticket::DEMAND_TYPE) {
   $params['condition'] = " AND `is_request`='1'";
} else {
   $params['condition'] = " AND `is_incident`='1'";
}
$groups   = PluginMeteofrancehelpdeskCategory_Group::getGroupsForCategory($category, $params);
$group    = new Group();

echo "<option value='0'>".Dropdown::EMPTY_VALUE."</option>";
if (!empty($groups)) {
   foreach (array('one', 'two', 'three', 'four') as $value) {
      if ($group->getFromDB($groups['groups_id_level'.$value])) {
         echo "<option value='".$group->getID()."'>".$group->getName()."</option>";
      }
   }
}
?>