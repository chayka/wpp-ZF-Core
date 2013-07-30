(function($) {

    $.declare = function(classname, parent, implementation){
        var parts = classname.split('.');
        var root = $;
        var part = ''
        for(var i = 0; i < parts.length; i++){
            part = parts[i];
            if(i == parts.length - 1){
                break;
            }
            root[part] = root[part] || {}
            root = root[part];
        }
        
        if(_.isUndefined(implementation)){
            implementation = parent;
            parent = null;
        }
        
        var options = null;
        
        if(parent){
            options = $.extend(true, {}, _.getItem(parent, 'options', {}));
            if(parent.__super__){
                options = $.extend(true, {}, options, _.getItem(parent.__super__, 'options', {}));
            }
            if(parent.prototype){
//                options = $.extend( {}, options, _.getItem(parent.prototype, 'options', {}));
            }
            options = $.extend(true, {}, options, _.getItem(implementation, 'options', {}));
            implementation.options = options;
            if(_.has(parent, 'extend') && _.isFunction(parent.extend)){
                root[part] = parent.extend(implementation);
            }else{
                root[part] = _.extend(parent, implementation);
            }
        }else{
            root[part] = implementation;
        }
        
        return;// root[part];
    }
    
    _.empty = function(value){
        return 	!value
        ||	value == ""
        ||	value == "undefined"
        ||	value == null
        ||	value == "NaN"
        ||	value == 0
        ||	value == "0"
        ||	value == {}
        ||	value == []
        ;
    };
    
    _.getItem = function(obj, key, defaultValue){
        var parts = (key+'').split('.');
        var root = obj;
        for(var i in parts){
            var part = parts[i];
            if(root[part]!=undefined){
                root = root[part];
            }else{
                return defaultValue;
            }
        }
        return root;
//        return _.empty(obj[key])?defaultValue:obj[key];
    };
    
    _.getVar = function(path, root){
        root = root || window;
        var parts = path.split('.');
        for(var x in parts){
            var part = parts[x];
            if(!parseInt(x)  && part == '$'){
                root = $;
                continue;
            }
            if(root[part]!=undefined){
                root = root[part];
            }else{
                return null;
            }
        }
        return root;
    };
    
    _.setVar = function(path, val, root){
        var parts = path.split('.');
        root = root || window;
        var part = ''
        for(var i = 0; i < parts.length; i++){
            part = parts[i];
            if(i == parts.length - 1){
                break;
            }
            root[part] = root[part] || {}
            root = root[part];
        }
        
        return root[part] = val;
    };

    $.brx = $.brx || {};

    $.brx.Model = Backbone.Model.extend({
        
        collectionFields: [],
        
        dateFields: [],
        
        strings: {},
        
        userIdAttribute: 'user_id',
        
        nlsNamespace: '',
        
        parse: function(response, options){
            response = response || {payload: null, code: 1, message: 'empty response'};
            return response.payload ? response.payload : response;
        },
        
        set: function(key, val, options){
            var attr, attrs, unset, changes, silent, changing, prev, current;
            if (key == null) return this;

            // Handle both `"key", value` and `{key: value}` -style arguments.
            if (typeof key === 'object') {
                attrs = key;
                options = val;
            } else {
                (attrs = {})[key] = val;
            }

            options || (options = {});
            
            for(var field in this.collectionFields){
                if(_.has(attrs, field)){
                    var constructor = this.collectionFields[field];
                    var models = (_.isObject(attrs[field]) && _.has(attrs[field], 'models'))?
                        attrs[field].models : attrs[field];
                    if(this.attributes && _.has(this.attributes, field) && _.has(this.attributes[field], 'models')){
                        this.attributes[field].reset(models);
                        attrs = _.omit(attrs, field);
                    }else{
                        attrs[field] = new constructor(models);
                    }
                    if(_.has(attrs, this.idAttribute)){
                        (attrs[field] || this.attributes[field]).parentId = attrs[this.idAttribute];
                    }
                }
            }
            
            for(i in this.dateFields){
                field = this.dateFields[i];
                if(_.has(attrs, field) && !_.isDate(attrs[field])){
                    if(_.isString(attrs[field]) && attrs[field]){
                        attrs[field] = Date.parse(attrs[field]);
                    }
                    if(_.isNumber(attrs[field]) && attrs[field]){
                        attrs[field] = new Date(attrs[field]);
                    }
                }
            }
            
            if(!_.isEmpty(attrs)){
                return Backbone.Model.prototype.set.apply(this, [attrs, options]);
            }
            
            return this;
        },
        
        get: function(key, defaultValue){
            if(_.isUndefined(defaultValue)){
                defaultValue = null;
            }
            
            var parts = key.split('.');
            var value = this.attributes;
            for(var i = 0; i < parts.length; i++){
                var part = parts[i];
                if(!_.has(value, part)){
                    return null;
                }
                value = value[part];
            }
            return value || defaultValue;
        },
                
        getString: function(attr, defaultValue){
            if(_.isUndefined(defaultValue)){
                defaultValue = 'unknown';
            }
            return this.get(attr)?_.getItem(this, 'strings.'+attr+'.'+this.get(attr), defaultValue):defaultValue;
        },
                
        getInt: function(key){
            return parseInt(this.get(key, 0));
        },
        
        setInt: function(key, val){
            return this.set(key, parseInt(val));
        },
                
        nls: function(key){
            if( this.nlsNamespace.length){
                key = this.nlsNamespace + '.' + key;
            }
            
            return window.nls._(key);
        },

        revert: function(){
            this.set(this.previousAttributes());
            this.trigger('revert', this);
        },
                
        canModify: function(userIdAttr){
            userIdAttr = userIdAttr || this.userIdAttribute;
            var ownerId = parseInt(this.get(userIdAttr));
            return ownerId && ownerId === parseInt($.wp.currentUser.id) 
                || 'administrator' === $.wp.currentUser.getRole();
        },
                
        showSpinner: function(message, id){
            id = id || '*';
            id = this.nlsNamespace?this.nlsNamespace+'.'+id:id;
            Backbone.Events.trigger('brx.MultiSpinner.show', message, id);
        },
                
        hideSpinner: function(id){
            id = id || '*';
            id = this.nlsNamespace?this.nlsNamespace+'.'+id:id;
            Backbone.Events.trigger('brx.MultiSpinner.hide', id);
        }
        
    });
    
    $.brx.Collection = Backbone.Collection.extend({

        nlsNamespace: '',
        
        total: 0,
                
        nls: function(key){
            if( this.nlsNamespace.length){
                key = this.nlsNamespace + '.' + key;
            }
            
            return window.nls._(key);
        },

        parse: function(response, options){
            this.total = parseInt(response.payload.total);
            return response.payload.items;
        },
                
        showSpinner: function(message, id){
            id = id || '*';
            id = this.nlsNamespace?this.nlsNamespace+'.'+id:id;
            Backbone.Events.trigger('brx.MultiSpinner.show', message, id);
        },
                
        hideSpinner: function(id){
            id = id || '*';
            id = this.nlsNamespace?this.nlsNamespace+'.'+id:id;
            Backbone.Events.trigger('brx.MultiSpinner.hide', id);
        }
    });
    

//    $.brx.View = Backbone.View.extend({
    $.declare('brx.View', Backbone.View, {
        nlsNamespace: '',
        
        options:{
            templateSelector: null
        },
        
        initialize: function(options){
            Backbone.View.prototype.initialize.apply(this, arguments);
            if(this.options.templateSelector){
                var template = $(this.options.templateSelector);
                var element = $(template.prop('tagName').toLowerCase() == 'script' ?template.html():template[0]);
                this.setElement(element);
//                this._parseElement();
            }
            this.postCreate();
        },
                
        beautifyButtons: function(){
            switch($.brx.getOption('ZfCore.uiFramework')){
                case 'bootstrap':
                    this.$('button').addClass('btn');
                    break;
                case 'jQueryUI':
                    this.$('button').button();
                    break;
            }
        },
        
        postCreate: function(){
            
        },
        
        get: function(key, defaultValue){
            if(_.isUndefined(defaultValue)){
                defaultValue = null;
            }
            
            var parts = key.split('.');
            var value = this.options;
            for(var i = 0; i < parts.length; i++){
                var part = parts[i];
                if(!_.has(value, part)){
                    return null;
                }
                value = value[part];
            }
            return value || defaultValue;
        },
                
        getInt: function(key){
            return parseInt(this.get(key, 0));
        },
        
        set: function(key, value){
            if(!key) return this;
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
                
        setInt: function(key, val){
            return this.set(key, parseInt(val));
        },
                
        option: function(key, value){
            if(undefined === value){
                return this.get(key);
            }else{
                return this.set(key, value);
            }
        },
        
        setModel: function(model){
            if(this.model){
                this.stopListening(this.model);
            }
            this.model = model;
            if(this.model){
                this.listenTo(this.model, 'change', $.proxy(this.render, this));
            }
            this.render();
        },
        
        getModel: function(){
            return this.model;
        },
        
        setElement: function(element, delegate){
            if(element == this.el && this.$el){ return }
            Backbone.View.prototype.setElement.apply(this, arguments);
            this._parseElement();
        },
        
        getTemplate: function(){
            return this.$el;
        },
        
        _parseElement: function(){
//            console.log('templated._parseTemplate');
            var w = this;
            this.$el.restoreTemplatedAttrs();
            $('[widget-template], [backbone-view-template]', w.el).storeTemplatedAttrs();
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
            $('[backbone-view]', w.el).each(function(i){
                var viewName = $(this).attr("backbone-view");
                if(viewName){
                    var view = $.brx.createBackboneView(viewName, this);
                    var attachPoint = $(this).attr("attachPoint");
                    if(attachPoint){
                        w.option(attachPoint, view.$el);
                        $(this).storeAttr('attachPoint');
                    }
                    var attachWidget = $(this).attr("attachView");
                    if(attachWidget){
                        w.option(attachWidget, view);
                        $(this).storeAttr('attachView');
                    }
                    $(this).storeAttr('backbone-view');
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
                        $(this).unbind(eventId).bind(eventId, $.proxy(w[handlerId], w));
                    }
                }else{
                    $(this).unbind('click').bind('click', $.proxy(w[attachEvent], w));
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
//                        var parts = this.$el.attr(i+'-var').split('.');
//                        var root = window;
//                        for(var x in parts){
//                            var part = parts[x];
//                            if(!parseInt(x)  && part == '$'){
//                                root = $;
//                                continue;
//                            }
//                            root = root[part];
//                        }
//                        this.options[i] = root;
                        this.options[i] = _.getVar(this.$el.attr(i+'-var'));
                    }
                }
            }
            
            if(this.$el.attr('populate')){
                _.setVar(this.$el.attr('populate'), this);
            }

        },
                
        nls: function(key){
            if( this.nlsNamespace.length){
                key = this.nlsNamespace + '.' + key;
            }
            
            return window.nls._(key);
        },

                
        showSpinner: function(message, id){
            id = id || '*';
            id = this.nlsNamespace?this.nlsNamespace+'.'+id:id;
            Backbone.Events.trigger('brx.MultiSpinner.show', message, id);
        },
                
        hideSpinner: function(id){
            id = id || '*';
            id = this.nlsNamespace?this.nlsNamespace+'.'+id:id;
            Backbone.Events.trigger('brx.MultiSpinner.hide', id);
        }
    });

    $.brx.createBackboneView = function(view, element, options){
        if(view){
            options = options || {};
            options.el = element;
            var modelVar = $(element).attr('model-var');
            if(modelVar){
//                var parts = modelVar.split('.');
//                var root = window;
//                for(var x in parts){
//                    var part = parts[x];
//                    root = root[part];
//                }
//                options.model = root;
                options.model = _.getVar(modelVar);
            }
            
            if(_.isString(view)){
//                var parts = view.split('.');
//                var root = $;
//                for(var i in parts){
//                    var part = parts[i];
//                    if(0 == i && '$'==part){
//                        continue;
//                    }
//                    root=root[part];
//                }
//                view = root;
                view = _.getVar(view, $);
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

//    $.brx.FormView = $.brx.View.extend({
    $.declare('brx.FormView', $.brx.View, {
        
        options: { 
            fields: {},
            inputs: {},
            labels: {},
            hints: {},
            buttons: {},
            message: {text: '', isError: false}
        },
        
        initialize: function(options){
            $.brx.View.prototype.initialize.apply(this, arguments);
            $(document).bind('tinyMCE.initialized', $.proxy(this.onTinyMceInit, this));
            this.initFields();
        },
        
        onTinyMceInit: function(){
            this.render();
        },

        initField: function(key, selector){
            if(selector){
                this.options.fields[key] = this.element.find(selector);
            }
            if(this.options.fields[key]){
                var field = this.options.fields[key];
                if(!this.inputs(key)){
                    this.inputs(key, field.find('input, textarea, select'));
                }
                if(!this.labels(key)){
                    this.labels(key, field.find('label:first'));
                }
                if(!this.hints(key)){
                    this.hints(key, field.find('div.form_field-tips'));
                }
//                this.options.inputs[key] = this.options.inputs[key]||field.find('input, textarea, select');
//                this.options.labels[key] = this.options.labels[key]||field.find('label:first');
//                this.options.hints[key] = this.options.hints[key]||field.find('div.form_field-tips');
            }else{
                console.warn('initField('+key+', '+selector+')');
            }
        },
                
        initFields: function(){
            for(var field in this.options.fields){
                this.initField(field);
            }
        },
        
        fields: function(name, field){
            name = 'fields.'+name;
            return this.option(name, field);
        },
        
        inputs: function(name, field){
            name = 'inputs.'+name;
            return this.option(name, field);
        },
        
        labels: function(name, field){
            name = 'labels.'+name;
            return this.option(name, field);
        },
        
        hints: function(name, field){
            name = 'hints.'+name;
            return this.option(name, field);
        },
        
        buttons: function(name, button){
            name = 'buttons.'+name;
            return this.option(name, button);
        },
        
        disableInputs: function(event){
            for(id in this.options.inputs){
                this.options.inputs[id].attr('disabled', true);
            }
        },

        enableInputs: function(event){
            for(id in this.options.inputs){
                this.options.inputs[id].removeAttr('disabled');
            }
        },
        
        disableButtons: function(event){
            for(id in this.options.buttons){
                this.options.buttons[id].attr('disabled', true);
            }
        },

        enableButtons: function(event){
            for(id in this.options.buttons){
                this.options.buttons[id].removeAttr('disabled');
            }
        },

        getRadioValue: function(fieldId, name){
            name = name || fieldId;
            var strValue = $('input:radio[name='+name+']:checked', this.options.fields[fieldId]).val();
            var intValue = parseInt(strValue);
            return isNaN(intValue)? strValue: intValue;
        },
        
        setRadioValue: function(value, fieldId, name){
            name = name || fieldId;
            $('input:radio[name='+name+'][value='+value+']', this.options.fields[fieldId]).attr('checked',true);            
        },
        
        getCheckboxState: function(inputId, fValue, tValue){
            var jCheckbox = this.options.inputs[inputId];
            fValue = fValue || 0;
            tValue = jCheckbox.val() || tValue || 1;
            return jCheckbox.is(':checked')?tValue:fValue;
        },
        
        setCheckboxState: function(inputId, state){
            var jCheckbox = this.options.inputs[inputId];
            if(state){
                jCheckbox.attr('checked', 'checked');
            }else{
                jCheckbox.removeAttr('checked');
            }
            
//            jCheckbox.attr('checked', !$.brx.utils.empty(state));
        },
        
        getTinyMceContent: function(editorId){
            if(!$.brx.utils.empty(window.tinyMCE)
            && !$.brx.utils.empty(window.tinyMCE.editors[editorId])){
                return window.tinyMCE.editors[editorId].getContent();    
            }else if(!$.brx.utils.empty(this.options.inputs[editorId])){
                return this.options.inputs[editorId].val();
            }
            return '';
        },
        
        storeTinyMce:function(editorId){
            var stored = window.tinymce.editors[editorId].iframeHTML;
        },
        
        restoreTinyMce: function(editorId){
            if(window.tinymce && window.tinymce.editors && window.tinymce.editors[editorId]){
                var m = window.tinymce.editors[editorId].iframeHTML.match(/<html[^>]*>([\s\S]*)<\/html>/mi);
                var html = m[1];
                var iframe = $(window.tinymce.editors[editorId].contentAreaContainer).find('iframe');
                iframe.attr('src', 'javascript:window.tinymce.editors.'+editorId+'.iframeHTML');
            }
        },
        
        setTinyMceContent: function(editorId, content){
            if(!$.brx.utils.empty(window.tinyMCE)
            && !$.brx.utils.empty(window.tinyMCE.editors[editorId])){
                window.tinyMCE.editors[editorId].setContent(content);            
            }else if(!$.brx.utils.empty(this.options.inputs[editorId])){
                this.options.inputs[editorId].val(content);
            }
        },
        
        getFieldVisibleValue: function(fieldId){
            if(this.inputs(fieldId).is('select')){
                return this.inputs(fieldId).find('option:selected').text();
            }
            if(this.inputs(fieldId).is('input[type=radio]')){
                return this.fields(fieldId).find('input[type=radio]:checked').next().text();
            }
            if(this.inputs(fieldId).is('input[type=checkbox]')){
                return this.fields(fieldId).find('input[type=chekbox]:checked').next().text();
            }
            if(!$.brx.utils.empty(window.tinyMCE) &&
                !$.brx.utils.empty(window.tinyMCE.editors[fieldId])){
                return this.getTinyMceContent(fieldId);
            }
            return this.inputs(fieldId).data('placeholder')?
                this.inputs(fieldId).data('placeholder').val():
                this.inputs(fieldId).val();
        },
        
        getFieldValue: function(fieldId){
            if(this.inputs(fieldId).is('select')){
                return this.inputs(fieldId).val();
            }
            if(this.inputs(fieldId).is('input[type=radio]')){
                return this.fields(fieldId).find('input[type=radio]:checked').val();
            }
            if(this.inputs(fieldId).is('input[type=checkbox]')){
                return this.fields(fieldId).find('input[type=chekbox]:checked').val();
            }
            if(!$.brx.utils.empty(window.tinyMCE) &&
                !$.brx.utils.empty(window.tinyMCE.editors[fieldId])){
                return this.getTinyMceContent(fieldId);
            }
            if(this.inputs(fieldId).data('datepicker')){
                return this.inputs(fieldId).datepicker('getDate');
            }
            return this.inputs(fieldId).data('placeholder')?
                this.inputs(fieldId).data('placeholder').val():
                this.inputs(fieldId).val();
        },

        setFieldValue: function(fieldId, value){
            if(this.inputs(fieldId).is('select')){
                return this.inputs(fieldId).val(value);
            }
            if(this.inputs(fieldId).is('input[type=radio]')){
                return this.setRadioValue(value, fieldId);
            }
            if(this.inputs(fieldId).is('input[type=checkbox]')){
                return this.setCheckboxState(fieldId, value);
            }
            if(!$.brx.utils.empty(window.tinyMCE) &&
                !$.brx.utils.empty(window.tinyMCE.editors[fieldId])){
                return this.setTinyMceContent(fieldId, value);
            }
            if(this.inputs(fieldId).data('datepicker') && value instanceof Date){
                return this.inputs(fieldId).datepicker('setDate', value);
            }
            return this.inputs(fieldId).data('placeholder')?
                this.inputs(fieldId).data('placeholder').val(value):
                this.inputs(fieldId).val(value);
        },
                
        setupFieldChecks: function(fieldId){
            this.inputs(fieldId).blur($.proxy(function(event){
                event === undefined || !this.getFieldValue(fieldId) || this.checkField(fieldId);
            }, this));
            this.inputs(fieldId).focus($.proxy(function(event){
                this.setFormFieldStateClear(fieldId);
            }, this));
        },
                
        setupFieldsChecks: function(){
            for(var fieldId in this.options.fields){
                if(this.inputs(fieldId).is('textarea, input[type=text], input[type=password], select')){
                    this.setupFieldChecks(fieldId);
                }
            }
        },
                
        createPlaceholderFromLabel: function(fieldId){
            var label = this.labels(fieldId);
            var text = label?label.text():'';
            if(text){
                text.replace(/\s*(\:|\.\.\.)\s*$/, '');
                this.inputs(fieldId).placeholder({'text': text+'...'});
            }
        },
                
        createPlaceholdersFromLabels: function(){
            for(var fieldId in this.options.fields){
                if(this.inputs(fieldId).is('textarea, input[type=text]')){
                    this.createPlaceholderFromLabel(fieldId);
                }
            }
            
        },
                
        setLabelText: function(fieldId, text){
            var label = this.labels(fieldId);
            if(label){
                label.text(text);
            }
            var placeholder = this.inputs(fieldId).data('placeholder');
            if(placeholder){
                placeholder.option('text', text).refresh();
            }
        },

        setupRemoteAutoComplete: function(jInput, url){
            jInput.remoteAutocomplete(url);
        },
                
        resizeTextarea: function(fieldId){
            setTimeout($.proxy(function(fieldId){
                var textarea = this.inputs(fieldId);
                textarea.css('height', 'auto');
                var height = textarea.css('box-sizing')==='border-box'?
                    parseInt(textarea.css('borderTopWidth')) +
                    parseInt(textarea.css('paddingTop')) +
                    textarea.prop('scrollHeight')+
                    parseInt(textarea.css('paddingBottom')) +
                    parseInt(textarea.css('borderBottomWidth')):
                    textarea.prop('scrollHeight');
                textarea.css('height', height+'px');
            },this, fieldId),0);
        },
                
        setupResizableTextarea: function(fieldId) {
            var textarea = this.inputs(fieldId);
            textarea.bind('change',  $.proxy(this.resizeTextarea, this, fieldId));
            textarea.bind('input',  $.proxy(this.resizeTextarea, this, fieldId));
            textarea.bind('cut',     $.proxy(this.resizeTextarea, this, fieldId));
            textarea.bind('paste',   $.proxy(this.resizeTextarea, this, fieldId));
            textarea.bind('drop',    $.proxy(this.resizeTextarea, this, fieldId));
            textarea.bind('keydown', $.proxy(this.resizeTextarea, this, fieldId));

//            textarea.focus();
//            textarea.select();
            this.resizeTextarea(fieldId);
        },                
        
        render: function(){
        },
        
        stored: function(id){
            window.storedInput = window.storedInput || {};
            return $.brx.utils.getItem(window.storedInput, id, null);
        },
        
        unstore: function(id){
            window.storedInput = window.storedInput || {};
            if(window.storedInput[id]){
                delete window.storedInput[id];
            }
        },
        
//        showSpinner: function(text){
//            window.showSpinner(text);
//        },
//        
//        hideSpinner: function(){
//            window.hideSpinner();
//        },
//        
        showFieldSpinner: function(fieldId, text){
            this.options.inputs[fieldId].addClass('ui-autocomplete-loading');
            this.setFormFieldStateHint(fieldId, text);
        },
        
        hideFieldSpinner: function(fieldId){
            this.options.inputs[fieldId].removeClass('ui-autocomplete-loading');
            this.options.hints[fieldId].text('');
        },
        
        setMessage: function(message, isError){
            this.option('message.text', message);
            this.option('message.isError', isError);
        },
        
        clearMessage: function(){
            this.option('message.text', '');
            this.option('message.isError', false);
        },
        
        showMessage: function(){
            if(this.option('message.text.length')){
                $.brx.modalAlert(this.options.message.text, '', this.options.message.isError? 'modal_alert':'modal_info');
            }
        },
        
        hideMessage: function(){
        },
        
        setFormFieldState: function(fieldId, message, isError){
            var field = this.options.fields[fieldId];
            var label = this.options.labels[fieldId];
            var input = this.options.inputs[fieldId];
            var tips = this.options.hints[fieldId];
            
            var state = 'clear';
            if(message){
                state = isError?'error':'hint';
            }
            switch(state){
                case 'clear':
                    input.removeClass( "ui-state-error" );
                    tips.text('').removeClass( "ui-state-highlight");
                    break;
                case 'error' :
                    input.addClass( "ui-state-error" );
                    tips.html( message ).addClass('form_field-tips_error');
                    break;
                case 'hint' :
                    input.removeClass( "ui-state-error" );
                    tips.html( message ).removeClass('form_field-tips_error');
                    break;
            }
        },
        
        setFormFieldStateClear: function(fieldId){
            this.setFormFieldState(fieldId);
        },
        
        setFormFieldStateError: function(fieldId, message){
            this.setFormFieldState(fieldId, message, true);
        },
        
        setFormFieldStateHint: function(fieldId, message){
            this.setFormFieldState(fieldId, message, false);
        },
        
        clearField: function(fieldId){
            this.setFormFieldState(fieldId);
            this.options.inputs[fieldId].val('');
        },
        
        clearForm: function(){
            for(var id in this.options.fields){
                this.clearField(id);
            }
        },
        
        checkLength: function ( fieldId, fieldLabel, min, max, messageTemplate ) {
            fieldLabel = fieldLabel || this.labels(fieldId).text().replace(':', '');
            min = min || this.fields(fieldId).attr('check-length-min') || 0;
            max = max || this.fields(fieldId).attr('check-length-max') || 0;
            messageTemplate = messageTemplate 
                || this.fields(fieldId).attr('check-length-message')
                || "Длина значения должна быть от <%= min %> до <%= max => символов."
            var message = _.template(messageTemplate, {min: min, max: max, label: fieldLabel}); 
            var input = this.options.inputs[fieldId];
            if ( max && input.val().length > max || min && input.val().length < min ) {
                this.setFormFieldStateError(fieldId, message);
                return false;
            } else {
                return true;
            }
        },

        checkRegexp: function ( fieldName, regexp, errorMessage ) {
            regexp = regexp 
                || new RegExp(this.fields(fieldName).attr('check-regexp-pattern'),this.fields(fieldName).attr('check-regexp-modifiers'));
            errorMessage = errorMessage 
                || this.fields(fieldName).attr('check-regexp-message')
                || 'Неверный формат';
            var input = this.options.inputs[fieldName];
            if ( !( regexp.test( input.val() ) ) ) {
                this.setFormFieldStateError(fieldName, errorMessage );
                return false;
            } else {
                return true;
            }
        },
    
        checkEmail: function (fieldName, errorMessage){
            errorMessage = errorMessage 
                || this.fields(fieldName).attr('check-email')
                || "(образец: master@potroydom.by)";
            return this.checkRegexp( fieldName, /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i, errorMessage );
        },
		
        checkPassword: function (fieldId, min, errorTemplate){
            min = min || this.fields(fieldName).attr('check-pass-min') || 0;
            errorTemplate = errorTemplate 
                || this.fields(fieldName).attr('check-pass-message')
                || "Пароль должен быть не короче <%= min %> символов."
            var errorMessage = _.template(errorTemplate, {min: min});
            var input = this.options.inputs[fieldId];
            if ( input.val().length < min ) {
                this.setFormFieldStateError(fieldId, errorMessage);
                return false;
            } else {
                return true;
            }
        },
        
        checkPasswordMatch: function(pass1FieldId, pass2FieldId, errorMessage){
            pass2FieldId = pass2FieldId
                || this.fields(pass1FieldId).attr('check-pass-match-id');

            errorMessage = errorMessage 
                || this.fields(pass1FieldId).attr('check-pass-match-message')
                || "Введенные пароли отличаются";
            var inputPass1 = this.options.inputs[pass1FieldId];
            var inputPass2 = this.options.inputs[pass2FieldId];
            if ( inputPass1.val() != inputPass2.val()) {
//                this.setFormFieldStateError(pass1FieldId, errorMessage );
                this.setFormFieldStateError(pass2FieldId, errorMessage );
                return false;
            } else {
                return true;
            }
        },
        
        checkRequired: function ( fieldName, errorMessage ) {
            errorMessage = errorMessage 
                || this.fields(fieldName).attr('check-required')
                || "Необходимо заполнить";
            var input = this.options.inputs[fieldName];
            var val = this.getFieldValue(fieldName);
            if ( !val ) {
                this.setFormFieldStateError(fieldName, errorMessage );
                return false;
            } else {
                this.setFormFieldStateClear(fieldName);
                return true;
            }
        },

        checkRequiredTinyMce: function ( fieldName, errorMessage ) {
            errorMessage = errorMessage 
                || this.fields(fieldName).attr('check-required')
                || "Необходимо заполнить";
            var value = this.getTinyMceContent(fieldName).replace(/<[^>]+>/g).trim();
            var valid = value.length > 0;
            if ( !valid ) {
                this.setFormFieldStateError(fieldName, errorMessage );
                return false;
            } else {
                return true;
            }
        },
        
        checkField: function(fieldId){
            valid = true;
            this.setFormFieldStateClear(fieldId);
            value = this.getFieldValue(fieldId);
            if(valid && this.fields(fieldId).attr('check-required')){
                valid = valid && this.checkRequired(fieldId);
            }else if(!value){
                return true;
            }
            if(valid && this.fields(fieldId).attr('check-email')){
                valid = valid && this.checkEmail(fieldId);
            }
            if(valid && this.fields(fieldId).attr('check-regexp-pattern')){
                valid = valid && this.checkRegexp(fieldId);
            }
            if(valid && this.fields(fieldId).attr('check-length-min') || this.fields(fieldId).attr('check-length-max')){
                valid = valid && this.checkLength(fieldId);
            }
            if(valid && this.fields(fieldId).attr('check-pass-min')){
                valid = valid && this.checkPassword(fieldId);
            }
            if(valid && this.fields(fieldId).attr('check-pass-match-id')){
                valid = valid && this.checkPasswordMatch(fieldId);
            }
            return valid
        },

        checkFields: function(fieldIds){
            fieldIds = fieldIds || _.keys(this.options.inputs);
            var valid = true;
            for(var i in fieldIds){
                var fieldId = fieldIds[i];
                valid = this.checkField(fieldId) && valid;
            }
            
            return valid;
        },

        handleAjaxErrors: function(data){
            this.processErrors($.brx.utils.handleErrors(data));
        },
        
        processErrors: function(errors){
            console.dir({'processErrors': errors});
            for(key in errors){
                var errorMessage = errors[key];
                var field = 'messageBox';
                if(!$.brx.utils.empty(this.options.fields[key])){
                    field = key;
                }
                if(field!='messageBox'){
                    this.setFormFieldStateError(field, errorMessage );
                }else{
                    this.setMessage(errorMessage, true);
                }
            }
        }
        
    });
    
    
    if(!_.getItem(Backbone, 'history')){
        Backbone.history = {};
    }
    
    Backbone.history.matchUrl = function(url){
        url = this.getFragment(url);
        var matched = _.any(this.handlers, function(handler) {
            if (handler.route.test(url)) {
//                handler.callback(url);
                return true;
            }
        });
        return matched;
    };
    
    $.brx.options = {};
    
    $.brx.getOption = function(key, def){
        return _.getItem($.brx.options, key, def);
    };

}(jQuery));

