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
                                                               intval($_REQUEST['itilcategories_id']));
if (! empty($condition)) {
   $rand = mt_rand();
   $_SESSION['glpicondition'][$rand] = $condition;
   
   $_GET["condition"] = $rand;
   require ("../../../ajax/getDropdownValue.php");
} else {
   echo '{"results":[{"id":0,"text":"-----"}],"count":0}';
}
