(function($, _, Backbone){
    
_.declare('brx.Modals.Window', $.brx.View, {
    options:{
        el: '<div class="brx-modal_window">'+
                '<div class="container_header">'+
                    '<div class="header_title" data-attach-point="titleBox"></div>'+
                    '<div class="header_btn_close" data-attach-event="close"></div>'+
                '</div>'+
                '<div class="container_content" data-attach-point="contentBox"></div>'+
                '<div class="container_footer" data-attach-point="footerBox">'+
                    '<div class="footer_buttons" data-attach-point="buttonsBox"></div>'+
                '</div>'+
            '</div>',
//        reusable: true,
        title: '',
        content: '',
        element: null,
        buttons: []
    },
    
    postCreate: function(){
        this.$el.click(function(event){
            event.preventDefault();
            return false;
        });
//        var fader = this.getFader();
//        this.$el.appendTo(fader);
    },
    
    getFader: function(){
        return $.brx.Modals._getFader();
    },
    
    render: function(){
        this.$el.removeClass().addClass('brx-modal_window');
        if(this.get('modalClass')){
            this.$el.addClass(this.get('modalClass'));
        }
        this.get('titleBox').html(this.get('title'));
        this.get('contentBox').html(this.get('content'));
        if(this.get('element')){
            this.get('contentBox').html('').append(this.get('element'));
        }
        if(this.get('buttons') && this.get('buttons').length){
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
//                    button.bind('click', $.proxy(this.hide, this));
                    button.bind('click', $.brx.Modals.hide);
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
//                    button.bind('click', $.proxy(this.hide, this));
                    button.bind('click', $.brx.Modals.hide);
                    this.get('buttonsBox').append(button);
                }

            }
            this.get('buttonsBox').show();
        }else{
            this.get('buttonsBox').hide();
        }
    },
    
    show: function(options){
        this.set(options);
        this.render();
        this.$el.show();
    },
    
    hide: function(){
        this.$el.hide();
        if(!this.get('element')){
           this.remove(); 
        }
    },
    
    close: function(){
        $.brx.Modals.hide();
    }
    
});

_.declare('brx.Modals', {
    
    _queue: [],
    _current: null,
    
    _getFader: function(){
        var fader = $('.brx-modal_fader');
        if(!fader.length){
            fader = $('<div class="brx-modal_fader">').appendTo('body');
        }
        fader.click($.brx.Modals.hide);
        return fader;
    },
    
    _processQueue: function(){
        if(!$.brx.Modals._current){
            var next = $.brx.Modals._queue().shift();
            if(next){
                $.brx.Modals._current = next;
                $.brx.Modals._getFader().append(next.$el);
                next.show();
                $('body').addClass('brx-modal-shown');
            }else{
                $('body').removeClass('brx-modal-shown');
            }
        }else{
            
        }
    },
    
    show: function($el, options){
        
        if(_.empty(options)){
            // in case second param is omitted, options will be in the first param
            if(!_.isFunction($el) && !_.isElement($el) && !_.isString($el) && _.isObject($el)){
                // $el is options
                options = $el;
                $el = _.getItem(options, 'element');
            }else{
                // $el is $el
                options = {};
            }
        }
        
        if(_.isElement($el) || _.isString($el)){
            $el = $($el);
        }
        
        var view = null;
        
        if($el){
            view = $el.data('brx-modals-window') || new $.brx.Modals.Window();
            $el.data('brx-modals-window', view);
            options.element = $el;
        }else{
            view = new $.brx.Modals.Window();
        }
        
        $.brx.Modals._queue.push(view);
        
        $.brx.Modals._processQueue();
//        view.show(options);
    },
    
    hide: function(){
        var current = $.brx.Modals._current;
        if(current){
            current.hide();
            $.brx.Modals._current = null;
        }
        
        $.brx.Modals._processQueue();
    },
    
    alert: function(message, title){
        
        $.brx.Modals.show({
            content: message,
            title: title,
            modalClass: 'modal_alert',
            buttons: [
                {text: 'Ok'/*, click: function() {$(this).dialog("close");}*/}
            ]
        });
    },

    confirm: function(message, callback, title){
        $.brx.Modals.show({
            content: message,
            title: title || 'Подтверждение',
            modalClass: 'modal_confirm',
            buttons: [
                {text: 'Да', click: callback},
                {text: 'Нет'}
            ]
        });
    }

//    dialog: function(message, options){
//        
//        if(!options && _.isObject(message)){
//            options = message;
//            message = '';
//        }
//        
//        var view = new $.brx.Modals.Dialog({
//            content: message
//        });
//
//        view.show(options);
//    },
//
//    box: function(message, modalClass, buttons, title){
//        var view = new $.brx.Modals.Dialog({
//            content: message,
//            title: title || 'Подтверждение',
//            modalClass: modalClass,
//            buttons: buttons
//        });
//        
//        view.show();
//    }
});


}(jQuery, _, Backbone));


