<?php
/*
 * @version $Id: setup.php 19 2012-06-27 09:19:05Z walid $
LICENSE

This file is part of the meteofrancehelpdesk plugin.

Order plugin is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Order plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI; along with meteofrancehelpdesk. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
@package   meteofrancehelpdesk
@author    the meteofrancehelpdesk plugin team
@copyright Copyright (c) 2010-2011 meteofrancehelpdesk plugin team
@license   GPLv2+
http://www.gnu.org/licenses/gpl.txt
@link      https://forge.indepnet.net/projects/meteofrancehelpdesk
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

      var ticket_id = document.form_ticket.elements['id'].value;
      
      //get id of itilactor select
      var actor_select_dom_id = Ext.select("select[name*=_itil_assign\[_type]")
         .elements[0].attributes.getNamedItem('id').nodeValue;

      //trigger the filter only on actor(group) selected
      Ext.get(actor_select_dom_id).on("change", function() {
         if(this.getValue() != 'group') return;
          
         //get ticket_cat value
         cat_id = Ext.get(cat_select_dom_id).getValue();

         //perform an ajax request to get the new options for the group list
         Ext.Ajax.request({
            url: '../plugins/meteofrancehelpdesk/ajax/group_values.php',
            params: {
               'cat_id': cat_id,
               'ticket_id': ticket_id
            },
            success: function(response, opts) {
               options = response.responseText;

               var assign_select_dom_id = Ext.select("select[name*=_itil_assign\[groups_id]")
                  .elements[0].attributes.getNamedItem('id').nodeValue;

               //replace groups select by ajax response
               Ext.get(assign_select_dom_id).update(options);
               
            },
            failure: function(response, opts) {
               console.log('server-side failure with status code ' + response.status);
            }
         });
      });
      
   }
});
JAVASCRIPT;
echo $JS;
