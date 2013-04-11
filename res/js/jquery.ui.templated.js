(function($){

$.resourceLoader = {
        resources: {},
        loading: {},
        basePath: '',
        
        get: function(name, getter){
            if(this.resources[name]){
                return this.resources[name];
            }else{
                if(this.loading[name]){
                    if($.inArray(getter, this.loading[name]) < 0){
                        this.loading[name].push(getter);
                    }
                }else{
                    this.loading[name] = [getter];
                    $.get(this.basePath + name, $.proxy(function(data, xhr){
//                        console.dir({data:data, xhr: xhr, that: this, name: name});
                        for(var i in this.loading[name]){
                            var handler = this.loading[name][i];
//                            console.dir({handler: handler});
                            handler(data);
                        }
                        delete this.loading[name];
//                        getter(data)
                    }, this))
                }
                return false;
            }
        },
        
        getTemplate: function(name, getter){
            if(this.resources[name]){
                return this.resources[name];
            }else{
                if(this.loading[name]){
                    if($.inArray(getter, this.loading[name]) < 0){
                        this.loading[name].push(getter);
                    }
                }else{
                    this.loading[name] = [getter];
                    $.get(this.basePath+name, $.proxy(function(data, xhr){
//                        console.dir({data:data, xhr: xhr, that: this, name: name});
                        for(var i in this.loading[name]){
                            var handler = this.loading[name][i];
//                            console.dir({handler: handler});
                            handler.option('template', data);
                        }
                        delete this.loading[name];
//                        getter(data)
                    }, this))
                }
                return false;
            }
        }
    };

    
})(jQuery);

(function( $ ) {
    $.ui = $.ui || {};
    $.fn.storeAttr = function(attrName){
        return this.attr('x-'+attrName, this.attr(attrName)).removeAttr(attrName);
//        return this.data(attrName, this.attr(attrName)).removeAttr(attrName);
    }
    
    $.fn.restoreAttr = function(attrName){
        return this.attr(attrName, this.attr('x-'+attrName)).removeAttr('x-'+attrName);
//        return this.attr(attrName, this.data(attrName)).removeData(attrName);
    }
    
    $.fn.storeTemplatedAttrs = function(){
        var v = this;
        v.find('[attachPoint]').each(function(i){$(this).storeAttr('attachPoint')});
        v.find('[attachWidget]').each(function(i){$(this).storeAttr('attachWidget')});
        v.find('[attachEvent]').each(function(i){$(this).storeAttr('attachEvent')});
        v.find('[widget]').each(function(i){$(this).storeAttr('widget')});
        v.find('[plugin]').each(function(i){$(this).storeAttr('plugin')});
        return v;
    }
    
    $.fn.restoreTemplatedAttrs = function(){
        this.find('[x-attachPoint]').each(function(i){$(this).restoreAttr('attachPoint')});
        this.find('[x-attachWidget]').each(function(i){$(this).restoreAttr('attachWidget')});
        this.find('[x-attachEvent]').each(function(i){$(this).restoreAttr('attachEvent')});
        this.find('[x-widget]').each(function(i){$(this).restoreAttr('widget')});
        this.find('[x-plugin]').each(function(i){$(this).restoreAttr('plugin')});
        return this;
    }
    
    $.ui.createTemplatedWidget = function(widget, element, options){
        if(widget){
            options = options || {};
//            $(element).restoreTemplatedAttrs();
            var m = widget.match(/([\w\d]+)\.([\w\d]+)/)
            if(m){
                var namespace = m[1];
                widget = m[2];
            }
            element = element || $('<div></div>')[0];
            var forbidden = ['id', 'class', 'style', 'href', 'src', 'widget' ];
//            for(var i in element.attributes){
//                var value = element.attributes[i].value;
//                var key = element.attributes[i].name;
//                if($.inArray(key, forbidden)<0){
//                    options[key] = value;
//                }
//            }
            var r = $(element)[widget](options).data(widget);
//                console.dir({'$.ui.createTemplatedWidget': r});
            return r;
        }
        return null;
    }
    
    $.fn.createTemplatedWidget = function(widget, options){
        $.ui.createTemplatedWidget(widget, this, options);
        return this;
    }
    
    $.ui.parseWidgets = function(basePath){
        if(window.tb_init){
            $('[widget=button]').removeAttr('widget').button();
        }
        if(basePath){
            $.resourceLoader.basePath = basePath;
        }
        $('[widgetTemplate]').each(function(i){
            $(this).storeTemplatedAttrs();
        });
        $('[widget]').each(function(i){
            var widget = $(this).attr("widget");
            $(this).createTemplatedWidget(widget);
        });
        $(document).restoreTemplatedAttrs()
        
    }
})(jQuery);

