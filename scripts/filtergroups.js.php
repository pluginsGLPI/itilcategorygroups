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

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT."/inc/includes.php");

//change mimetype
header("Content-type: application/javascript");

$JS = <<<JAVASCRIPT
Ext.onReady(function() {\n
   // only in ticket form
   if (location.pathname.indexOf('ticket.form.php') > 0) {

      //get id of cat select
         var cat_select_dom_id = Ext.select("select[name=itilcategories_id]")
            .elements[0].attributes.getNamedItem('id').nodeValue;

      // separating the GET parameters from the current URL
      var getParams = document.URL.split("?");
      // transforming the GET parameters into a dictionnary
      var url_params = Ext.urlDecode(getParams[getParams.length - 1]);
      // get tickets_id
      var tickets_id = url_params['id'];

      //only in edit form
      if(tickets_id == undefined) {
         // -----------------------
         // ---- Create Ticket ---- 
         // -----------------------
        
        cat_id = Ext.get(cat_select_dom_id).getValue();
        if (cat_id == 0) return;

        //perform an ajax request to get the new options for the group list
         Ext.Ajax.request({
            url: '../plugins/itilcategorygroups/ajax/group_values.php',
            params: {
               'cat_id': cat_id,
               'tickets_id': 0
            },
            success: function(response, opts) {
               options = response.responseText;

               setTimeout(function() {  
                  var assign_select_dom_id = Ext.select("select[name=_groups_id_assign]")
                        .elements[0].attributes.getNamedItem('id').nodeValue;

                  //replace groups select by ajax response
                  Ext.get(assign_select_dom_id).update(options);
               }, 200);
            }
         });

      } else {
         // -----------------------
         // ---- Update Ticket ---- 
         // -----------------------
         
         //remove # in tickets_id
         tickets_id = parseInt(tickets_id);
         
         //get id of itilactor select
         var actor_select_dom_id = Ext.select("select[name*=_itil_assign\[_type]")
            .elements[0].attributes.getNamedItem('id').nodeValue;

         Ext.Ajax.on('requestcomplete', function(conn, response, option) {
            //trigger the filter only on actor(group) selected
            if (option.url.indexOf('dropdownItilActors.php') > 0 
               && option.params.indexOf("group") > 0 && option.params.indexOf("assign") > 0) {

               //delay the execution (ajax requestcomplete event fired before dom loading)
               setTimeout( function () {
             
                  //get ticket_cat value
                  cat_id = Ext.get(cat_select_dom_id).getValue();

                  //perform an ajax request to get the new options for the group list
                  Ext.Ajax.request({
                     url: '../plugins/itilcategorygroups/ajax/group_values.php',
                     params: {
                        'cat_id': cat_id,
                        'tickets_id': tickets_id
                     },
                     success: function(response, opts) {
                        options = response.responseText;

                        var assign_select_dom_id = Ext.select("select[name*=_itil_assign\[groups_id]")
                           .elements[0].attributes.getNamedItem('id').nodeValue;

                        var id_num = assign_select_dom_id.
                                       replace("dropdown__itil_assign[groups_id]", "");

                        //remove input associated to search dropdown
                        var input_search = Ext.get('search_'+id_num);
                        if (input_search) {
                           input_search.remove();
                        }

                        //replace groups select by ajax response
                        var dropdown_search = Ext.get(assign_select_dom_id);
                        if (dropdown_search) {
                           dropdown_search.update(options);
                        }
                     }
                  });
               }, 200);   //end timeout
            } 
         }, this); //end on requestcomplet
      } // end if update ticket
   }
});
JAVASCRIPT;
echo $JS;
