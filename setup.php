<?php
/*
 * @version $Id: setup.php 19 2012-06-27 09:19:05Z walid $
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

define ("GMAO_ENTITY_TAG_PATTERN", "/_GMAO$/");

function plugin_init_meteofrancegmao() {
   global $PLUGIN_HOOKS;
    
   $PLUGIN_HOOKS['csrf_compliant']['meteofrancegmao'] = true;
   
   $plugin = new Plugin();
   if ($plugin->isInstalled('meteofrancegmao') && $plugin->isActivated('meteofrancegmao')) {
      $PLUGIN_HOOKS['item_transfer']['meteofrancegmao'] = 'plugin_meteofrancegmao_transfer';
   }
}

// Get the name and the version of the plugin - Needed
function plugin_version_meteofrancegmao() {
   global $LANG;

   $author = "<a href='www.teclib.com'>TECLIB'</a>";
   return array ('name'           => $LANG['plugin_meteofrancegmao']['title'][1],
                   'version'        => '0.83',
                   'author'         => $author,
                   'homepage'       => 'www.teclib.com',
                   'minGlpiVersion' => '0.83.3');
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_meteofrancegmao_check_prerequisites() {
   if (version_compare(GLPI_VERSION,'0.83.3','lt') || version_compare(GLPI_VERSION,'0.84','ge')) {
      echo "This plugin requires GLPI 0.83.3+";
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_meteofrancegmao_check_config() {
   global $CFG_GLPI;
   if (!$CFG_GLPI['use_ocs_mode']) {
      Session::addMessageAfterRedirect("Le mode OCS doit être activé", INFO);
      return false;
   } else {
      return true;
   }
}