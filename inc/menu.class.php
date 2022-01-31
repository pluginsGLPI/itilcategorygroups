<?php

/**
 * -------------------------------------------------------------------------
 * ItilCategoryGroups plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of ItilCategoryGroups.
 *
 * ItilCategoryGroups is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ItilCategoryGroups is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ItilCategoryGroups. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2012-2022 by ItilCategoryGroups plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/itilcategorygroups
 * -------------------------------------------------------------------------
 */

class PluginItilcategorygroupsMenu extends CommonGLPI {

   static function getTypeName($nb = 0) {
      return __('Link ItilCategory - Groups', 'itilcategorygroups');
   }

   static function getMenuName() {
      return __('ItilCategory Groups', 'itilcategorygroups');
   }

   static function getMenuContent() {
      global $CFG_GLPI;
      $menu          = [];
      $menu['title'] = self::getMenuName();
      $menu['page']  = '/' . Plugin::getWebDir('itilcategorygroups', false) . '/front/category.php';
      $menu['icon']  = PluginItilcategorygroupsCategory::getIcon();

      if (Session::haveRight('config', READ)) {

         $menu['options']['model']['title'] = PluginItilcategorygroupsMenu::getTypeName();
         $menu['options']['model']['page'] = Toolbox::getItemTypeSearchUrl('PluginItilcategorygroupsCategory', false);
         $menu['options']['model']['links']['search'] = Toolbox::getItemTypeSearchUrl('PluginItilcategorygroupsCategory', false);

         if (Session::haveRight('config', UPDATE)) {
            $menu['options']['model']['links']['add'] = Toolbox::getItemTypeFormUrl('PluginItilcategorygroupsCategory', false);
         }

      }

      return $menu;
   }

}