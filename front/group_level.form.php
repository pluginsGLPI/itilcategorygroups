<?php

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkCentralAccess();

$level = new PluginMeteofrancehelpdeskGroup_Level();

if (isset($_POST["add"])) {
   $level->add($_POST);
   Html::back();

} else if (isset($_POST["update"])) {
   $level->update($_POST);
   Html::back();

}
Html::displayErrorAndDie("lost");
?>