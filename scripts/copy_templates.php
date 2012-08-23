<?php


/*
 * @version $Id: copy_templates.php 217 2012-05-31 08:43:15Z wnouh $
 This file is part of the teclibtoolbox plugin.

 teclibtoolbox plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 teclibtoolbox plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with teclibtoolbox. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   teclibtoolbox
 @author    TECLIB' : TECLIB (www.teclib.com)
 @copyright Copyright (c) 2012 TECLIB'
 @license   GPLv2+
            http://www.gnu.org/licenses/gpl.txt
 @link      http://www.teclib.com
 @link      http://www.glpi-project.org/
 @since     2012
 ---------------------------------------------------------------------- */

ini_set("memory_limit","-1");
ini_set("max_execution_time", "0");

if ($argv) {
   for ($i=1;$i<count($argv);$i++) {
      //To be able to use = in search filters, enter \= instead in command line
      //Replace the \= by ° not to match the split function
      $arg   = str_replace('\=','°',$argv[$i]);
      $it    = explode("=",$arg);
      $it[0] = preg_replace('/^--/','',$it[0]);

      //Replace the ° by = the find the good filter
      $it           = str_replace('°','=',$it);
      $_GET[$it[0]] = $it[1];
   }
}

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

if (isset($_GET['help'])) {
   echo "This script duplicates a template in child entities\n";
   echo "php copy_template.php itemtype=[xxx] items_id=[yyy]\n";
   echo "- itemtype : the template item type (for example Computer, Printer, etc)\n";
   echo "- items_id : template ID\n";
   exit(1);
}

$itemtype = $_GET['itemtype'];
$items_id = $_GET['items_id'];

if (!class_exists($itemtype)) {
   echo "Itemtype not found\n";
   exit();
}
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
         echo "Adding template ".$item->fields['template_name']." ($new_id) in entity ".$entity."\n";
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
         
      } else {
         echo "Template exists in entity ".$entity."\n";
      }
   }
} else {
   if ($item->mayBeRecursive()) {
      echo "Templates can be recursive !\n";
      exit();
   }
   echo "Cannot load template or or it's not template!\n";
   exit();
}