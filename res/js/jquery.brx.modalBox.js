(function( $ ) {
    
//    $.widgetTemplated( "brx.modalBox", $.ui.templated, {
    $.widget( "brx.modalBox", $.ui.templated, {
 
//        _parentPrototype: $.ui.templated.prototype,
        
        // These options will be used as defaults
        options: { 
            create: true,
            template: '<div class="bem-modal_box"><span attachPoint="icon" class="ui-icon" style="float:left;"></span><div attachPoint="messageBox" class="message_box" style="padding-left: 24px;"></div></div>',
            message: null,
            iconClass: 'ui-icon-error'
        },
        
        
        _create: function() {
            console.log('brx.modalBox._create');
//            this.option()
            this._initTemplated();
//            $('a').hide();
            return this;
        },
        
        getMessageBox: function(){
            return this.option('messageBox');
        },
 
        getIcon: function(){
            return this.option('icon');
        },
        
        renderIcon: function(iconClass){
            this.getIcon().removeClass(this.options.iconClass);
            if(iconClass){
                this.getIcon().addClass(iconClass);
                this.getIcon().show();
            }else{
                this.getIcon().hide();
            }
        },
 
        // Use the _setOption method to respond to changes to options
        _setOption: function( key, value ) {
//            console.dir({'brx.modal._setOption':{key:key, value:value}});
//            $.ui.templated.prototype._setOption.apply( this, arguments );
            switch( key ) {
                case "message":
                    if(this.options.messageBox){
                        this.getMessageBox().html(value);
                    }
                    break;
                case "messageBox":
                    if(this.options.message){
                        this.options.messageBox = value;
                        this.getMessageBox().html(this.options.message);
                    }
                    break;
                case "iconClass":
                    if(this.getIcon()){
                        this.renderIcon(value);
                    }
                    break;
                case "icon":
                    if(this.options.iconClass){
                        this.options.icon = value;
                        this.renderIcon(this.options.iconClass);
                    }
                    break;
            }
            $.ui.templated.prototype._setOption.apply( this, arguments );
//            this._super( "_setOption", key, value );
        },
        
        postCreate: function(){
            console.info('modalBox.postCreate');
            this.hide();
            this.getTemplate().dialog({
                autoOpen: false,
//                height: 400,
//                width: 600,
                modal: true,
//                dialogClass: '',
//                close: $.proxy(this.clearForm, this)
                open: function(){
                    console.dir({modalbox: this, b:$(this).nextAll('.ui-dialog-buttonpane').find('button')});
                    $('button', this.parentNode).addClass('btn');
                }
            });
//            console.dir({'modal parent': this.getTemplate().parent()});
            this.getTemplate().show();
//            this.getTemplate().parent().find('[type]').css({'color': 'red'});
            
            window.modalAlert = $.proxy(function(message, title, icon){
                icon = icon || 'ui-icon-alert';
                this.show(message, icon,[
                    {text: 'Ok', click: function() {$(this).dialog("close");}}
                ], title);
            }, this);

            window.modalConfirm = $.proxy(function(message, callback, title){
                this.show(message, 'ui-icon-alert',[
                    {text: 'Да', click: function() {$(this).dialog("close");callback();}},
                    {text: 'Нет', click: function() {$(this).dialog("close");}}
                ], title||'Подтверждение');
            }, this);

            window.modalDialog = $.proxy(function(message, icon, options){
                this.showDialog(message, icon, options);
            }, this);

            window.modalBox = $.proxy(function(message, icon, buttons, title){
                this.show(message, icon, buttons, title);
            }, this);
        },
        
        refresh: function(){
            
        },
        
        showDialog: function(message, icon, options){
            this.option('message', message);
            this.option('iconClass', icon);
            this.getTemplate().dialog(options);
            $('button', this.parentNode).addClass('btn');
            this.getTemplate().dialog('open');
        },
        
        show: function(message, icon, buttons, title){
//            title = title || '';
//            this.option('message', message);
//            this.option('icon', icon);
//            this.getTemplate().dialog('option', {'buttons': buttons, 'title': title});
//            this.getTemplate().dialog('open');
            this.showDialog(message, icon, {'buttons': buttons, 'title': title});
        },
        
        hide: function(callback){
            this.getTemplate().hide('fade', {}, 300, callback);
        },

        // Use the destroy method to clean up any modifications your widget has made to the DOM
        destroy: function() {
            // In jQuery UI 1.8, you must invoke the destroy method from the base widget
//            $.Widget.prototype.destroy.call( this );
        // In jQuery UI 1.9 and above, you would define _destroy instead of destroy and not call the base method
        }
    });
    
}( jQuery ) );
