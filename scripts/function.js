var getItilcategories_id = function () {
   var itilcategories_id = $("input[name*='itilcategories_id'").val();
   return itilcategories_id;
};

var getUrlParameter = function(val) {
   var result = "Not found",
       tmp = [];

   location.search
      .substr(1) // remove '?'
      .split("&")
      .forEach(function (item) {
         tmp = item.split("=");
         if (tmp[0] === val) {
            result = decodeURIComponent(tmp[1]);
         }
      });
   return result;
};


var checkDOMChange = function (selector, handler) {
   if ($(selector).get(0)) {
      return handler();
   }
   setTimeout( function() {
      checkDOMChange(selector, handler);
   }, 100 );
};