(function($, _){
    $.scrolly = { 
        timeout: null,
        meter: $('.scrolly')
    };
    
    $.scrolly.scrollLayout = {
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
    
    $.scrolly.addItemToScrollLayout = function(id, element, rules){
        var item = _.getItem($.scrolly.scrollLayout, id);
        if(item){
            item.rules.concat(rules);
        }else{
            $.scrolly.scrollLayout[id] = {
                element: element,
                rules: rules
            };
        }
    };
    
    $.scrolly.fixItem = function(id, $element, params /*$bottomContainer, mode, offsetTop, offsetBottom*/){
        $.scrolly.fixItemXY(id, $element, [params]);
    };
    
    $.scrolly.fixItemXY = function(id, $element, params /*$bottomContainer, mode, offsetTop, offsetBottom*/){
        params = params || [];
        var rules = [];
        for(var x in params){
            var xRange = params[x];
            var $bottomContainer = _.getItem(xRange, '$bottomContainer', $('body'));
            var mode = _.getItem(xRange, 'mode');
            var offsetTop = _.getItem(xRange, 'offsetTop', 0);
            var offsetBottom = _.getItem(xRange, 'offsetBottom', 0);
            var minWidth = _.getItem(xRange, 'minWidth', 0);
            var maxWidth = _.getItem(xRange, 'maxWidth', 'infinity');
            var isStatic = _.getItem(xRange, 'static', false);

            if('next' === $bottomContainer){
                mode = 'margin';
                $bottomContainer = $($element).next();
            }else if('parent' === $bottomContainer || !$bottomContainer){
                mode = 'padding';
                $bottomContainer = $($element).parent();
            }
            
            if(!isStatic){
                rules.push({
                        alias: 'top',
                        minWidth: minWidth,
                        maxWidth: maxWidth,
                        offsetTop: offsetTop,
                        offsetBottom: offsetBottom,
                        bottomContainer: $bottomContainer,
                        mode: mode
                    });
                rules.push({
                        alias: 'fixed',
                        minWidth: minWidth,
                        maxWidth: maxWidth,
                        offsetTop: offsetTop,
                        offsetBottom: offsetBottom,
                        bottomContainer: $bottomContainer,
                        mode: mode
                    });

                rules.push({
                        alias: 'bottom',
                        minWidth: minWidth,
                        maxWidth: maxWidth,
                        offsetTop: offsetTop,
                        offsetBottom: offsetBottom,
                        bottomContainer: $bottomContainer,
                        mode: mode
    //                    since: offset_2,
    //                    css: {'position': 'absolute', 'top':(offset_2+offsetTop)+'px'}
                    });
            }else{
                rules.push({
                        alias: 'static',
                        minWidth: minWidth,
                        maxWidth: maxWidth,
                        bottomContainer: $bottomContainer
                    });
            }
        }

        $.scrolly.addItemToScrollLayout(id, $($element), rules);
    };
    
    $.scrolly.processXYRange = function($element, params){
        params = params || {};
        var $bottomContainer = _.getItem(params, 'bottomContainer');
        var mode = _.getItem(params, 'mode');
        var offsetTop = _.getItem(params, 'offsetTop', 0);
        var offsetBottom = _.getItem(params, 'offsetBottom', 0);

        var itemHeight = parseInt($element.css('margin-top')) 
            + $element.height() 
            + parseInt($element.css('margin-bottom'));
        var bottomContainerHeight = parseInt($bottomContainer.css('margin-top')) 
            + $bottomContainer.height() 
            + parseInt($bottomContainer.css('margin-bottom'));
//        if(mode !== undefined && mode){
//            $bottomContainer.css(mode+'-top', itemHeight+'px');
//        }
        var offset_1 = $element.offset().top - parseInt($element.css('margin-top'));
        var offset_2 = $bottomContainer.offset().top + (bottomContainerHeight - itemHeight - offsetBottom);
        switch(params.alias){
            case 'top':
                params.since = 0;
                params.to = offset_1 - offsetTop;
                params.css = {'position': 'absolute', 'top':offset_1+'px'};
                params.itemHeight = itemHeight;
                break;
            case 'fixed':
                params.since = offset_1 - offsetTop;
                params.to = offset_2;
                params.css = {'position': 'fixed', 'top':offsetTop+'px'};
                params.itemHeight = itemHeight;
                break;
            case 'bottom':
                params.since = offset_2;
                params.css = {'position': 'absolute', 'top':(offset_2+offsetTop)+'px'};
                params.itemHeight = itemHeight;
                break;
            case 'static':
                params.since = 0;
                params.css = {'position': '', 'top':''};
                params.itemHeight = 0;
                break;
        }
        
        return params;
    };
    
    $.scrolly.onResize = function(){
//        console.log('>$.onResize');
        var needScroll = false;
        var windowWidth = $( window ).width(); // X-coord that is checked against fromX & toX
        for(var id in $.scrolly.scrollLayout){
            // cycling through all visual elements that should react 
            // to scrolling and resizing
           var item = $.scrolly.scrollLayout[id];
           var areas = item.rules.length;
           for(var i in item.rules){
               var rule = item.rules[i];
               var fromX = _.getItem(rule, 'minWidth', 0);
               var toX = _.getItem(rule, 'maxWidth', 'infinity');
//               var checkin = fromX <= windowWidth && ('infinity' === toX || windowWidth < toX);
               var minWidthScrolly = parseInt($.scrolly.meter.css('min-width'));
               var maxWidthScrolly = $.scrolly.meter.css('max-width');
               maxWidthScrolly = maxWidthScrolly === 'none'?'infinity':parseInt(maxWidthScrolly);
               var checkin = fromX === minWidthScrolly && toX === maxWidthScrolly;
               needScroll |= checkin;
               if(checkin && rule.since === undefined){    
                   $(item.element).css('position', '');
                   $(item.element).css('top', '');
                   item.rules[i].bottomContainer.css('margin-top', '');
//                   pause(100);
               // item entered new range and should adapt
                    item.rules[i] = $.scrolly.processXYRange(item.element, rule);
                    
               }
           }
        }  
        if(needScroll){
            // dark magick here do not touch this useless string
            $.scrolly.scrollLayout = $.scrolly.scrollLayout;
            setTimeout($.scrolly.onScroll, 0);
//            $.scrolly.onScroll();
        }
        return true;
//        console.log('<$.onResize');
    };
    
    $.scrolly.onScroll = function(){
//        console.log('>$.onScrol');
        var scrollPos = $(this).scrollTop(); // Y-coord that is checked against fromY & toY
        var windowWidth = $( window ).width(); // X-coord that is checked against fromX & toX
        for(var id in $.scrolly.scrollLayout){
            // cycling through all visual elements that should react 
            // to scrolling and resizing
           var item = $.scrolly.scrollLayout[id];
           var areas = item.rules.length;
//           if(item && item.rules){
//               console.dir({itemXXX: item});
           for(var i in item.rules){
               var fromY = _.getItem(item.rules[i], 'since', 0);
               var toY = _.getItem(item.rules[i], 'to', 'bottom');
               var fromX = _.getItem(item.rules[i], 'minWidth', 0);
               var toX = _.getItem(item.rules[i], 'maxWidth', 'infinity');
//               var to = i < areas - 1 ? item.rules[i+1].since:'bottom';
//               var checkin = fromY <= scrollPos && ('bottom' === toY || scrollPos < toY)
//                        &&   fromX <= windowWidth && ('infinity' === toX || windowWidth < toX);
               var minWidthScrolly = parseInt($.scrolly.meter.css('min-width'));
               var maxWidthScrolly = $.scrolly.meter.css('max-width');
               maxWidthScrolly = maxWidthScrolly === 'none'?'infinity':parseInt(maxWidthScrolly);
               var checkin = fromY <= scrollPos && ('bottom' === toY || scrollPos < toY)
                        &&   fromX === minWidthScrolly && toX === maxWidthScrolly;
               fromY = item.rules[i].alias||fromY;
//               toY = i < areas - 1 && item.rules[i+1].alias ? item.rules[i+1].alias:toY;
               var newClass = 'scroll-pos-'+fromY+'-to-'+toY+' window-width-'+fromX+'-to-'+toX;
               var lastClass = item.lastClass || '';
               var lastRule = item.lastRule !== undefined? item.lastRule: null;
               if(checkin && lastRule !== i){    
               // item entered new range and should adapt
                   $.scrolly.scrollLayout[id].lastClass = newClass;
                   $.scrolly.scrollLayout[id].lastRule = i;

                   if(lastRule !== null && item.rules[lastRule].onCheckOut){
                       item.rules[lastRule].onCheckOut(item.element);
                   }
                   if(item.rules[i].css){
                       item.element.css(item.rules[i].css);
                   }
                   if(item.rules[i].addClass){
                       item.element.addClass(item.rules[i].addClass);
                   }
                   if(item.rules[i].removeClass){
                       item.element.removeClass(item.rules[i].removeClass);
                   }
                   item.element.removeClass(lastClass);
                   item.element.addClass(newClass);
                   
                   var $bottomContainer = _.getItem(item.rules[i], 'bottomContainer');
                   var mode = _.getItem(item.rules[i], 'mode');
                   var itemHeight = _.getItem(item.rules[i], 'itemHeight');
                   
                    if($bottomContainer && mode && itemHeight){
                        $bottomContainer.css(mode+'-top', itemHeight+'px');
                    }
                    
                   if(item.rules[i].onCheckIn){
                       item.rules[i].onCheckIn(item.element);
                   }
               }
           }
//        }        
        }
//        console.log('<$.onScrol');

    };
    

$(document).ready(function(){
    $(window).resize($.scrolly.onResize).resize();
    $(document).scroll($.scrolly.onScroll).scroll();

});
//    .resize($.onResize) 
//    .resize();
    
}(jQuery, _));


