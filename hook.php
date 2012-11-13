<?php
/*
 * @version $Id: hook.php 18 2012-06-19 16:06:58Z walid $
 LICENSE

  This file is part of the meteofrancegmao plugin.

 Order plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Order plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; along with meteofrancegmao. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   meteofrancegmao
 @author    the meteofrancegmao plugin team
 @copyright Copyright (c) 2010-2011 meteofrancegmao plugin team
 @license   GPLv2+
            http://www.gnu.org/licenses/gpl.txt
 @link      https://forge.indepnet.net/projects/meteofrancegmao
 @link      http://www.glpi-project.org/
 @since     2009
 ---------------------------------------------------------------------- */

function plugin_meteofrancegmao_install() {
   return true;
}

function plugin_meteofrancegmao_uninstall() {
   return true;
}

function plugin_meteofrancegmao_transfer($options = array()) {
   global $DB, $DBocs;
   //Seulement traiter les ordinateurs
   if ($options['type'] == 'Computer') {
      $item   = new Computer();
      
      //On récupére les infos du matos transféré t on traite s'il est importé par OCS
      if ($item->getFromDB($options['newID']) && $item->fields['is_ocs_import']) {
         //On récupère les données de la nouvelle entité
         $edata  = new EntityData();
         $query  = "SELECT `tag`
                       FROM `glpi_entitydatas`
                       WHERE `entities_id`='".$item->fields['entities_id']."' LIMIT 1";
         $result = $DB->query($query);
         if ($DB->numrows($result)) {
            $data = $DB->fetch_array($result);
            $tag  = $data['tag'];
         } else {
            $tag = false;
         }
          
         //Si le nom de l'entité fini par _GMAO, alors on traite
         if ($tag && preg_match(GMAO_ENTITY_TAG_PATTERN, $tag)) {
            //On cherche les infos sur la liaison OCS correspondantes
            $query  = "SELECT `id`, `tag`, `ocsservers_id`, `ocsid`
                       FROM `glpi_ocslinks`
                       WHERE `computers_id`='".$options['newID']."'";
            $result = $DB->query($query);
            if ($DB->numrows($result) == 1) {
               $data = $DB->fetch_array($result);
                
               //Si le tag stocké dans GLPI pour cette machine est différent : on met à jour dans OCS & GLPI
               if ($data['tag'] != $tag) {
                  //MAJ TAG OCS
                  OcsServer::checkOCSconnection($data['ocsservers_id']);
                  $query = "UPDATE `accountinfo`
                            SET `TAG`='$tag'
                            WHERE `HARDWARE_ID`='".$data['ocsid']."'";
                  $DBocs->query($query);
                  
                  //MAJ TAG GLPI
                  $tmp['tag'] = $tag;
                  $tmp['id']  = $data['id'];
                  $ocslink    = new OcsLink();
                  $ocslink->update($tmp);
               }
            }
         }
      }
   }
   return true;
}
?>