<?php
/*
 * @version $Id: setup.php 19 2012-06-27 09:19:05Z walid $
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

// Init the hooks of the plugins -Needed
function plugin_init_teclibtoolbox() {
   global $PLUGIN_HOOKS,$CFG_GLPI,$LANG;
    
   $plugin = new Plugin();
   if ($plugin->isInstalled('teclibtoolbox') && $plugin->isActivated('teclibtoolbox')) {
       
      //if glpi is loaded
      if (getLoginUserID()) {
          
         // Display a menu entry ?
         if (haveRight("config", "w")) {
            //add teclibtoolbox to items details
            $PLUGIN_HOOKS['headings']['teclibtoolbox']           = 'plugin_get_headings_teclibtoolbox';
            $PLUGIN_HOOKS['headings_action']['teclibtoolbox']    = 'plugin_headings_actions_teclibtoolbox';
         }
         $PLUGIN_HOOKS['post_init']['teclibtoolbox']                = 'plugin_teclibtoolbox_postinit';
      }
   }
}

// Get the name and the version of the plugin - Needed
function plugin_version_teclibtoolbox() {
   global $LANG;

   $author = "<a href='www.teclib.com'>TECLIB'</a>";
   return array ('name' => $LANG['plugin_teclibtoolbox']['title'][1],
                   'version' => '0.80',
                   'author' => $author,
                   'homepage' => 'https://forge.indepnet.net/projects/show/teclibtoolbox',
                   'minGlpiVersion' => '0.80.0');
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_teclibtoolbox_check_prerequisites() {
   if (version_compare(GLPI_VERSION,'0.80','lt') || version_compare(GLPI_VERSION,'0.81','ge')) {
      echo "This plugin requires GLPI >= 0.80 and GLPI < 0.81";
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_teclibtoolbox_check_config() {
   return true;
}