<?php
/*
 * @version $Id: fr_FR.php 17 2012-06-19 15:09:22Z walid $
 LICENSE

  This file is part of the teclibtoolbox plugin.

 Order plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Order plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; along with teclibtoolbox. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   teclibtoolbox
 @author    the teclibtoolbox plugin team
 @copyright Copyright (c) 2010-2011 teclibtoolbox plugin team
 @license   GPLv2+
            http://www.gnu.org/licenses/gpl.txt
 @link      https://forge.indepnet.net/projects/teclibtoolbox
 @link      http://www.glpi-project.org/
 @since     2009
 ---------------------------------------------------------------------- */

class PluginTeclibtoolboxTemplate extends CommonDBTM {

   static function showForItem($item) {
      global $LANG,$DB;

      if (!haveRight("config","w")) {
         return false;
      }

      $query = "SELECT `entities_id`
                FROM `".$item->getTable()."`
                WHERE `is_template`='1'
                   AND `entities_id` IN (".implode(',',getSonsOf('glpi_entities',
                                                                 $item->fields["entities_id"])).")
                      AND `template_name`='".$item->fields['template_name']."'";
      $results = $DB->query($query);
      if ($DB->numrows($results) == 0) {
         echo "<span class='center'>".$LANG['plugin_teclibtoolbox']['template'][3]."</span>";
      } else {
         echo "<div align='center'>";
         echo "<table class='tab_cadre_fixe' cellpadding='5'>";
         echo "<tr><th>".$LANG['plugin_teclibtoolbox']['template'][4]."</th></tr>";
         while ($data = $DB->fetch_array($results)) {
            echo "<tr><td>";
            echo Dropdown::getDropdownName('glpi_entities', $data['entities_id']);
            echo "</td></tr>";
         }
         echo "</table></div>";
      }
      echo "<div align='center'>";
      echo "<form action='".GLPI_ROOT."/plugins/teclibtoolbox/front/template.php' method='post'>";
      echo "<table class='tab_cadre_fixe' cellpadding='5'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' align='center'>";
      echo "<input type='hidden' name='id' value=".$item->getID().">";
      echo "<input type='hidden' name='itemtype' value=".$item->getType().">";
      echo "<input type='submit' name='copy_template' value=\"".$LANG['plugin_teclibtoolbox']['template'][2].
            "\" class='submit'>&nbsp;";
      echo "</td></tr>\n";

      echo "</table>";
      echo "</form>";
      echo "</div>";
   }
   
   static function copyTemplate($itemtype, $items_id) {
      global $CFG_GLPI, $DB, $LANG;
      
      $cpt = 0;
      $item = new $itemtype();
      if ($item->getFromDB($items_id) && $item->fields['is_template']) {
      $tmp = $item->fields;
      $entities = getSonsOf('glpi_entities', $item->fields['entities_id']);
      unset($entities[$item->fields['entities_id']]);
      foreach ($entities as $entity) {
         if (!countElementsInTable($item->getTable(),
                                   "`template_name`='".$item->fields['template_name']."'
                                      AND `entities_id`='".$entity."' AND `is_template`='1'")) {
            unset($tmp['id']);
            $tmp['entities_id'] = $entity;
            $new_id = $item->add($tmp);
            $cpt++;
            if ($new_id) {
               if (in_array($itemtype, $CFG_GLPI["infocom_types"])) {
                  $ic = new Infocom();
                  $ic->cloneItem($itemtype, $items_id, $new_id);
               }
               
               if (in_array($itemtype, $CFG_GLPI["contract_types"])) {
                  // ADD Contract
                  $query = "SELECT `contracts_id`
                            FROM `glpi_contracts_items`
                            WHERE `items_id` = '".$items_id."'
                                  AND `itemtype` = '".$itemtype."';";
                  $result=$DB->query($query);
                  if ($DB->numrows($result)>0) {
                     $contractitem = new Contract_Item();
                     while ($data=$DB->fetch_array($result)) {
                        $contractitem->add(array('contracts_id' => $data["contracts_id"],
                                                 'itemtype'     => $itemtype,
                                                 'items_id'     => $new_id));
                     }
                  }
               }
   
               if (in_array($itemtype, $CFG_GLPI["document_types"])) {
                  //ADD Documents
                  $query = "SELECT `documents_id`
                            FROM `glpi_documents_items`
                            WHERE `items_id` = '".$items_id."'
                                  AND `itemtype` = '".$itemtype."';";
                  $result=$DB->query($query);
                  if ($DB->numrows($result)>0) {
                     $docitem = new Document_Item();
                     while ($data=$DB->fetch_array($result)) {
                        $docitem->add(array('documents_id' => $data["documents_id"],
                                            'itemtype'     => $itemtype,
                                            'items_id'     => $new_id));
                     }
                  }
               }
               
               if (in_array($itemtype, $CFG_GLPI["networkport_types"])) {
                  //ADD Ports
                  $query = "SELECT `id`
                            FROM `glpi_networkports`
                            WHERE `items_id` = '".$items_id."'
                                  AND `itemtype` = '".$itemtype."';";
                  $result=$DB->query($query);
                  if ($DB->numrows($result)>0) {
                     while ($data=$DB->fetch_array($result)) {
                        $np  = new NetworkPort();
                        $npv = new NetworkPort_Vlan();
                        $np->getFromDB($data["id"]);
                        unset($np->fields["id"]);
                        unset($np->fields["ip"]);
                        unset($np->fields["mac"]);
                        unset($np->fields["netpoints_id"]);
                        $np->fields["items_id"] = $new_id;
                        $portid = $np->addToDB();
                        foreach ($DB->request('glpi_networkports_vlans',
                                              array('networkports_id' => $data["id"])) as $vlan) {
                           $npv->assignVlan($portid, $vlan['vlans_id']);
                        }
                     }
                  }
               }
               
               if ($itemtype == 'Computer') {
                  // ADD software
                  $inst = new Computer_SoftwareVersion();
                  $inst->cloneComputer($items_id, $new_id);
         
                  $inst = new Computer_SoftwareLicense();
                  $inst->cloneComputer($items_id, $new_id);
   
                  // ADD Devices
                  $compdev = new Computer_Device();
                  $compdev->cloneComputer($items_id, $new_id);
   
                  // Add connected devices
                  $query = "SELECT *
                            FROM `glpi_computers_items`
                            WHERE `computers_id` = '".$items_id."';";
                  $result = $DB->query($query);
         
                  if ($DB->numrows($result)>0) {
                     $conn = new Computer_Item();
                     while ($data = $DB->fetch_array($result)) {
                        $conn->add(array('computers_id' => $new_id,
                                         'itemtype'      => $data["itemtype"],
                                         'items_id'      => $data["items_id"]));
                        }
                     }
                  }
               }
            }
         }
         if ($cpt++) {
            addMessageAfterRedirect($LANG['plugin_teclibtoolbox']['template'][5]." : ".$cpt, true, INFO);
         } else {
            addMessageAfterRedirect($LANG['plugin_teclibtoolbox']['template'][6], true, INFO);
         }
      }
   }
}
?>