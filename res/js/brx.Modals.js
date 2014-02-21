(function($, _, Backbone){
    

_.declare('brx.Modals', {
    
    _queue: [],
    _current: null,
    _lastIndex: 0,
    
    
    _getIndex: function(){
        if(!$.brx.Modals._lastIndex){
            $.brx.Modals._lastIndex = parseInt($.brx.Modals._getFader().css('z-index'))+10;
        }
        $.brx.Modals._lastIndex++;
        return $.brx.Modals._lastIndex;
    },
    
    /**
     * Get $('.brx-modal_fader') - fading element
     * 
     * @returns {$(DOMnode)}
     */
    _getFader: function(){
        var fader = $('.brx-modal_fader');
        if(!fader.length){
            fader = $('<div class="brx-modal_fader">')
                    .appendTo('body')
//                    .click($.brx.Modals.hide)
            ;
        }
        
        return fader;
    },
    
    
    /**
     * Creates $.brx.Modals.Window() for specified $el and stores it in 
     * $el.data('brx-modals-window') for future use. 
     * $el can be omited and proveded as options.element
     * 
     * @param {$(DOMnode)} $el
     * @param {object} options:{
     *      buttons:[]
     * }
     * @returns {$.brx.Modals.Window}
     */
    create: function($el, options){
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
        
        view.set(options);
        
        return view;
        
    },
    
    _open: function(win){
        win.$el.css('z-index', $.brx.Modals._getIndex());
        $.brx.Modals._getFader().append(win.$el);
        $('body').addClass('brx-modal-shown');
        win.show();
        
    },
    
    _close: function(win){
        win.hide();
        if(!$.brx.Modals._getFader().find('.brx-modal_window:visible').length){
            $('body').removeClass('brx-modal-shown');
        }
    },
    
    /**
     * Shows $el in a modal window.
     * Ensures $el with modal $.brx.Modals.Window.
     * Calls $.brx.Modals.create($el, options) and shows modal via queue 
     * processing.
     * 
     * @param {$(DOMnode)} $el
     * @param {object} options
     * @returns {$.brx.Modals.Window}
     */
    show: function($el, options){
        var view = $.brx.Modals.create($el, options);

        view.open();
        return view;
    },
    
    hide: function($el){
        var win = $el.data('brx-modals-window');
        if(win){
            $.brx.Modals._close(win);
        }
    },
    
    /**
     * Shows alert box.
     * 
     * @param {String} message
     * @param {String} title
     * @param {String} modalClass
     */
    alert: function(message, title, modalClass){
        modalClass = modalClass || 'modal_alert';
        $.brx.Modals.show({
            content: message,
            title: title,
            modalClass: modalClass,
            modal: false,
            buttons: [
                {text: 'Ok'/*, click: function() {$(this).dialog("close");}*/}
            ]
        });
    },

    /**
     * Shows confirm box
     * @param {type} message
     * @param {type} callback
     * @param {type} title
     * @returns {undefined}
     */
    confirm: function(message, callback, title){
        $.brx.Modals.show({
            content: message,
            title: title || 'Подтверждение',
            modalClass: 'modal_confirm',
            modal: false,
            buttons: [
                {text: 'Да', click: callback},
                {text: 'Нет'}
            ]
        });
    }

});

_.declare('brx.Modals.Window', $.brx.View, {
    options:{
        el: '<div class="brx-modal_window">'+
                '<div class="window_container" data-attach-point="container">'+
                    '<div class="container_header" data-attach-point="headerBox">'+
                        '<div class="header_title" data-attach-point="titleBox"></div>'+
                        '<div class="header_btn_close" data-attach-event="close">×</div>'+
                    '</div>'+
                    '<div class="container_content" data-attach-point="contentBox"></div>'+
                    '<div class="container_footer" data-attach-point="footerBox">'+
                        '<div class="footer_buttons" data-attach-point="buttonsBox"></div>'+
                    '</div>'+
                '</div>'+
            '</div>',
//        reusable: true,
        modal: true,
        title: '',
        content: '',
        element: null,
        buttons: [],
        css: null
    },
    
    postCreate: function(){
        this.$el.click($.proxy(this.close, this));
        this.get('container').click(function(event){
            event.stopPropagation();
        });
        $(window).resize($.proxy(this.onResize, this));
    },
    
    getFader: function(){
        return $.brx.Modals._getFader();
    },
    
    render: function(){
        this.$el.removeClass().addClass('brx-modal_window');
        if(this.get('modalClass')){
            this.get('container').addClass(this.get('modalClass'));
        }
        if(!_.empty(this.get('css'))){
            this.get('container').css(this.get('css'));
        }
        this.get('titleBox').html(this.get('title'));
        if(!_.empty(this.get('title'))){
            this.get('headerBox').show();
        }else{
            this.get('headerBox').hide();
        }
        if(this.get('content')){
            this.get('contentBox').html(this.get('content'));
        }
        if(this.get('element')){
            this.get('contentBox').append(this.get('element').show());
            console.dir({
                'width': this.get('element').width(),
                'height': this.get('element').height()
            });
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
                    button.bind('click', $.proxy(this.close, this));
//                    button.bind('click', $.brx.Modals.hide);
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
                    button.bind('click', $.proxy(this.close, this));
//                    button.bind('click', $.brx.Modals.hide);
                    this.get('buttonsBox').append(button);
                }

            }
            this.get('footerBox').show();
        }else{
            this.get('footerBox').hide();
        }
    },
    
    onResize: function(){
        if(this.$el.is(':visible')){
            var winHeight = this.get('container').outerHeight();
            var viewportHeight = $(window).height();
            var top = Math.floor((viewportHeight-winHeight)/2);
            if(top < 0){
                top = 0;
            }

            this.get('container').css('top', top+'px');
        }
    },
    
    /**
     * Shows modal window in the _procesQueue.
     * Do not use directly, use open() instead.
     * 
     * @param {object} options
     */
    show: function(options){
        this.set(options);
        this.render();
        this.$el.show();
        this.onResize();
    },
    
    /**
     * Hides modal window in the _procesQueue.
     * Do not use directly, use close() instead.
     * 
     * @param {object} options
     */
    hide: function(){
        this.$el.hide();
        if(!this.get('element')){
           this.remove(); 
        }
    },
    
    /**
     * Open (show) this modal window
     */
    open: function(){
//        $.brx.Modals._queue.push(this);
//        $.brx.Modals._processQueue();
        $.brx.Modals._open(this);
    },

    /**
     * Closes modal window.
     * Beware, it closes actually currently open modal window, could be any 
     * other one, not this one.
     * 
     * @param {DOMEvent} event
     * @returns {undefined}
     */    
    close: function(event){
        console.log('close');
        $.brx.Modals._close(this);
    }
    
});


}(jQuery, _, Backbone));