(function( $ ) {

//    $.widgetTemplated( "ui.templated", {
    $.widget( "ui.templated", {
 
        // These options will be used as defaults
        options: { 
            templatePath: null,//"widget.tpl.html",
            template: null,
            templateSelector: null,
            elementAsTemplate: false
        },
        
//        _parentPrototype: $.Widget.prototype,
 
        // Set up the widget
        _create: function() {
//            console.log('templated._create');
//            this.option()
            for(var i in this.options){
                if(!$.isFunction(this.options[i])){
                    if( this.element.attr(i)){
                        this.options[i] = this.element.attr(i);
                    }
                    if( this.element.attr(i+'-array')){
                        this.options[i] = this.element.attr(i+'-array').split(',');
                    }
                }
            }
            this._initTemplated();
        },
        
        _initTemplated: function(){
            this._setOptions(this.options);
        },
 
        // Use the _setOption method to respond to changes to options
        _setOption: function( key, value ) {
//            console.dir({'templated._setOption':{key:key, value:value}});
            switch( key ) {
                case "templatePath":
                    this.options.templatePath = value;
                    if(value){
                        var template = $.resourceLoader.getTemplate(value, this);
                        if(template){
                            this.setTemplate(template);
                        }
                    }
                    break;
                case "template":
                    if(!this.options.elementAsTemplate){
                        this.setTemplate(value);
                    }
                    break;
                case "elementAsTemplate":
                    if(value){
                        this.setTemplate(this.element);
                    }
                    break;
                case "templateSelector":
                    if(value){
                        var template = $(value).clone();
                        if(template.length){
                            this.setTemplate(template);
                        }
                    }
                    break;
                default:
                    this.options[key] = value;
            }
 
            // In jQuery UI 1.8, you have to manually invoke the _setOption method from the base widget
//            $.Widget.prototype._setOption.apply( this, arguments );
            // In jQuery UI 1.9 and above, you use the _super method instead
//            this._super( "_setOption", key, value );
        },
        
        set: function(option, value){
            return this.option(option, value);
        },
        
        get: function(option, defaultValue){
            return this.option(option);
            var value = this.option(option);
            return defaultValue != undefined && !value?defaultValue:this.option(option);
        },
        
        getTemplate: function(){
            return this.option('template');
        },
        
        setTemplate: function(template){
//            console.dir({'templated._setTemplate': this});
            this.options.template = $(template);
            if (template != this.element){
                this._replaceWithTemplate();
            }
            if(template){
                this._parseTemplate();
                this.postCreate();
            }
            this.refresh();
        },
        
        _replaceWithTemplate: function(){
            this.element.empty();
            $(this.options.template).appendTo(this.element.context);
//            console.dir({'element': this.element});
            this.options.template = $(this.element.context).children().first();
//            $(this.element.context).remove();
        },
 
        _parseTemplate: function(){
//            console.log('templated._parseTemplate');
            var w = this;
            $('[widget]', w.option('template')).each(function(i){
                var widgetName = $(this).attr("widget");
                if(widgetName){
                    var widget = $.ui.createTemplatedWidget(widgetName, this);
                    var attachPoint = $(this).attr("attachPoint");
                    if(attachPoint){
                        w.option(attachPoint, widget.element);
//                        console.dir({'[widget]attachPoint':{event: attachPoint, widget: w, element: $(this)}});
                        $(this).storeAttr('attachPoint');
                    }
                    var attachWidget = $(this).attr("attachWidget");
                    if(attachWidget){
                        w.option(attachWidget, widget);
//                        console.dir({'[widget]attachWidget':{event: attachWidget, widget: w, element: $(this)}});
                        $(this).storeAttr('attachWidget');
                    }
                    $(this).storeAttr('widget');
                }
            });
            $('[attachPoint]', w.option('template')).each(function(i){
                var attachPoint = $(this).attr("attachPoint");
//                console.dir({'attachPoint':{point: attachPoint, widget: w, element: $(this)}});
                w.option(attachPoint, $(this));
                $(this).storeAttr('attachPoint');
            })
            $('[attachEvent]', w.option('template')).each(function(i){
                var attachEvent = $(this).attr("attachEvent");
                var re1 = /\s*\w+\s*:\s*[^\s,]+/g;
                var re2 = /\s*(\w+)\s*:\s*([^\s,]+)/;
                var bindings = attachEvent.match(re1);
                if(bindings && bindings.length > 0){
                    for(var i = 0; i < bindings.length; i++){
//                        console.dir({'binding':bindings[i].match(re2)});
                        var binding = bindings[i].match(re2);
                        var eventId = binding[1];
                        var handlerId = binding[2];
                        $(this).bind(eventId, $.proxy(w[handlerId], w));
                    }
                }else{
                    $(this).bind('click', $.proxy(w[attachEvent], w));
                }
//                console.dir({'attachEvent':{event: attachEvent, widget: w, element: $(this)}});
                $(this).storeAttr('attachEvent');
            })
            $('[plugin]', w.option('template')).each(function(i){
                var plugin = $(this).attr("plugin");
//                console.log(plugin);
                var path = plugin.split('.');
                var handler = $.fn;
                for(var i in path){
                    var key = path[i];
                    handler = handler[key];
                }
//                console.dir({handler: handler, '$': $});
                handler(this);
                $(this).storeAttr('plugin');
            })
//            console.dir({'widget':w});
        },

        postCreate: function(){
            
        },
        
        refresh: function(){
            
        },
        
        
        // Use the destroy method to clean up any modifications your widget has made to the DOM
        destroy: function() {
            // In jQuery UI 1.8, you must invoke the destroy method from the base widget
//            $.Widget.prototype.destroy.call( this );
        // In jQuery UI 1.9 and above, you would define _destroy instead of destroy and not call the base method
        }
    });
}( jQuery ) );



