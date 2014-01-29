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
define('GLPI_ROOT', '../../..');
$AJAX_INCLUDE = 1;

include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();
echo "<option value='0'>".Dropdown::EMPTY_VALUE."</option>";

if (!isset($_REQUEST['cat_id'])) {
   return ;
}

$lnum = array('one'   => 1,
              'two'   => 2,
              'three' => 3,
              'four'  => 4);

$category = $_REQUEST['cat_id'];
      

if (isset($_REQUEST['tickets_id']) && !empty($_REQUEST['tickets_id'])) {
   $ticket = new Ticket();
   if ($ticket->getFromDB($_REQUEST['tickets_id'])) {
      $params   = array('entities_id' => $ticket->fields['entities_id'], 'is_recursive' => 1);
      if ($ticket->fields['type'] == Ticket::DEMAND_TYPE) {
         $params['condition'] = " AND `is_request`='1'";
      } else {
         $params['condition'] = " AND `is_incident`='1'";
      }
      $groups   = PluginItilcategorygroupsCategory_Group::getGroupsForCategory($category, $params);
      $group    = new Group();
      
      if (!empty($groups)) {
         foreach (array('one', 'two', 'three', 'four') as $value) {
            if ($groups['groups_id_level'.$value] == 0) {
               foreach (PluginItilcategorygroupsGroup_Level::getAllGroupForALevel($lnum[$value]) as $groups_id) {
                  if ($group->getFromDB($groups_id)) {
                     echo "<option value='".$group->getID()."'>".$lnum[$value]."-".$group->getName()."</option>";
                  }
               }
            } else if ($group->getFromDB($groups['groups_id_level'.$value])) {
               echo "<option value='".$group->getID()."'>".$lnum[$value]."-".$group->getName()."</option>";
            }
         }
      }
   }
} else {
   $params   = array('entities_id' => $_SESSION['glpiactive_entity'], 'is_recursive' => 1);

   $groups   = PluginItilcategorygroupsCategory_Group::getGroupsForCategory($category, $params);
   $group    = new Group();
   if (!empty($groups)) {
      foreach (array('one', 'two', 'three', 'four') as $value) {
         if ($groups['groups_id_level'.$value] == 0) {
            foreach (PluginItilcategorygroupsGroup_Level::getAllGroupForALevel($lnum[$value]) as $groups_id) {
               if ($group->getFromDB($groups_id)) {
                  echo "<option value='".$group->getID()."'>".$lnum[$value]."-".$group->getName()."</option>";
               }
            }
         } else if ($group->getFromDB($groups['groups_id_level'.$value])) {
            echo "<option value='".$group->getID()."'>".$lnum[$value]."-".$group->getName()."</option>";
         }
      }
   }
}
?>