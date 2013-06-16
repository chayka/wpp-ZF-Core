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
    
    $.fixItem = function(id, $item, $bottomContainer, mode, offsetTop, offsetBottom){
        offsetTop = undefined == offsetTop? 0 : offsetTop;
        offsetBottom = undefined == offsetBottom? 0 : offsetBottom;
        if('next' == $bottomContainer){
            mode = 'margin';
            $bottomContainer = $($item).next();
        }else if('parent' == $bottomContainer || !$bottomContainer){
            mode = 'padding'
            $bottomContainer = $($item).parent();
        }
        var itemHeight = parseInt($item.css('margin-top')) 
            + $item.height() 
            + parseInt($item.css('margin-bottom'));
        var bottomContainerHeight = parseInt($bottomContainer.css('margin-top')) 
            + $bottomContainer.height() 
            + parseInt($bottomContainer.css('margin-bottom'));
        if(mode != undefined && mode){
            $bottomContainer.css(mode+'-top', itemHeight+'px');
        }
//        var itemHeight = $item.height() + offsetBottom
        var offset_1 = $item.offset().top - parseInt($item.css('margin-top'));
        var offset_2 = $bottomContainer.offset().top + (bottomContainerHeight - itemHeight - offsetBottom);
        if(offset_2 > offset_1){
            $.addItemToScrollLayout(id, $($item), [
                {
                    since: 0,
                    alias: 'top',
                    css: {'position': 'absolute', 'top':offset_1+'px'}
                },{
                    since: offset_1 - offsetTop,
                    alias: 'fixed',
                    css: {'position': 'fixed', 'top':offsetTop+'px'}
                },{
                    since: offset_2,
                    alias: 'bottom',
                    css: {'position': 'absolute', 'top':(offset_2+offsetTop)+'px'}
                }

            ]);
        }
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


