(function($, _) {
    $.scrolly = {
        options: {
            timeout: null,
            meter: $('.scrolly'),
            body: document
        },
        theCSSPrefix: '',
        theDashedCSSPrefix: '',
        isMobile: false,
//        requestAnimFrame: null,
//        cancelAnimFrame: null,

        animFrame: null,
        direction: 0,
        scrollTop: 0,
        scrollCenter: 0,
        scrollBottom: 0,
        docHeight: 0,
        docMiddle: 0,
        winHeight: $(window).height()

    };

    $.scrolly.scrollLayout = {
//  TSB - top screen border        
//        topbarSearchForm:{
//            element: searchFormTop,
//            rules:[
//                {
//                    from: 0, // top border of the rule region
//                    to: 'finish', // bottom border of the rule region
//                          // if ommited then set to 'from' of the following rule
//                          // if there is no following rule set to 'bottom'
//                    minWith: 0, // min viewport width for the rule to apply
//                    maxWidth: 'infinity', // max viewport width for the rule to apply
//                    direction: 0, // 0 - ignored, >0 - forward, <0 - backward
//                    alias: 'top', // region alias
//                    css: null,//{'display': 'none'}, // css to apply when TSB enters rule region
//                    addClass: null,   // $.addClass() param value to add classes when TSB enters rule region
//                    removeClass: null,    // $.removeClass() param value to remove classes when TSB enters rule region
//                    onCheckIn: function(element){ // callback on TSB enters rule region
//                        element
//                        .hide('fade', 100);
//                        searchInputMain.val(searchInputTop.val());
//                    },
//                    onCheckOut: function(element){} // callback on TSB leaves rule region
//                    onTopIn: function(element){}  // callback on TSB enters rule region from the top border
//                    onTopOut: function(element){}  // callback on TSB leaves rule region from the top border
//                    onBottomIn: function(element){}  // callback on TSB enters rule region from the bottom border
//                    onBottomOut: function(element){}  // callback on TSB leaves rule region from the bottom border
//                    onScroll: function(element, offset, length){}  // callback on scroll event while TSB is in the rule region
//                                      // offset - is the offset (px) of the TSB from the rule region top border
//                                      // length - is the rule region size (px)
//                    onDirectionChanged: function(element, direction){}
//                },
//                {
//                    from: searchFormMain.offset().top,
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

    /**
     * Parse rule boundry
     * @param {string} boundry - '[document] [docOffset] [vieport] [vpOffset]' if abs
     *                          - [vieport] [vpOffset] [element] [elementOffset] if rel
     * @param {boolean} isAbsolute is positioning abolute 
     * @return {object} - parsed boundry
     */
    $.scrolly.parseBoundry = function(boundry, isAbsolute) {
        var parsed = {};
        isAbsolute = /^\s*(start|middle|finish)/.test(boundry) || isAbsolute;
        var parts = null;
        if (isAbsolute) {
            var reAbs = /^\s*(start|middle|finish)?(\s*(-?\d+[\%#]?))?(\s+(top|center|bottom))?(\s*(-?\d+[\%#]?))?\s*$/;
            parts = reAbs.exec(boundry);
            parsed = {
                isAbsolute: isAbsolute,
                documentLevel: 'start',
                documentOffset: '0',
                documentRelative: false,
                viewportLevel: 'top',
                viewportOffset: '0',
                viewportRelative: false
            };
            if (parts) {
                parsed.documentLevel = _.getItem(parts, 1, parsed.documentLevel);

                parsed.documentOffset = _.getItem(parts, 3, parsed.documentOffset);
                parsed.documentRelative = false;
                if(parsed.documentOffset.match(/\%$/)){
                    parsed.documentRelative = 'document';
                }else if(parsed.documentOffset.match(/#$/)){
                    parsed.documentRelative = 'element';
                }
                parsed.documentOffset = parsed.documentRelative ? parseFloat(parsed.documentOffset) : parseInt(parsed.documentOffset);

                parsed.viewportLevel = _.getItem(parts, 5, parsed.viewportLevel);

                parsed.viewportOffset = _.getItem(parts, 7, parsed.viewportOffset);
                parsed.viewportRelative = false;
                if(parsed.viewportOffset.match(/\%$/)){
                    parsed.viewportRelative = 'viewport';
                }else if(parsed.viewportOffset.match(/#$/)){
                    parsed.viewportRelative = 'element';
                }
                parsed.viewportOffset = parsed.viewportRelative ? parseFloat(parsed.viewportOffset) : parseInt(parsed.viewportOffset);
            }

        } else {
            var reRel = /^\s*(top|center|bottom)?(\s*(-?\d+[\%#]?))?(\s+(top|center|bottom))?(\s*(-?\d+[\%#]?))?\s*$/;
            parts = reRel.exec(boundry);
            parsed = {
                isAbsolute: isAbsolute,
                viewportLevel: 'top',
                viewportOffset: '0',
                viewportRelative: false,
                containerLevel: 'top',
                containerOffset: '0',
                containerRelative: false
            };
            if (parts) {
                parsed.viewportLevel = _.getItem(parts, 1, parsed.viewportLevel);

                parsed.viewportOffset = _.getItem(parts, 3, parsed.viewportOffset);
                parsed.viewportRelative = false;
                if(parsed.viewportOffset.match(/\%$/)){
                    parsed.viewportRelative = 'viewport';
                }else if(parsed.viewportOffset.match(/#$/)){
                    parsed.viewportRelative = 'element';
                }
                parsed.viewportOffset = parsed.viewportRelative ? parseFloat(parsed.viewportOffset) : parseInt(parsed.viewportOffset);

                parsed.containerLevel = _.getItem(parts, 5, parsed.viewportLevel);

                parsed.containerOffset = _.getItem(parts, 7, parsed.containerOffset);
                parsed.containerRelative = false;
                if(parsed.containerOffset.match(/\%$/)){
                    parsed.containerRelative = 'container';
                }else if(parsed.containerOffset.match(/#$/)){
                    parsed.containerRelative = 'element';
                }
                parsed.containerOffset = parsed.containerRelative ? parseFloat(parsed.containerOffset) : parseInt(parsed.containerOffset);
            }

        }
        return parsed;
    };

    /**
     * Calculate how much we should scroll down till boundry
     * @param {type} boundry
     * @param {type} $element
     * @returns {integet} how much we should scroll down till boundry
     */
    $.scrolly.cmpBoundry = function(boundry, $element, $container) {
        var elementHeight = $element.outerHeight();
        var viewportCoord = 0;
        switch (boundry.viewportLevel) {
            case 'top':
                viewportCoord = $.scrolly.scrollTop;
                break;
            case 'center':
                viewportCoord = $.scrolly.scrollCenter;
                break;
            case 'bottom':
                viewportCoord = $.scrolly.scrollBottom;
                break;
        }
//        var viewportOffset = boundry.viewportRelative ?
//                Math.ceil(boundry.viewportOffset / 100 * $.scrolly.winHeight) :
//                boundry.viewportOffset;
        var viewportOffset = boundry.viewportOffset;
        switch(boundry.viewportRelative){
            case 'element':
                viewportOffset = Math.ceil(boundry.viewportOffset / 100 * elementHeight);
                break;
            case 'viewport':
                viewportOffset = Math.ceil(boundry.viewportOffset / 100 * $.scrolly.winHeight);
                break;
        }
        viewportCoord += viewportOffset;

        if (boundry.isAbsolute) {
            var documentCoord = 0;
            switch (boundry.documentLevel) {
                case 'start':
                    documentCoord = 0;
                    break;
                case 'middle':
                    documentCoord = $.scrolly.docMiddle;
                    break;
                case 'finish':
                    documentCoord = $.scrolly.docHeight;
                    break;
            }

//            var documentOffset = boundry.documentRelative ?
//                    Math.ceil(boundry.documentOffset / 100 * $.scrolly.docHeight) :
//                    boundry.documentOffset;
            var documentOffset = boundry.documentOffset;
            switch(boundry.documentRelative){
                case 'document':
                    documentOffset = Math.ceil(boundry.documentOffset / 100 * $.scrolly.docHeight);
                    break;
                case 'element':
                    documentOffset = Math.ceil(boundry.documentOffset / 100 * elementHeight);
                    break;
            }
            documentCoord += documentOffset;

            return documentCoord - viewportCoord;

        } else {
            var containerCoord = 0;
            var containerHeight = $container.outerHeight();
            var containerTop = $container.offset().top;
            var containerBottom = containerTop + containerHeight;
            var containerCenter = containerTop + Math.floor(containerHeight / 2);
            switch (boundry.containerLevel) {
                case 'top':
                    containerCoord = containerTop;
                    break;
                case 'center':
                    containerCoord = containerCenter;
                    break;
                case 'bottom':
                    containerCoord = containerBottom;
                    break;
            }

//            var containerOffset = boundry.containerRelative ?
//                    Math.ceil(boundry.containerOffset / 100 * containerHeight) :
//                    boundry.containerOffset;
            var containerOffset = boundry.containerOffset;
            switch(boundry.containerRelative){
                case 'container':
                    containerOffset = Math.ceil(boundry.containerOffset / 100 * containerHeight);
                    break;
                case 'element':
                    containerOffset = Math.ceil(boundry.containerOffset / 100 * elementHeight);
                    break;
            }
            containerCoord += containerOffset;
            return containerCoord - viewportCoord;
        }

        return 0;
    };

    $.scrolly.isRuleInActiveWidthRange = function(rule) {
        var fromX = _.getItem(rule, 'minWidth', 0);
        var toX = _.getItem(rule, 'maxWidth', 'infinity');
        var meter = _.getItem($.scrolly.options, 'meter');
        var minWidthScrolly = meter.length ? parseInt(meter.css('min-width')) : 0;
        var maxWidthScrolly = meter.length ? meter.css('max-width') : 'none';
        maxWidthScrolly = maxWidthScrolly === 'none' ? 'infinity' : parseInt(maxWidthScrolly);
        var checkinWidth = fromX <= minWidthScrolly && (toX === 'infinity' || toX >= maxWidthScrolly);
        return checkinWidth;
    };

    /**
     * Check if rule is active
     * 
     * @param {object} rule
     * @param {$(DOMnode)} $element
     * @returns {boolean|object} false if rule is not active or scrolling params instead
     * {
     *      offset: how many pixels since top boundry were scrolled
     *      length: total length of the region in pisels
     * }
     */
    $.scrolly.isRuleActive = function(rule, $element, $container) {
        var checkinWidth = $.scrolly.isRuleInActiveWidthRange(rule);
        if (!checkinWidth) {
            return false;
        }

        var ruleDirection = _.getItem(rule, 'direction', 0);
        var scrollDirection = $.scrolly.direction;

        if (ruleDirection
                && (ruleDirection > 0 && scrollDirection < 0
                        || ruleDirection < 0 && scrollDirection > 0)) {
            return false;
        }

        var isAbsolute = !$container;//?true:false;

        var fromY = _.getItem(rule, 'from', '0');

        if (_.isString(fromY) || _.isNumber(fromY)) {
            fromY = $.scrolly.parseBoundry('' + fromY, isAbsolute);
            rule.from = fromY;
        }

        var toY = _.getItem(rule, 'to', 'finish');

        if (_.isString(toY) || _.isNumber(toY)) {
            toY = $.scrolly.parseBoundry('' + toY, isAbsolute);
            rule.to = toY;
        }

        var toTop = $.scrolly.cmpBoundry(fromY, $element, $container);
        if (toTop > 0) {
            return false;
        }
        var toBottom = $.scrolly.cmpBoundry(toY, $element, $container);
        if (toBottom <= 0) {
            return false;
        }

        return {offset: -toTop, length: toBottom - toTop};

    };

    /**
     * Add ellement with its rules to scroll layout
     * See the commented sample above for the rules syntax
     * 
     * @param {string} id
     * @param {$(DOMnode)} $element
     * @param {array} rules
     * @param {$(DOMnode)} $container description
     */
    $.scrolly.addItem = $.scrolly.addItemToScrollLayout = function(id, $element, rules, $container) {
        if(!$element.length){
            return false;
        }
        if($element.length > 1){
            $element.each(function(i){
                var clonedRules = [];
                for(var j = 0; j < rules.length; j++){
                    var rule = rules[j];
                    var clonedRule = {};
                    $.extend(clonedRule, rule);
                    clonedRules.push(clonedRule);
                }
                var $con = null;
                if($container){
                    if($container === 'self'){
                        $con = $container;
                    }else{
                        $con = $container.length > 1 && i < $container.length ?
                            $($container[i]):$container;
                    }
                }
//                $container.length > 1 && i < $container.length ?
//                    $($container[i]):$container;
                $.scrolly.addItem(id+'-'+i, $(this), clonedRules, $con);
            });
            
            return true;
        }
        var item = _.getItem($.scrolly.scrollLayout, id);
        if (item) {
            item.rules.concat(rules);
        } else {
            $.scrolly.scrollLayout[id] = {
                element: $element,
                container: $container,
                rules: rules
            };
        }
        return true;
    };

    /**
     * Fix DOM element in NON-Responsive (non viewport width dependent) layout.
     * When applied, DOMnode is fixed when TSB is within 
     * (node's top border - offsetTop) and ($bottomContainer's bottom border - offsetBottom)
     * and unfixed when TSB is out of the region
     * 
     * @param string id
     * @param $(DOMnode) $element
     * @param object params: {
     *      $bottomContainer - $(DOMnode) which restricts fix from the bottom, 
     *          '<body>' by default, 
     *          'next' means the next dom sibling $element.next()
     *          'parent' means $element.parent()
     *      mode - sets the mode of adding needed white space to $bottomContainer 
     *          when $element is fixed
     *          'margin' means margin-top=$element.height() wil be added to $bottomContainer
     *          'padding' means padding-top=$element.height() wil be added to $bottomContainer
     *      offsetTop - top offset that is left before fixed element when fixed
     *      offsetBottom - bottom offset left before $bottomContainer
     *      minWidth, maxWidth - viewport width (px) boundries 
     *          is used within fixItemXY for responsive layouts
     *          0, 'infinity' by default
     *      static - 
     * } 
     */
    $.scrolly.fixItem = function(id, $element, params /*$bottomContainer, mode, offsetTop, offsetBottom*/) {
        $.scrolly.fixItemXY(id, $element, [params]);
    };

    /**
     * Fix DOM element in NON-Responsive (non viewport width dependent) layout.
     * When applied, DOMnode is fixed when TSB is within 
     * (node's top border - offsetTop) and ($bottomContainer's bottom border - offsetBottom)
     * and unfixed when TSB is out of the region
     * 
     * @param string id
     * @param $(DOMnode) $element
     * @param array params - array of objects described in fixItem()
     */
    $.scrolly.fixItemXY = function(id, $element, params /*$bottomContainer, mode, offsetTop, offsetBottom*/) {
        params = params || [];
        var rules = [];
        for (var x in params) {
            var xRange = params[x];
            var $bottomContainer = _.getItem(xRange, '$bottomContainer', $('body'));
            var mode = _.getItem(xRange, 'mode');
            var offsetTop = _.getItem(xRange, 'offsetTop', 0);
            var offsetBottom = _.getItem(xRange, 'offsetBottom', 0);
            var minWidth = _.getItem(xRange, 'minWidth', 0);
            var maxWidth = _.getItem(xRange, 'maxWidth', 'infinity');
            var isStatic = _.getItem(xRange, 'static', false);

            if ('next' === $bottomContainer) {
                mode = 'margin';
                $bottomContainer = $($element).next();
            } else if ('parent' === $bottomContainer || !$bottomContainer) {
                mode = 'padding';
                $bottomContainer = $($element).parent();
            }

            if (!isStatic) {
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
                            //                    from: offset_2,
                            //                    css: {'position': 'absolute', 'top':(offset_2+offsetTop)+'px'}
                });
            } else {
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

    /**
     * This function calculates all rules boundries when browser is resized and 
     * enters new width range. We cannot precalculate all sizes as during window 
     * resize some element are resized.
     * 
     * @param {$(DOMnode)} $element
     * @param {object} params - single rule
     * @returns {object} - recalculated rule
     */
    $.scrolly.processXYRange = function($element, params) {
        params = params || {};
        var $bottomContainer = _.getItem(params, 'bottomContainer', $('body'));
        var mode = _.getItem(params, 'mode');
        var offsetTop = _.getItem(params, 'offsetTop', 0);
        var offsetBottom = _.getItem(params, 'offsetBottom', 0);

        var itemHeight = parseInt($element.css('margin-top'))
                + $element.height()
                + parseInt($element.css('margin-bottom'));
        if ($element.css('box-sizing') === 'border-box') {
            itemHeight += parseInt($element.css('padding-top'))
                    + parseInt($element.css('padding-bottom'));
        }
        var bottomContainerHeight = parseInt($bottomContainer.css('margin-top'))
                + $bottomContainer.height()
                + parseInt($bottomContainer.css('margin-bottom'));
        if ($bottomContainer.css('box-sizing') === 'border-box') {
            bottomContainerHeight += parseInt($bottomContainer.css('padding-top'))
                    + parseInt($bottomContainer.css('padding-bottom'));
        }

        var offset_1 = Math.round($element.offset().top - parseInt($element.css('margin-top')));
        var offset_2 = Math.round($bottomContainer.offset().top + (bottomContainerHeight - itemHeight - offsetBottom));
        switch (params.alias) {
            case 'top':
                params.from = 0;
                params.to = offset_1 - offsetTop;
                params.css = {'position': 'absolute', 'top': offset_1 + 'px'};
                params.itemHeight = itemHeight;
                break;
            case 'fixed':
                params.from = offset_1 - offsetTop;
                params.to = offset_2;
                params.css = {'position': 'fixed', 'top': offsetTop + 'px'};
                params.itemHeight = itemHeight;
                break;
            case 'bottom':
                params.from = offset_2;
                params.css = {'position': 'absolute', 'top': (offset_2 + offsetTop) + 'px'};
                params.itemHeight = itemHeight;
                break;
            case 'static':
                params.from = 0;
                params.css = {'position': '', 'top': ''};
                params.itemHeight = 0;
                break;
        }

        return params;
    };

    /**
     * Heads up, this function is called on window resize. However even if window
     * has entered new width range it doesn't mean that new responsive styles were
     * allready applied. So we cannot rely on $( window ).width(). What we can rely
     * on are styles that are applied to some predefined element called 'meter'.
     * 
     * Html: (our Meter)
     * <div class="scrolly"></div>
     * 
     * CSS:
     * 
     * .scrolly{
     *      display: none;
     * }
     * 
     * media (min-device-width : 320px) and (max-device-width : 480px){
     *      .scrolly{
     *          min-width: 320px;
     *          max-width: 480px;
     *      }
     * }
     * media (min-device-width : 481px) and (max-device-width : 800px){
     *      .scrolly{
     *          min-width: 481px;
     *          max-width: 800px;
     *      }
     * }
     * 
     * JS rules:
     * 
     * {
     *      minWidth: 320,
     *      maxWidth: 480
     * },
     * {
     *      minWidth: 480,
     *      maxWidth: 800
     * }
     * 
     * @returns {Boolean}
     */
    $.scrolly.onResize = function() {
        $.scrolly.winHeight = $(window).height();
//        $.scrolly.docHeight = $(document).height();
        $.scrolly.docHeight = $.scrolly.body.height();
        $.scrolly.docMiddle = Math.floor($.scrolly.docHeight / 2);

        var needScroll = false;

        for (var id in $.scrolly.scrollLayout) {
            // cycling through all visual elements that should react 
            // to scrolling and resizing
            var item = $.scrolly.scrollLayout[id];
            for (var i in item.rules) {
                var rule = item.rules[i];
                var checkin = $.scrolly.isRuleInActiveWidthRange(rule);
                needScroll |= checkin;
                if (checkin && rule.from === undefined) {
                    $(item.element).css('position', '');
                    $(item.element).css('top', '');
                    if (rule.bottomContainer) {
                        rule.bottomContainer.css('margin-top', '');
                    }
                    // item entered new range and should adapt
                    item.rules[i] = $.scrolly.processXYRange(item.element, rule);

                }
            }
        }
        if (needScroll) {
            // dark magick here do not touch this useless string
            $.scrolly.scrollLayout = $.scrolly.scrollLayout;
            setTimeout(function() {
                $.scrolly.onScroll(true);
            }, 0);
//            $.scrolly.onScroll();
        }
        return true;
    };

    /** 
     * Helper to get progress values for onScroll handlers
     * @param {integer} offset
     * @param {integer} length
     * @returns {object} progress metrics
     */
    $.scrolly.getProgress = function(offset, length) {
        var relative = offset / length;
        return {
            offset: offset,
            length: length,
            relative: relative,
            left: length - offset,
            leftRelative: 1 - relative
        };
    };

    /**
     * Get transition value  based on start, stop and progress values
     * @param {type} start
     * @param {type} stop
     * @param {type} progress
     * @returns {Number}
     */
    $.scrolly.getTransitionValue = function(start, stop, progress) {
        return Math.round(start + (stop - start) * progress);
    };

    /**
     * Function that is called while sccrolls.
     * @param {boolean} force description
     * @returns {boolean}
     */
    $.scrolly.onScroll = function(force) {
//        var scrollPos = $(document).scrollTop(); // Y-coord that is checked against fromY & toY
        var scrollPos = $.scrolly.body.scrollTop(); // Y-coord that is checked against fromY & toY
        
        if (!force && scrollPos === $.scrolly.scrollTop) {
            return false;
        }
        var prevPos = $.scrolly.scrollTop;
        var prevDirection = $.scrolly.direction;
        $.scrolly.scrollTop = scrollPos; // Y-coord that is checked against fromY & toY
        $.scrolly.scrollBottom = scrollPos + $.scrolly.winHeight;
        $.scrolly.scrollCenter = scrollPos + Math.floor($.scrolly.winHeight / 2);
        $.scrolly.direction = scrollPos - prevPos;
        var directionChanged = !($.scrolly.direction === prevDirection
                || $.scrolly.direction < 0 && prevDirection < 0
                || $.scrolly.direction > 0 && prevDirection > 0);
        for (var id in $.scrolly.scrollLayout) {
            // cycling through all visual elements that should react 
            // to scrolling and resizing
            var item = $.scrolly.scrollLayout[id];
            var totalRules = item.rules.length;
            var checkedIn = [];
            var checkedOut = [];
            var active = [];
            
            for (var i = 0; i < totalRules; i++) {
                var rule = item.rules[i];
                var fromX = _.getItem(rule, 'minWidth', 0);
                var toX = _.getItem(rule, 'maxWidth', 'infinity');

                var container = item.container === 'self' ? item.element : item.container;
                rule.checkin = $.scrolly.isRuleActive(rule, item.element, container);
                rule.class = rule.class || 'scroll-pos-' + (rule.alias) + ' window-width-' + fromX + '-to-' + toX;
                if(rule.checkin){ 
                    active.push(i);
                    if(!rule.isActive){
                        rule.isActive = true;
                        item.element.data('rule', rule);
                        checkedIn.push(i);
                    }
                }else if(rule.isActive){
                    rule.isActive = false;
                    checkedOut.push(i);
                }
                item.rules[i] = rule;
            }

            for(var j = 0; j < checkedOut.length; j++){
                var i = checkedOut[j];
                var rule = item.rules[i];
                item.element.removeClass(rule.class);
                if (rule.onScroll) {
                    var l = rule.length || 0;
                    rule.onScroll(item.element, scrollPos > prevPos ? l : 0, l);
                }
                if (rule.onCheckOut) {
                    rule.onCheckOut(item.element);
                }
                if (rule.onTopOut && scrollPos < prevPos) {
                    rule.onTopOut(item.element);
                } else if (rule.onBottomOut && scrollPos > prevPos) {
                    rule.onBottomOut(item.element);
                }
            }

            for(var j = 0; j < checkedIn.length; j++){
                var i = checkedIn[j];
                var rule = item.rules[i];
                if (rule.css) {
                    item.element.css(rule.css);
                }
                if (rule.addClass) {
                    item.element.addClass(rule.addClass);
                }
                if (rule.removeClass) {
                    item.element.removeClass(rule.removeClass);
                }
                item.element.addClass(rule.class);

                var $bottomContainer = _.getItem(rule, 'bottomContainer');
                var mode = _.getItem(rule, 'mode');
                var itemHeight = _.getItem(rule, 'itemHeight');

                if ($bottomContainer && mode && itemHeight) {
                    $bottomContainer.css(mode + '-top', itemHeight + 'px');
                }

                if (rule.onCheckIn) {
                    rule.onCheckIn(item.element);
                }
                if (rule.onTopIn && scrollPos > prevPos) {
                    rule.onTopIn(item.element);
                } else if (rule.onBottomIn && scrollPos < prevPos) {
                    rule.onBottomIn(item.element);
                }
                rule.length = rule.checkin.length;
            }
            
            for(var j = 0; j < active.length; j++){
                var i = active[j];
                var rule = item.rules[i];
                if(rule.onScroll){
                    rule.onScroll(item.element, rule.checkin.offset, rule.checkin.length);
                }
                if (rule.onDirectionChanged) {
                    rule.onDirectionChanged(item.element, $.scrolly.direction);
                }
            }
            $.scrolly.scrollLayout[id] = item;
            
//            for (var i = 0; i < totalRules; i++) {
//                var rule = item.rules[i];
//                var fromX = _.getItem(rule, 'minWidth', 0);
//                var toX = _.getItem(rule, 'maxWidth', 'infinity');
//
//                var container = item.container === 'self' ? item.element : item.container;
//                var checkin = $.scrolly.isRuleActive(rule, container);
//                rule.class = rule.class || 'scroll-pos-' + (rule.alias) + ' window-width-' + fromX + '-to-' + toX;
//                if (checkin && !rule.isActive) {
//                    // item entered new range and should adapt
//                    if (rule.css) {
//                        item.element.css(rule.css);
//                    }
//                    if (rule.addClass) {
//                        item.element.addClass(rule.addClass);
//                    }
//                    if (rule.removeClass) {
//                        item.element.removeClass(rule.removeClass);
//                    }
//                    item.element.addClass(rule.class);
//
//                    var $bottomContainer = _.getItem(rule, 'bottomContainer');
//                    var mode = _.getItem(rule, 'mode');
//                    var itemHeight = _.getItem(rule, 'itemHeight');
//
//                    if ($bottomContainer && mode && itemHeight) {
//                        $bottomContainer.css(mode + '-top', itemHeight + 'px');
//                    }
//
//                    if (rule.onCheckIn) {
//                        rule.onCheckIn(item.element);
//                    }
//                    if (rule.onTopIn && scrollPos > prevPos) {
//                        rule.onTopIn(item.element);
//                    } else if (rule.onBottomIn && scrollPos < prevPos) {
//                        rule.onBottomIn(item.element);
//                    }
//                    rule.isActive = true;
//                    rule.length = checkin.length;
//                } else if (!checkin && rule.isActive) {
//                    item.element.removeClass(rule.class);
//                    if (rule.onScroll) {
//                        var l = rule.length || 0;
//                        rule.onScroll(item.element, scrollPos > prevPos ? l : 0, l);
//                    }
//                    if (rule.onCheckOut) {
//                        rule.onCheckOut(item.element);
//                    }
//                    if (rule.onTopOut && scrollPos < prevPos) {
//                        rule.onTopOut(item.element);
//                    } else if (rule.onBottomOut && scrollPos > prevPos) {
//                        rule.onBottomOut(item.element);
//                    }
//                    if (rule.onDirectionChanged) {
//                        rule.onDirectionChanged(item.element, $.scrolly.direction);
//                    }
//                    rule.isActive = false;
//                }
//                if (checkin && rule.onScroll) {
//                    rule.onScroll(item.element, checkin.offset, checkin.length);
//                }
//            }
        }

    };


    //Will be called once (when scrolly gets initialized).
    $.scrolly.detectCSSPrefix = function() {
        //Only relevant prefixes. May be extended.
        //Could be dangerous if there will ever be a CSS property which actually starts with "ms". Don't hope so.
        var rxPrefixes = /^(?:O|Moz|webkit|ms)|(?:-(?:o|moz|webkit|ms)-)/;

        //Detect prefix for current browser by finding the first property using a prefix.
        if (!window.getComputedStyle) {
            return;
        }

        var style = window.getComputedStyle(document.body, null);

        for (var k in style) {
            //We check the key and if the key is a number, we check the value as well, because safari's getComputedStyle returns some weird array-like thingy.
            $.scrolly.theCSSPrefix = (k.match(rxPrefixes) || (+k === k && style[k].match(rxPrefixes)));

            if ($.scrolly.theCSSPrefix) {
                break;
            }
        }

        //Did we even detect a prefix?
        if (!$.scrolly.theCSSPrefix) {
            $.scrolly.theCSSPrefix = $.scrolly.theDashedCSSPrefix = '';

            return;
        }

        $.scrolly.theCSSPrefix = $.scrolly.theCSSPrefix[0];

        //We could have detected either a dashed prefix or this camelCaseish-inconsistent stuff.
        if ($.scrolly.theCSSPrefix.slice(0, 1) === '-') {
            $.scrolly.theDashedCSSPrefix = $.scrolly.theCSSPrefix;

            //There's no logic behind these. Need a look up.
            $.scrolly.theCSSPrefix = ({
                '-webkit-': 'webkit',
                '-moz-': 'Moz',
                '-ms-': 'ms',
                '-o-': 'O'
            })[$.scrolly.theCSSPrefix];
        } else {
            $.scrolly.theDashedCSSPrefix = '-' + $.scrolly.theCSSPrefix.toLowerCase() + '-';
        }
    };
    
    $.scrolly.cssPrefix = function(key){
        return $.scrolly.theDashedCSSPrefix + key;
    };

    $.scrolly.now = Date.now || function() {
        return +new Date();
    };


    $.scrolly.getRAF = function() {
        var requestAnimFrame = window.requestAnimationFrame || window[$.scrolly.theCSSPrefix.toLowerCase() + 'RequestAnimationFrame'];

        var lastTime = $.scrolly.now();

        if (false && $.scrolly.isMobile || !requestAnimFrame) {
            requestAnimFrame = function(callback) {
                //How long did it take to render?
                var deltaTime = $.scrolly.now() - lastTime;
                var delay = Math.max(0, 1000 / 60 - deltaTime);

                return window.setTimeout(function() {
                    lastTime = $.scrolly.now();
//        $.scrolly.timesCalled++;
//        $.scrolly.x.text($.scrolly.timesCalled);
                    callback();
                }, delay);
            };
        }

        return requestAnimFrame;
    };

    $.scrolly.getCAF = function() {
        var cancelAnimFrame = window.cancelAnimationFrame || window[$.scrolly.theCSSPrefix.toLowerCase() + 'CancelAnimationFrame'];

        if ($.scrolly.isMobile || !cancelAnimFrame) {
            cancelAnimFrame = function(timeout) {
                return window.clearTimeout(timeout);
            };
        }

        return cancelAnimFrame;

    };

    $.scrolly.animLoop = function() {
        $.scrolly.onScroll();
        $.scrolly.animFrame = window.requestAnimFrame($.scrolly.animLoop);
    };

    $.scrolly.init = function(options) {
        $.extend($.scrolly.options, options);
        $.scrolly.isMobile = _.getItem($.scrolly.options, 'isMobile',
                (/Android|iPhone|iPad|iPod|BlackBerry/i).test(navigator.userAgent || navigator.vendor || window.opera));
        $.scrolly.detectCSSPrefix();
        $.scrolly.body = $($.scrolly.options.body);
        window.requestAnimFrame = $.scrolly.getRAF();
        window.cancelAnimFrame = $.scrolly.getCAF();

//        $.scrolly.x = $('<div></div>')
//                .css({
//                    position: 'fixed',
//                    top: '0',
//                    right: '0'
//                })
//                .appendTo($('body'));
        $.scrolly.timesCalled = 0;
        $(document).ready(function() {
            $(window).resize($.scrolly.onResize).resize();
//            $(document).scroll($.scrolly.onScroll).scroll(true);
            $.scrolly.body.scroll(function(){$.scrolly.onScroll(true);}).scroll();
//            $.scrolly.animLoop();
        });
    };

    $.scrolly.destroy = function() {
        window.cancelAnimFrame($.scrolly.animFrame);
    };

//    .resize($.onResize) 
//    .resize();

}(jQuery, _));


