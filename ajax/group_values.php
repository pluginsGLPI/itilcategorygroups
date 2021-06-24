<?php
$AJAX_INCLUDE = 1;

include ("../../../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();

$tickets_id = (int) $_REQUEST['ticket_id'] ?? 0;
$ticket = new Ticket;
$ticket->getFromDB($tickets_id);

if (!isset($_REQUEST['itilcategories_id'])) {
   $_REQUEST['itilcategories_id'] = $ticket->fields['itilcategories_id'];
}
if (!isset($_REQUEST['type'])) {
   $_REQUEST['type'] = $ticket->fields['type'];
}

$condition = PluginItilcategorygroupsCategory::getSQLCondition(
   $tickets_id,
   intval($_REQUEST['itilcategories_id']),
   $_REQUEST['type']
);

if (empty($condition)) {
   $condition = [
      'glpi_groups.is_assign' => 1,
   ] + getEntitiesRestrictCriteria("", "entities_id", $_SESSION['glpiactive_entity'], 1);
}

if (! empty($condition)) {
   $_POST['display_emptychoice'] = true;
   $_POST['itemtype'] = 'Group';
   $_POST['condition'] = Dropdown::addNewCondition($condition);

   require "../../../ajax/getDropdownValue.php";
}
