<?php
/*
 * @version $Id: hook.php 18 2012-06-19 16:06:58Z walid $
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

function plugin_meteofrancehelpdesk_install() {
   include_once(GLPI_ROOT."/plugins/meteofrancehelpdesk/inc/category_group.class.php");
   $migration = new Migration("0.83");
   PluginMeteofrancehelpdeskCategory_Group::install($migration);
   return true;
}

function plugin_meteofrancehelpdesk_uninstall() {
   include_once(GLPI_ROOT."/plugins/meteofrancehelpdesk/inc/category_group.class.php");
   PluginMeteofrancehelpdeskCategory_Group::uninstall();
   return true;
}

?>