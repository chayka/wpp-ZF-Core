(function($) {
    $.brx = $.brx||{};
    
    $.brx.View = Backbone.View.extend({
        
        options:{
            templateSelector: null
        },
        
        initialize: function(options){
            Backbone.View.prototype.initialize.apply(this, arguments);
            if(this.options.templateSelector){
                var template = $(this.options.templateSelector);
                var element = $(template.html());
                this.setElement(element);
            }
            this._parseElement();
            this.postCreate();
        },
        
        postCreate: function(){
            
        },
        
        get: function(key, defaultValue){
            defaultValue = defaultValue || null;
            
            var parts = key.split('.');
            var value = this.options;
            for(var i = 0; i < parts.length; i++){
                var part = parts[i];
                if(!_.has(value, part)){
                    return key;
                }
                value = value[part];
            }
            return value || defaultValue;
        },
        
        set: function(key, value){
            var parts = key.split('.');
            var root = this.options;
            for(var i = 0; i < parts.length - 1; i++){
                var part = parts[i];
                if(!_.has(root, part)){
                    root[part] = {};
                }
                root = root[part];
            }
            root[_.last(parts)] = value;
            return this;
            
        },
        
        option: function(key, value){
            if(undefined == value){
                return this.get(key);
            }else{
                return this.set(key, value);
            }
        },
        
        setElement: function(element, delegate){
            Backbone.View.prototype.setElement.apply(this, arguments);
            this._parseElement();
        },
        
        getTemplate: function(){
            return $el;
        },
        
        _parseElement: function(){
//            console.log('templated._parseTemplate');
            var w = this;
            $('[widget]', w.el).each(function(i){
                var widgetName = $(this).attr("widget");
                if(widgetName){
                    var widget = $.ui.createTemplatedWidget(widgetName, this);
                    var attachPoint = $(this).attr("attachPoint");
                    if(attachPoint){
                        w.option(attachPoint, widget.element || widget.$element);
                        $(this).storeAttr('attachPoint');
                    }
                    var attachWidget = $(this).attr("attachWidget");
                    if(attachWidget){
                        w.option(attachWidget, widget);
                        $(this).storeAttr('attachWidget');
                    }
                    $(this).storeAttr('widget');
                }
            });
            $('[attachPoint]', w.el).each(function(i){
                var attachPoint = $(this).attr("attachPoint");
//                console.dir({'attachPoint':{point: attachPoint, widget: w, element: $(this)}});
                w.option(attachPoint, $(this));
                $(this).storeAttr('attachPoint');
            })
            $('[attachEvent]', w.el).each(function(j){
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
            $('[plugin]', w.el).each(function(j){
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
            for(var i in this.options){
                if(!$.isFunction(this.options[i])){
                    if( this.$el.attr(i)){
                        this.options[i] = this.$el.attr(i);
                    }
                    if( this.$el.attr(i+'-array')){
                        this.options[i] = this.$el.attr(i+'-array').split(',');
                    }
                    if( this.$el.attr(i+'-var')){
                        var parts = this.$el.attr(i+'-var').split('.');
                        var root = window;
                        for(var x in parts){
                            var part = parts[x];
                            root = root[part];
                        }
                        this.options[i] = root;
                    }
                }
            }

        }
    });

    $.brx.createBackboneView = function(view, element, options){
        if(view){
            options = options || {};
            options.el = element;
            
            if(_.isString(view)){
                var parts = view.split('.');
                var root = $;
                for(var i in parts){
                    var part = parts[i];
                    if(0 == i && '$'==part){
                        continue;
                    }
                    root=root[part];
                }
                view = root;
            }
            
            return new view(options);
        }
        return null;
    }
    
    $.fn.createBackboneView = function(view, options){
        $.brx.createBackboneView(view, this, options);
        return this;
    }
    
    $.brx.parseBackboneViews = function(){
        $('[backbone-view]').each(function(i){
            var view = $(this).attr("backbone-view");
            $(this).createBackboneView(view);
        });
        $(document).restoreTemplatedAttrs()
        
    }


}(jQuery));

