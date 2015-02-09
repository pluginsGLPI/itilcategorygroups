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
$AJAX_INCLUDE = 1;

include ("../../../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();

if (! isset($_REQUEST['itilcategories_id'])) {
   exit;
}
if (! isset($_REQUEST['tickets_id'])) {
   $_REQUEST['tickets_id'] = 0;
}

PluginItilcategorygroupsCategory::filteredDropdownAssignGroups(intval($_REQUEST['tickets_id']), 
                                                               intval($_REQUEST['itilcategories_id']));
