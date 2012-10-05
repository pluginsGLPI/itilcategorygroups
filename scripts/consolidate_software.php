<?php
ini_set("memory_limit", "-1");
define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

$SOFTWARE_ROOT_ENTITY = 0;

function doSearchDat($array_same_soft) {
   global $SOFTWARE_ROOT_ENTITY;

   for ($i = 0; isset($array_same_soft[$i]); $i++) {
      if ($array_same_soft[$i]['entities_id'] == $SOFTWARE_ROOT_ENTITY) {
         return $array_same_soft[$i]['id'];
      }
   }
   return NOTFOUND;
}

function doTransferAndGroupement($array_same_soft) {
   global $SOFTWARE_ROOT_ENTITY, $DB, $CFG_GLPI;

   if (count($array_same_soft) > 1) {
      //Si plus d'un soft : on merge le tout
      $software = new Software();
      $id_software = doSearchDat($array_same_soft);
      
      //Le soft n'existe pas dans l'entité DAT
      if ($id_software == NOTFOUND) {
         $id_software = $software->addSoftware(Toolbox::addslashes_deep($array_same_soft[0]['name']),
                                               Toolbox::addslashes_deep($array_same_soft[0]['manufacturer']),
                                               $SOFTWARE_ROOT_ENTITY);
         if (isCommandLine()) {
            echo "Software added ".$array_same_soft[0]['name']." in entity DAT (ID=$SOFTWARE_ROOT_ENTITY)\n";
         }
      } else {
         $software->getFromDB($id_software);
         if ($software->fields['is_deleted']) {
            $tmp['is_deleted'] = 0;
            $tmp['id']         = $id_software;
            $software->update($tmp);
         }
      }
      
      # On positionne a oui la visualisation
      $query = "UPDATE `glpi_softwares` SET `is_recursive` = '1' WHERE `id` = '" . $id_software."'";
      $result = $DB->query($query);

      # On recupere la liste des logiciels qu'on peut regrouper
      $query = "SELECT `gs`.`id`, `gs`.`name`, `gs`.`entities_id`, `glpi_entities`.`completename` AS entity " .
               "FROM `glpi_softwares` AS gs " .
               "LEFT JOIN `glpi_entities` ON (`gs`.`entities_id`=`glpi_entities`.`id`) " .
               "WHERE `gs`.`id` !='$id_software' AND `gs`.name='" .
                  Toolbox::addslashes_deep($array_same_soft[0]['name']) . "'".
                  "AND `gs`.`is_template` = '0' " .
                     getEntitiesRestrictRequest('AND', 'gs', 'entities_id',
                                                getSonsOf('glpi_entities', $SOFTWARE_ROOT_ENTITY),
                                                false).
               "ORDER BY `glpi_entities`.`completename`";

      //On merge tous les softs
      $array_merge_soft = array();
      foreach($DB->request($query) as $data) {
         $array_merge_soft[$data['id']] = 1;
         if (isCommandLine()) {
            echo "Merge software ".$data['name']." with software ID=$SOFTWARE_ROOT_ENTITY\n";
         }
      }
      
      $software->getFromDB($id_software);
      $software->merge($array_merge_soft);
      
   } elseif (count($array_same_soft) == 1) {
      //Le soft n'existe qu'une seule fois dans une sous entité
      $soft = array_pop($array_same_soft);
      if ($soft['entities_id'] != $SOFTWARE_ROOT_ENTITY) {
         //On transfère le soft
         $transfer = new Transfer();
         $transfer->getFromDB($CFG_GLPI['transfers_id_auto']);
         $item_to_transfer    = array("Software" => array($soft['id'] => $soft['id']));
         $transfer->moveItems($item_to_transfer, $SOFTWARE_ROOT_ENTITY, $transfer->fields);
         
         //On met bien le soft comme visible dans les sous-entités
         $tmp['id']           = $soft['id'];
         $tmp['is_recursive'] = 1;
         $software            = new Software();
         $software->update($tmp);
      }
   }
}

$SOFTWARE_ROOT_ENTITY = false;

echo "Start merging softwares\n";
echo "Finding DAT entity...";
foreach ($DB->request("glpi_entities", "`name` = 'DAT'") as $data) {
         $SOFTWARE_ROOT_ENTITY = $data['id'];
         echo "... found, ID $SOFTWARE_ROOT_ENTITY\n";
         break;
}
if (!$SOFTWARE_ROOT_ENTITY) {
   echo "Exit!!";
   exit();
}

$query = "SELECT `gs`.id, `gs`.name, `gs`.entities_id, `gm`.`name` as manufacturer " .
         "FROM `glpi_softwares` AS gs " .
         "LEFT JOIN `glpi_manufacturers` AS gm ON (`gs`.`manufacturers_id` = `gm`.`id`) " .
         "WHERE `gs`.`is_template` = '0' " .
         "ORDER BY `name` ASC";
$software_name   = false;
$array_softwares = array();
foreach ($DB->request($query) as $data) {
   //Name is empty : do not process
   if ($data['name'] == '') {
      continue;
   }
   if ($software_name != $data['name'] && $software_name != false) {
      doTransferAndGroupement($array_softwares);
      $array_softwares = array();

   }
   $software_name     = $data['name'];
   $array_softwares[] = $data;
}

#On s'assure que TOUS les softs dans DAT sont récursifs
$query = "UPDATE `glpi_softwares` SET `is_recursive` = '1'
          WHERE `entities_id` = '$SOFTWARE_ROOT_ENTITY'";
$result = $DB->query($query);
