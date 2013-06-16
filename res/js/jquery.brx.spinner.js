(function( $ ) {
//    $.widgetTemplated( "brx.spinner", $.ui.templated, {
    $.widget( "brx.spinner", $.ui.templated, {
 
//        _parentPrototype: $.ui.templated.prototype,
        
        // These options will be used as defaults
        options: { 
            templatePath: null,
            template: '<div class="brx_spinner"></div>',
            message: null
        },
        
        message: null,
        
        // Set up the widget
        _create: function() {
            console.log('brx.spinner._create');
//            this.option()
            this._initTemplated();
//            $('a').hide();
            return this;
        },
        
 
        // Use the _setOption method to respond to changes to options
        _setOption: function( key, value ) {
//            console.dir({'brx.loginForm._setOption':{key:key, value:value}});
            $.ui.templated.prototype._setOption.apply( this, arguments );
            switch( key ) {
                case "message":
                    this.option('template').html(value);
                    this.options.message = value;
                    break;
            }
 
            // In jQuery UI 1.8, you have to manually invoke the _setOption method from the base widget
            // In jQuery UI 1.9 and above, you use the _super method instead
//            this._super( "_setOption", key, value );
        },
        
        postCreate: function(){
            this.hide();
//            this.template.dialog({
//                autoOpen: false,
//                height: 400,
//                width: 600,
//                modal: true,
//                close: $.proxy(this.clearForm, this)
//            });
//            this.template.show();
//            $('a').click($.proxy(function(event){
//                event.preventDefault();
//                this.template.dialog('open');
//            }, this));
//            
        },
        
        refresh: function(){
            
        },
        
        show: function(message, callback){
            this.option('message', message);
            this.option('template').show('fade', {}, 300, callback);
        },
        
        hide: function(callback){
            console.dir({'spinner.template': this.option('template')});
            this.option('template').hide('fade', {}, 300, callback);
        },

        // Use the destroy method to clean up any modifications your widget has made to the DOM
        destroy: function() {
            // In jQuery UI 1.8, you must invoke the destroy method from the base widget
//            $.Widget.prototype.destroy.call( this );
        // In jQuery UI 1.9 and above, you would define _destroy instead of destroy and not call the base method
        }
    });
    
}( jQuery ) );

(function($){
//    $.widgetTemplated( "brx.generalSpinner", $.brx.spinner, {
    $.widget( "brx.generalSpinner", $.brx.spinner, {
 
        postCreate: function(){
            this.option('template');
//                .css('display', 'inline');
//            this.hide();
            this.element.dialog({
               autoOpen:false,
               closeOnEscape: false,
               draggable:false,
               resizable:false,
               modal:true,
               height: 75,
               open: function(event, ui) {console.dir({'ui': ui, 'event': $(event.target).prev()});$(".ui-dialog-titlebar-close", $(event.target).prev()).hide();$(event.target).prev().hide();}
            });
            window.showSpinner = $.proxy(function(text){this.show(text)}, this);
            window.hideSpinner = $.proxy(function(){this.hide()}, this);
            this.hide();
        },
        
        show: function(message, callback){
            this.element.dialog('open');
            this.element.dialog({'height': 55});
            this.option('message', message);
            this.option('template').show('fade', {}, 300, callback);
        },
        
        hide: function(callback){
            this.element.dialog('close');
            this.option('template').hide('fade', {}, 300, callback);
        },

        // Use the destroy method to clean up any modifications your widget has made to the DOM
        destroy: function() {
            // In jQuery UI 1.8, you must invoke the destroy method from the base widget
//            $.Widget.prototype.destroy.call( this );
        // In jQuery UI 1.9 and above, you would define _destroy instead of destroy and not call the base method
        }
    });
//    var div = document.createElement('div');
//    div.attributes['id']='general_spinner';
//    $('body').append(div);
//    $(div).attr('id','general_spinner').hide();
//    $(div).createTemplatedWidget('brx.spinner');
//    window.spinner = $(div).data('spinner_general_spinner');
//    spinner.template
//        .css('display', 'inline');
//    spinner.hide();
//    spinner.element.dialog({
//       autoOpen:false,
//       closeOnEscape: false,
//       draggable:false,
//       resizable:false,
//       modal:true,
//       height: 75,
//       open: function(event, ui) {console.dir({'ui': ui, 'event': $(event.target).prev()});$(".ui-dialog-titlebar-close", $(event.target).prev()).hide();$(event.target).prev().hide();}
//    });
//    spinner.element.show();
//    window.showSpinner = function(text){
//        window.spinner.element.dialog('open');
//        window.spinner.element.dialog({'height': 35});
//        window.spinner.show(text);
//    };
//        
//    window.hideSpinner = function(){
//        window.spinner.element.dialog('close');
//        window.spinner.hide();
//    };
//
}(jQuery));