<?php
/*
 * @version $Id: hook.php 18 2012-06-19 16:06:58Z walid $
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

function plugin_teclibtoolbox_install() {
   return true;
}

function plugin_teclibtoolbox_uninstall() {
   return true;
}

// Define headings added by the plugin

function plugin_get_headings_teclibtoolbox($item, $withtemplate) {
   global $LANG;
   if ($item instanceof CommonDBTM
      && haveRight('config', 'w')
         && $item->mayBeTemplate()
            && $item->fields['is_template'] == 1 & $withtemplate) {
      return array(1 => $LANG['plugin_teclibtoolbox']['template'][1]);
   }

   return false;
}

// Define headings actions added by the plugin

function plugin_headings_actions_teclibtoolbox($item) {
   if ($item instanceof CommonDBTM
      && haveRight('config', 'w')
         && $item->mayBeTemplate()
            && $item->fields['is_template'] == 1) {
      return array(1 => "plugin_headings_teclibtoolbox");
   }
   
   return false;
}


// action heading
function plugin_headings_teclibtoolbox($item,$withtemplate=0) {
   //if (haveRight('config', 'w') && $item->mayBeTemplate() && $item->fields['is_template'] == 1) {
      PluginTeclibtoolboxTemplate::showForItem($item);
   //}
}

?>