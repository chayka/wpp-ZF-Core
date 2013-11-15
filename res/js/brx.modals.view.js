(function($, _){
    
    _.declare('brx.ModalBox', $.brx.View, {
        options:{
            el:'<div class="brx-modal"><div class="modal_icon"></div><div class="modal_message" attachPoint="messageView"></div><div class="modal_buttons" attachPoint="buttonsBox"></div></div>',
            content: '', 
            buttons: '',
            title: '',
            modalClass: ''
        },
        
        postCreate: function(){
            this.render();
        },
        
        render: function(){
            this.$el.removeClass().addClass('brx-modal');
            this.get('messageView').html(this.get('content'));
            if(this.get('modalClass')){
                this.$el.addClass(this.get('modalClass'));
            }
            if(this.get('buttons')){
                var buttons = this.get('buttons');
                this.get('buttonsBox').html('');
                if(_.isArray(buttons)){
                    for(var i in buttons){
                        var data = buttons[i];
                        var label = _.getItem(data, 'text');
                        if(!label){
                            continue;
                        }
                        var button = $('<button></button>').html(label);
                        var callback = _.getItem(data, 'click');
                        if(callback){
                            button.bind('click', callback);
                        }
                        var buttonClass = _.getItem(data, 'class');
                        if(buttonClass){
                            button.addClass(buttonClass);
                        }
                        button.bind('click', $.proxy(this.hide, this));
                        this.get('buttonsBox').append(button);
                    }
                }else if(_.isObject(buttons)){
                    for(var label in buttons){
                        var data = buttons[label];
                        var button = $('<button></button>').html(label);
                        var callback = null;
                        var buttonClass = '';
                        
                        if(_.isFunction(data)){
                            callback = data;
                        }else if(_.isObject(data)){
                            callback = _.getItem(data, 'click');
                            buttonClass = _.getItem(data, 'class');
                        }
                        if(callback){
                            button.bind('click', callback);
                        }
                        if(buttonClass){
                            button.addClass(buttonClass);
                        }
                        button.bind('click', $.proxy(this.hide, this));
                        this.get('buttonsBox').append(button);
                    }
                    
                }
                this.get('buttonsBox').show();
            }else{
                this.get('buttonsBox').hide();
            }
        },
                
        show: function(options){
            if(options && _.isObject(options)){
                var myOptions = _.pick(options, _.keys(this.options));
                this.set(myOptions);
                this.render();
                options = _.omit(options, _.keys(this.options));
            }else{
                options = {};
            }
            this.$el.dialog(_.extend({
                autoOpen: true,
                title: this.get('title'),
                modal: true,
                minHeight: 50
            }, options));
        },
                
        hide: function(){
            this.$el.dialog('close');
        }
        
    });
    
    $.brx.modalAlert = function(message, title, modalClass){
        modalClass = modalClass || 'modal_alert';
        
        var view = new $.brx.ModalBox({
            content: message,
            title: title,
            modalClass: modalClass,
            buttons: [
                {text: 'Ok'/*, click: function() {$(this).dialog("close");}*/}
            ]
        });
        
        view.show();
        
//        this.show(message, modalClass,[
//            {text: 'Ok', click: function() {$(this).dialog("close");}}
//        ], title || '');
    };

    $.brx.modalConfirm = function(message, callback, title){
        var view = new $.brx.ModalBox({
            content: message,
            title: title || 'Подтверждение',
            modalClass: 'modal_confirm',
            buttons: [
                {text: 'Да', click: callback},
                {text: 'Нет'}
            ]
        });
        
        view.show();
//        this.show(message, 'ui-icon-alert',[
//            {text: 'Да', click: function() {$(this).dialog("close");callback();}},
//            {text: 'Нет', click: function() {$(this).dialog("close");}}
//        ], title||'Подтверждение');
    };

    $.brx.modalDialog = $.proxy(function(message, options){
        
        if(!options && _.isObject(message)){
            options = message;
            message = '';
        }
        
        var view = new $.brx.ModalBox({
            content: message
        });

        view.show(options);
//        this.showDialog(message, icon, options);
    }, this);

    $.brx.modalBox = function(message, modalClass, buttons, title){
        var view = new $.brx.ModalBox({
            content: message,
            title: title || 'Подтверждение',
            modalClass: modalClass,
            buttons: buttons
        });
        
        view.show();
//        this.show(message, icon, buttons, title);
    };
    
}(jQuery, _));

