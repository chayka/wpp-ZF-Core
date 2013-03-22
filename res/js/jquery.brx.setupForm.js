(function( $ ) {
    $.widget( "brx.setupForm", $.brx.form, {
        options: { 
            elementAsTemplate: true,
            source: 'setupOptions',
            url: ''
        },
        
        
        
        // Set up the widget
        postCreate: function() {
            this.set('setupOptions', window[this.get('source')]);
            var options = this.get('setupOptions', {});
            for (var field in options){
                this.initField(field);
            }
            this.render();
            this.element.show();
            console.dir({'brx.setupForm': this});
        },

        render: function(){
            var options = this.get('setupOptions', {})
            for (var field in options){
                this.inputs(field).val(options[field]);
            }
        },
        
        saveOptions: function(event){
            event.preventDefault();
            var data = {};
            for (var field in this.get('setupOptions', {})){
                data[field] = this.inputs(field).val();
            }
            console.dir({save:data});
        
            this.clearMessage();
            this.showSpinner('Обновление данных...');
//                this.disableInputs();
            $.ajax(this.get('url'), {
                data:data,
                dataType: 'json',
                type: 'post'
            })

            .done($.proxy(function(data){
                console.dir({'data': data});
                if(0 == data.code){
                    this.set('setupOptions', data.payload);
                    this.render();
                }else{
                      this.handleAjaxErrors(data);
                }
            },this))

            .fail($.proxy(function(response){
                var message = $.brx.utils.processFail(response) 
                    || 'Ошибка обновления данных';
                this.setMessage(message, true);
            },this))

            .always($.proxy(function(){
               this.hideSpinner();
               this.showMessage();
//                    this.enableInputs();
            },this));

        },
        
        
        destroy: function(){
            
        }
    });
}(jQuery));


