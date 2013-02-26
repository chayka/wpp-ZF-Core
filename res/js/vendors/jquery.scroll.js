(function($){
    
    $.scrollLayout = {
//        topbarSearchForm:{
//            element: searchFormTop,
//            rules:[
//                {
//                    since: 0,
//                    alias: 'top',
//                    css: null,//{'display': 'none'},
//                    addClass: null,
//                    removeClass: null,
//                    onCheckIn: function(element){
//                        element
//                        .hide('fade', 100);
//                        searchInputMain.val(searchInputTop.val());
//                    },
//                    onCheckOut: function(element){}
//                },
//                {
//                    since: searchFormMain.offset().top,
//                    alias: 'searchform',
//                    css: null,//{'display': 'block'},
//                    addClass: null,
//                    removeClass: null,
//                    onCheckIn: function(element){
//                        element.show('fade', 300);
//                        searchInputTop.val(searchInputMain.val());
//                    },
//                    onCheckOut: function(element){}
//                }
//            ]
//        }
        
    };
    
    $.addItemToScrollLayout = function(id, item, rules){
        $.scrollLayout[id] = {
            element: item,
            rules: rules
        };
    }
    
    $(document).scroll(function() {
        for(var id in $.scrollLayout){
           var item = $.scrollLayout[id];
           var areas = item.rules.length;
           var pos = $(this).scrollTop();
           for(var i = 0 ; i < areas ; i++){
               var from = item.rules[i].since;
               var to = i < areas - 1 ? item.rules[i+1].since:'bottom';
               var checkin = from <= pos && ('bottom' == to || pos < to);
               from = item.rules[i].alias||from;
               to = i < areas - 1 && item.rules[i+1].alias?item.rules[i+1].alias:to;
               var newClass = 'scroll-pos-'+from+'-to-'+to;
               var lastClass = item.lastClass || '';
               var lastRule = item.lastRule !=undefined? item.lastRule: null;
               if(checkin && lastRule != i){
                   $.scrollLayout[id].lastClass = newClass;
                   $.scrollLayout[id].lastRule = i;

                   if(lastRule != null && item.rules[lastRule].onCheckOut){
                       item.rules[lastRule].onCheckOut(item.element);
                   }
                   if(item.rules[i].css){
                       item.element.css(item.rules[i].css)
                   }
                   if(item.rules[i].addClass){
                       item.element.addClass(item.rules[i].addClass)
                   }
                   if(item.rules[i].removeClass){
                       item.element.removeClass(item.rules[i].removeClass)
                   }
                   item.element.removeClass(lastClass)
                   item.element.addClass(newClass)

                   if(item.rules[i].onCheckIn){
                       item.rules[i].onCheckIn(item.element);
                   }
                   
               }
           }
        }
    }).scroll();
}(jQuery))


