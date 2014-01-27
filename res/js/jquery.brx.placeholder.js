(function( $, _ ) {
    $.widget( "brx.placeholder", {
 
//        _parentPrototype: $.ui.templated.prototype,
        
        // These options will be used as defaults
        options: { 
            text: 'Введите значение...',
            value: null,
            type: null
        },
        
        set: function(option, value){
            return this.option(option, value);
        },
        
        get: function(option){
            return this.option(option);
        },
        
        // Set up the widget
        _create: function() {
            console.log('brx.placeholder._create');
            this.set('value', this.element.val());
            this.set('type', this.element.attr('type'));
            this.set('text', this.element.attr('placeholder')||this.get('text'));
            this.set('isPassword', this.get('type')=='password');
            this.set('fakePassword', null);
            if(this.get('isPassword')){
                var fakeHTML = $(this.element[0].outerHTML
                    .replace(/type=(['"])?password\1/gi, 'type=$1text$1')
                    .replace(/(name|id)=(['"])?[\w\d\s]*\1/gi, ''));
                fakeHTML.val(this.get('text')).addClass('placeholder')
                    .focus($.proxy(function() {
                        this.get('fakePassword').hide();
                        this.element.show().focus();
                    }, this));
                this.set('fakePassword', fakeHTML);
                this.element
                    .blur($.proxy(function(){
                        if(_.empty(this.element[0].value)){
                            this.element.after(this.get('fakePassword').show()).hide();
                        }
                    }, this))
    //                .keypress($.proxy(this.setValue, this))
                    .change($.proxy(this.setValue, this));
            }else{
                this.element
                    .blur($.proxy(function(){
                        if(_.empty(this.element[0].value)||this.element[0].value == this.get('text')){
                            this.element[0].value = this.get('text');
                            this.element.addClass('placeholder');
                        }else if(!this.isPlaceholderValue()){
                            this.element.removeClass('placeholder');
                        }
                    }, this))
                    .focus($.proxy(function(){
                        if(this.isPlaceholderValue()){
                            this.element[0].value = '';
                            this.element.removeClass('placeholder');
                        }
                    }, this))
    //                .keypress($.proxy(this.setValue, this))
                    .change($.proxy(this.setValue, this));
            }
            this.refresh();    
            this.element.val = $.proxy(function(value){
                if(value==undefined){
                    return this.isPlaceholderValue()?'':this.element[0].value;
                }else{
                    this.element[0].value = value;
                    return this.element;
                }
            }, this);
            if(this.get('isPassword')){
                this.element.addClass = $.proxy(function(classname){
                    $(this.element[0]).addClass(classname);
                    this.get('fakePassword').addClass(classname);
                }, this);
                this.element.removeClass = $.proxy(function(classname){
                    $(this.element[0]).removeClass(classname);
                    this.get('fakePassword').removeClass(classname);
                }, this);
            }
//            this.element.blur();
        },
        
        val: function(value){
            if(value==undefined){
                return this.isPlaceholderValue()?'':this.element[0].value;
            }else{
                this.element[0].value = value?value:this.get('text');
                this.setValue();
                return this.element;
            }
        },
         
        // Use the _setOption method to respond to changes to options
//        _setOption: function( key, value ) {
//            $.ui.templated.prototype._setOption.apply( this, arguments );
//            switch( key ) {
////                case "message":
////                    this.getTemplate().html(value);
////                    this.options.message = value;
////                    break;
//            }
// 
//        },
        
        isPlaceholderValue: function(){
            return this.get('text') == this.element[0].value;
        },
        
        setValue: function(event){
//            console.log('placeholder.setValue');
            this.refresh();
        },
                
        refresh: function(){
            if(this.element.is(':focus')){
                this.element.focus();
            }else{
                this.element.blur();
            }
        },
        
        value: function(){
        },
        
        // Use the destroy method to clean up any modifications your widget has made to the DOM
        destroy: function() {
            // In jQuery UI 1.8, you must invoke the destroy method from the base widget
//            $.Widget.prototype.destroy.call( this );
        // In jQuery UI 1.9 and above, you would define _destroy instead of destroy and not call the base method
        }
    });
}( jQuery, _ ) );

