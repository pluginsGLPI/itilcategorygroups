<?php
$AJAX_INCLUDE = 1;

include ("../../../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();

if (! isset($_REQUEST['itilcategories_id'])) {
   exit;
}

$ticket_id = (isset($_REQUEST['ticket_id'])) ? $_REQUEST['ticket_id'] : 0;

PluginItilcategorygroupsCategory::filteredDropdownAssignGroups(intval($ticket_id), 
                                                               intval($_REQUEST['itilcategories_id']));
