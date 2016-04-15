<?php
include ("../../../inc/includes.php");

//change mimetype
header("Content-type: application/javascript");

$JS = <<<JAVASCRIPT
var groups_url = '{$CFG_GLPI['root_doc']}/plugins/itilcategorygroups/ajax/group_values.php';
var tickets_id = getUrlParameter('id');

var triggerNewTicket = function() {
   cat = getItilcategories_id();
   if (cat == 0) {
      return;

   } else {

      var assign_select_dom_id = $("*[name='_groups_id_assign']")[0].id;

      //var assign_select_dom_id = $("input[id*='_groups_id_assign'").val();
      var type = $("select[id^='dropdown_type']").val();

      redefineDropdown(assign_select_dom_id, groups_url, 0, type);
   }
};

var triggerupdateTicket = function() {
   if (getItilcategories_id() == 0) {
      return;
   } else {
      checkDOMChange("input[name='_itil_assign[groups_id]']", function() {
         var assign_select_dom_id = $("input[name='_itil_assign[groups_id]']")[0].id;
         var type = $("select[id^='dropdown_type']").val();

         redefineDropdown(assign_select_dom_id, groups_url, tickets_id, type);
      });
   }
};

var triggerAll = function() {
   if (tickets_id == 'Not found') {
      triggerNewTicket();
   } else {
      $(document).ajaxSend(function( event, jqxhr, settings ) {
         if (settings.url.indexOf("dropdownItilActors.php") > 0
            && settings.data.indexOf("group") > 0
               && settings.data.indexOf("assign") > 0
            ) {
          triggerupdateTicket();
         }
      });
   }
};

var redefineDropdown = function (id, url, tickets_id, type) {
cat = getItilcategories_id();

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
               type : type,
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
                     type : type,
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

$(document).ready(function() {
   if (location.pathname.indexOf('ticket.form.php') >= 0) {
      setTimeout(function() {
         $(".ui-tabs-panel:visible").ready(function() {
            triggerAll();
         });

         $("#tabspanel + div.ui-tabs").on("tabsload", function() {
            triggerAll();
         });
      }, 300);
   }
});
JAVASCRIPT;
echo $JS;
