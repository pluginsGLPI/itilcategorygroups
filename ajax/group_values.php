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

$condition = PluginItilcategorygroupsCategory::getSQLCondition(intval($ticket_id),
                                                               intval($_REQUEST['itilcategories_id']), $_REQUEST['type']);
$rand = mt_rand();
$default_options = array('display_emptychoice' => true,
                         'itemtype'            => 'Group',
                         'condition'           => $rand);


if (! empty($condition)) {
   $_GET = array_merge($_GET, $default_options);
   $_SESSION['glpicondition'][$rand] = $condition;

} else {
   $_GET = array_merge($_GET, $default_options);
   $_SESSION['glpicondition'][$rand]  = getEntitiesRestrictRequest(" ", "", "entities_id",
                                                       $_SESSION['glpiactive_entity'], 1). "AND glpi_groups.is_assign";
}
$_POST = $default_options; // fix for glpi 9.1
require ("../../../ajax/getDropdownValue.php");
