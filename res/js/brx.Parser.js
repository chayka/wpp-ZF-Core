(function($, _, Backbone){

    /**
     * Stores parser attribute from eventual parsing by renaming it
     * @param {type} attrName
     * @returns {_L1.$.fn@call;attr@call;removeAttr}
     */
    $.fn.storeParserAttr = function(attrName){
        var re = /^data-/;
        var xAttrName = re.test(attrName)?attrName.replace(re, 'data-x-'):'x-'+attrName;
        if(this.attr(attrName)){
            this.attr(xAttrName, this.attr(attrName));
        }
        return this.removeAttr(attrName);
    }
    
    $.fn.restoreParserAttr = function(attrName){
        var re = /^data-/;
        var xAttrName = re.test(attrName)?attrName.replace(re, 'data-x-'):'x-'+attrName;
        if(this.attr(xAttrName)){
            this.attr(attrName, this.attr(xAttrName))
        }
        return this.removeAttr(xAttrName);
//        return this.attr(attrName, this.data(attrName)).removeData(attrName);
    }
    
    $.fn.storeParserAttrs = function(){
        var v = this;
        v.find('[data-attach-point]').each(function(i){$(this).storeParserAttr('data-attach-point')});
        v.find('[data-attach-widget]').each(function(i){$(this).storeParserAttr('data-attach-widget')});
        v.find('[data-attach-view]').each(function(i){$(this).storeParserAttr('data-attach-view')});
        v.find('[data-attach-event]').each(function(i){$(this).storeParserAttr('data-attach-event')});
        v.find('[data-widget]').each(function(i){$(this).storeParserAttr('data-widget')});
        v.find('[data-plugin]').each(function(i){$(this).storeParserAttr('data-plugin')});
        v.find('[data-view]').each(function(i){$(this).storeParserAttr('data-view')});
        return v;
    }
    
    $.fn.restoreParserAttrs = function(){
        this.find('[data-x-attach-point]').each(function(i){$(this).restoreParserAttr('data-attach-point')});
        this.find('[data-x-attach-widget]').each(function(i){$(this).restoreParserAttr('data-attach-widget')});
        this.find('[data-x-attach-view]').each(function(i){$(this).restoreParserAttr('data-attach-view')});
        this.find('[data-x-attach-event]').each(function(i){$(this).restoreParserAttr('data-attach-event')});
        this.find('[data-x-widget]').each(function(i){$(this).restoreParserAttr('data-widget')});
        this.find('[data-x-plugin]').each(function(i){$(this).restoreParserAttr('data-plugin')});
        this.find('[data-x-view]').each(function(i){$(this).restoreParserAttr('data-view')});
        return this;
    }
 
    _.declare('brx.Parser');
    
    $.brx = $.brx || {};
    
    $.brx.Parser = $.brx.Parser || {};

    $.brx.Parser.parseViewElement = function(view){
//            console.log('templated._parseTemplate');
        var w = view;
        view.$el.restoreParserAttrs();

        $('[data-widget]', w.el).each(function(i){
            var widgetName = $(this).attr("data-widget");
            if(widgetName){
                var widget = $.brx.Parser.createWidget(widgetName, this);
                var attachPoint = $(this).attr("data-attach-point");
                if(attachPoint){
                    w.option(attachPoint, widget.element || widget.$element);
                    $(this).storeParserAttr('data-attach-point');
                }
                var attachWidget = $(this).attr("data-attach-widget");
                if(attachWidget){
                    w.option(attachWidget, widget);
                    $(this).storeParserAttr('data-attach-widget');
                }
                $(this).storeParserAttr('data-widget');
            }
        });
        // deprecated ends

        $('[data-view], [backbone-view]', w.el).each(function(i){
            var viewName = $(this).attr("data-view") || $(this).attr("backbone-view");
            if(viewName){
                var view = $.brx.Parser.createView(viewName, this);
                var attachView = $(this).attr("data-attach-view") || $(this).attr("attachView");
                if(attachView){
                    w.option(attachView, view);
                    $(this).storeParserAttr('data-attach-view')
                            .storeParserAttr('attachView');
                }
                $(this).storeParserAttr('data-view')
                        .storeParserAttr('backbone-view');
            }
        });
        $('[data-attach-point], [attachPoint]', w.el).each(function(i){
            var attachPoint = $(this).attr("data-attach-point") || $(this).attr("attachPoint");
            w.option(attachPoint, $(this));
            $(this).storeParserAttr('data-attach-point')
                    .storeParserAttr('attachPoint');
        });
        $('[data-attach-event], [attachEvent]', w.el).each(function(j){
            var attachEvent = $(this).attr("data-attach-event") || $(this).attr("attachEvent");
            var re1 = /\s*\w+\s*:\s*[^\s,]+/g;
            var re2 = /\s*(\w+)\s*:\s*([^\s,]+)/;
            var bindings = attachEvent.match(re1);
            if(bindings && bindings.length > 0){
                for(var i = 0; i < bindings.length; i++){
                    var binding = bindings[i].match(re2);
                    var eventId = binding[1];
                    var handlerId = binding[2];
                    $(this).unbind(eventId).bind(eventId, $.proxy(w[handlerId], w));
                }
            }else{
                $(this).unbind('click').bind('click', $.proxy(w[attachEvent], w));
            }
            $(this).storeParserAttr('data-attach-event')
                    .storeParserAttr('attachEvent');
        });
//        $('[data-plugin], [plugin]', w.el).each(function(j){
//            var plugin = $(this).attr("data-plugin") || $(this).attr("plugin");
//            var path = plugin.split('.');
//            var handler = $.fn;
//            for(var i in path){
//                var key = path[i];
//                handler = handler[key];
//            }
////                console.dir({handler: handler, '$': $});
//            handler(this);
//            $(this).storeParserAttr('data-plugin')
//                    .storeParserAttr('plugin');
//        });
//            console.dir({'widget':w});
        for(var i in view.options){
            if(!$.isFunction(view.options[i])){
                var variable = view.$el.attr('data-'+i) || view.$el.attr(i);
                if(variable){
                    view.options[i] = variable;
                }
                var arr = view.$el.attr('data-array-'+i) || view.$el.attr(i+'-array');
                if(arr){
                    view.options[i] = arr.split(',');
                }
                var imported = view.$el.attr('data-import-'+i) || view.$el.attr(i+'-var');
                if(imported){
                    view.options[i] = _.getVar(imported);
                }
            }
        }

        var exported = view.$el.attr('data-export') || view.$el.attr('populate')

        if(exported){
            _.setVar(exported, view);
        }

    }
    
    /**
     * Function to create view object
     * 
     * @param string|constructor view
     * @param DOMElement element
     * @param object options
     * @returns $.brx.View
     */
    $.brx.Parser.createView = function(view, element, options){
        if(view){
            options = options || {};
            options.el = element;
            var modelVar = $(element).attr('data-import-model');
            if(modelVar){
                options.model = _.getVar(modelVar);
                $(element).storeParserAttr('data-import-model');
            }
            
            if(_.isString(view)){
                view = _.getVar(view, $);
            }
            
            return new view(options);
        }
        return null;
    };
    
    /**
     * Function to create view object on a queried element
     * $('.selector').createBackboneView = function('brx.View', {});
     * 
     * @param string|constructor view
     * @param object options
     * @returns $
     */
    $.fn.createView = function(view, options){
        $.brx.Parser.createView(view, this, options);
        return this;
    };
    
    /**
     * Parse HTML code and create views out of found DOMElements with data-view specified
     */
    $.brx.Parser.parseViews = function(){
        $('[data-view-template],[backbone-viewTemplate]').each(function(i){
            $(this).storeParserAttrs();
        });
        $('[data-view], [backbone-view]').each(function(i){
            var view = $(this).attr("data-view") || $(this).attr("backbone-view");
            $(this).createView(view);
        });
        $(document).restoreParserAttrs();
        
    };

   
    $.brx.Parser.createWidget = function(widget, element, options){
        if(widget){
            options = options || {};
            var m = widget.match(/([\w\d]+)\.([\w\d]+)/)
            if(m){
//                var namespace = m[1];
                widget = m[2];
            }
            element = element || $('<div></div>')[0];
//            var forbidden = ['id', 'class', 'style', 'href', 'src', 'widget' ];
            var r = $(element)[widget](options).data(widget);

            return r;
        }
        return null;
    }
    
    $.fn.createWidget = function(widget, options){
        $.brx.Parser.createWidget(widget, this, options);
        return this;
    }
    
    $.brx.Parser.parseWidgets = function(){
        $('[data-widget-template],[widgetTemplate]').each(function(i){
            $(this).storeParserAttrs();
        });
        $('[data-widget],[widget]').each(function(i){
            var widget = $(this).attr("data-widget") || $(this).attr("widget") ;
            $(this).createWidget(widget);
        });
        $(document).restoreParserAttrs()
        
    }
    
    $.brx.Parser.parsePlugins = function(){
        $('[data-plugin], [plugin]').each(function(j){
            var plugin = $(this).attr("data-plugin") || $(this).attr("plugin");
            var path = plugin.split('.');
            var handler = $.fn;
            for(var i in path){
                var key = path[i];
                handler = handler[key];
            }
            handler(this);
            $(this).storeParserAttr('data-plugin')
                    .storeParserAttr('plugin');
        });
        
    }

    $.brx.Parser.parse = function(){
        $.brx.Parser.parsePlugins();
        $.brx.Parser.parseViews();
        $.brx.Parser.parseWidgets();
    }
    


}(jQuery, _, Backbone));

