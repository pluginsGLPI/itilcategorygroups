<?php

class PluginItilcategorygroupsMenu extends CommonGLPI {

   static function getTypeName($nb=0) {
      return __('Link ItilCategory - Groups', 'itilcategorygroups');
   }
   
   static function getMenuName() {
      return __('ItilCategory Groups', 'itilcategorygroups');
   }

   static function getMenuContent() {
      global $CFG_GLPI;
      $menu          = array();
      $menu['title'] = self::getMenuName();
      $menu['page']  = '/plugins/itilcategorygroups/front/category.php';
      
      //if (Session::haveRight(static::$rightname, READ)) {
      if (Session::haveRight('config', READ)) {
         
         $menu['options']['client']['title'] = self::getMenuName();
         $menu['options']['client']['page'] = Toolbox::getItemTypeSearchUrl('PluginItilcategorygroupsModel', false);
         $menu['options']['client']['links']['search'] = '/plugins/itilcategorygroups/front/clientinjection.form.php_search';

         $menu['options']['model']['title'] = PluginItilcategorygroupsMenu::getTypeName();
         $menu['options']['model']['page'] = Toolbox::getItemTypeSearchUrl('PluginItilcategorygroupsCategory', false);
         $menu['options']['model']['links']['search'] = Toolbox::getItemTypeSearchUrl('PluginItilcategorygroupsCategory', false);
         
         $image_model  = "<img src='".$CFG_GLPI["root_doc"]."/pics/rdv.png' title='";
         $image_model .= PluginItilcategorygroupsMenu::getTypeName(); //PluginItilcategorygroupsModel::getTypeName();
         $image_model .= "' alt='".PluginItilcategorygroupsMenu::getTypeName()."'>";

         $menu['options']['client']['links'][$image_model]  = Toolbox::getItemTypeSearchUrl('PluginItilcategorygroupsModel', false);

         if (Session::haveRight('config', UPDATE)) {
            $menu['options']['model']['links']['add'] = Toolbox::getItemTypeFormUrl('PluginItilcategorygroupsCategory', false);
            $menu['options']['client']['links'][$image_model] = 'toto.php'; //Toolbox::getItemTypeSearchUrl('PluginItilcategorygroupsCategory', false);
         }
      
      }

      return $menu;
   }

}