<?php

/**
 * DRAFT for filter groups based on category
 * This file must be loaded with javascrit hook
 * we must provide an ajax url who return <option>values>/<option>
 * see TODO
 */

define('GLPI_ROOT', '../..');
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
            url: '../plugins/example/ajax/group_values.php', //TODO: replace this url
            params: {
               'cat_id': cat_id
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


?>