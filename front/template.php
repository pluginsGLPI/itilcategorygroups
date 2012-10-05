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
global $LANG;
define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

if (isset($_POST['copy_template'])) {
   PluginTeclibtoolboxTemplate::copyTemplate($_POST['itemtype'], $_POST['id']);
}
Htlm::back();
?>