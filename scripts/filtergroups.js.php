<?php
include ("../../../inc/includes.php");

//change mimetype
header("Content-type: application/javascript");

$JS = <<<JAVASCRIPT
function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i=0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) {
            return sParameterName[1];
        }
    }
}

// only in ticket form

var url = '{$CFG_GLPI['root_doc']}/plugins/itilcategorygroups/ajax/group_values.php';
var tickets_id = getUrlParameter('id');

var getItilcategories_id = function () {
   var cat_select_dom_id = $("[name=itilcategories_id]")[0].id;
   return itilcategories_id = $("#"+cat_select_dom_id).val();
};

var redefineDropdown = function (id, url, tickets_id) {
   //console.log("category id : "+itilcategories_id);

   $('#' + id).select2({
      width:                   '80%',
      minimumInputLength:      0,
      quietMillis:             100,
      minimumResultsForSearch: 50,
      closeOnSelect:           false,
      ajax: {
         url: url,
         dataType: 'json',
         data: function (term, page) {
            return {
               ticket_id:         tickets_id,
               itilcategories_id: getItilcategories_id()
            };
         },
         results: function (data, page) {
            var more = (data.count >= 100);
            return { results: data.results, more: more };
         }
      },
      initSelection: function (element, callback) {
         var id = $(element).val();
         var defaultid = '0';
         if (id !== '') {
            // No ajax call for first item
            if (id === defaultid) {
              var data = {id: 0,
                        text: "-----"};
               callback(data);
            } else {
               $.ajax(url, {
                  data: {
                     ticket_id: tickets_id,
                     itilcategories_id: getItilcategories_id()
                  },
                  dataType: 'json',
               }).done(function(data) {
                  if (data.results[0].id == defaultid) {
                     var data = {id: 0, text: "-----"};
                  }
                  callback(data);
               });
            }
         }
      },
      formatResult: function(result, container, query, escapeMarkup) {
         var markup=[];
         window.Select2.util.markMatch(result.text, query.term, markup, escapeMarkup);
         if (result.level) {
            var a='';
            var i=result.level;
            while (i>1) {
               a = a+'&nbsp;&nbsp;&nbsp;';
               i=i-1;
            }
            return a+'&raquo;'+markup.join('');
         }
         return markup.join('');
      }
   });
};

var triggerNewTicket = function() {
   setTimeout(function() {
      if (getItilcategories_id() == 0) {
         return;
      }
      var assign_select_dom_id = $("*[name='_groups_id_assign']")[0].id;
      redefineDropdown(assign_select_dom_id, url, 0);
   }, 300);
};

var triggerupdateTicket = function() {
   setTimeout(function() {
      if (getItilcategories_id() == 0) {
         return;
      }
      var assign_select_dom_id = $("*[name='_itil_assign[groups_id]']")[0].id;
      redefineDropdown(assign_select_dom_id, url, tickets_id);
   }, 300);
};

$(document).ready(function() {
   if (location.pathname.indexOf('ticket.form.php') >= 0) {
   
      if (tickets_id == undefined) {
         // ---- Create Ticket ----
         triggerNewTicket();
         jQuery('div.ui-tabs').tabs({
            load: function( event, ui ) {
               triggerNewTicket();
            }
         });
   
      } else {
         // ---- Update Ticket ----             
         $(document).ajaxSend(function( event, jqxhr, settings ) {
            if (settings.url.indexOf("dropdownItilActors.php") > 0 
               && settings.data.indexOf("group") > 0
                  && settings.data.indexOf("assign") > 0
               ) {
               triggerupdateTicket();
            }
         });
   
      }
   }
});
JAVASCRIPT;
echo $JS;
