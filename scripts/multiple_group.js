initChosen = function() {
   var elements = document.querySelectorAll('.chzn-select');
   select_chosen = [];
   for (var i = 0; i < elements.length; i++) {
      select_chosen.push(new Chosen(elements[i], {width: '60%'}));
   }
}


toggleSelect = function(level) {
   index = level - 1;

   //toggle select
   var current_select = Ext.select("#select_level_"+level+" select");
   if (current_select.elements[0].disabled == false) {
      current_select.set({'disabled':'disabled'});
   } else {
      current_select.set({'disabled':null}, false)
   }

   //update chosen control
   select_chosen[index].results_update_field();
}