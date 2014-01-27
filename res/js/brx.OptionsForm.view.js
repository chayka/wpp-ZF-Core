(function($, _){
    _.declare('brx.OptionsForm', $.brx.FormView, {
        options: { 
            scope: '',
            prefix: '',
            options: null
        },
        
        
        
        // Set up the widget
        postCreate: function() {
            console.log('OptionsForm.postCreate');
            this.initFields();
            this.setupFieldsChecks();
            if(!_.empty(this.get('options'))){
                this.renderOptions();
            }else{
                this.loadOptions();
            }
        },

        render: function(){
//            var options = this.get('setupOptions', {})
        },
                
        renderOptions: function(options){
            options = options || this.get('options');
            for (var field in options){
                this.setFieldValue(field, options[field]);
            }
        },
        
        loadOptions: function(){
                var data = {
                scope: this.get('scope'),
                prefix: this.get('prefix'),
                options: _.keys(this.options.fields).join(' ')
            };
            console.dir({load:data});
        
            this.ajax('/api/options/get/', {
                data:data,
                spinnerMessage: 'Загрузка данных...',
                errorMessage: 'Ошибка загрузки данных',
                success: $.proxy(function(data){
                    console.dir({'data': data});
                    if(0 === data.code){
                        this.set('options', data.payload);

                        this.renderOptions(data.payload);
                        this.$el.show();
                    }
                },this)
            });
        
//            this.showSpinner('Загрузка данных...');
//            $.ajax('/api/options/get/', {
//                data:data,
//                dataType: 'json',
//                type: 'post'
//            })
//
//            .done($.proxy(function(data){
//                console.dir({'data': data});
//                if(0 === data.code){
//                    this.set('options', data.payload);
//                    
//                    this.renderOptions(data.payload);
//                    this.$el.show();
//                }else{
//                      this.handleAjaxErrors(data);
//                }
//            },this))
//
//            .fail($.proxy(function(response){
//                var message = $.brx.utils.processFail(response) 
//                    || 'Ошибка загрузки данных';
//                this.setMessage(message, true);
//            },this))
//
//            .always($.proxy(function(){
//               this.hideSpinner();
//               this.showMessage();
//            },this));
        },
        
        saveOptions: function(event){
            event.preventDefault();
            var data = {
                scope: this.get('scope'),
                prefix: this.get('prefix')
            };
            for (var field in this.options.fields){
                data[field] = this.getFieldValue(field);
            }
            console.dir({save:data});

            this.ajax('/api/options/set/', {
                data:data,
                spinnerMessage: 'Обновление данных...',
                errorMessage: 'Ошибка обновления данных',
                success: $.proxy(function(data){
                    console.dir({'data': data});
                    if(0 === data.code){
                        this.set('options', data.payload);
                        this.renderOptions(data.payload);
                    }
                },this)
            });
//            this.showSpinner('Обновление данных...');
//            $.ajax('/api/options/set/', {
//                data:data,
//                dataType: 'json',
//                type: 'post'
//            })
//
//            .done($.proxy(function(data){
//                console.dir({'data': data});
//                if(0 === data.code){
//                    this.set('options', data.payload);
//                    this.renderOptions(data.payload);
//                }else{
//                      this.handleAjaxErrors(data);
//                }
//            },this))
//
//            .fail($.proxy(function(response){
//                var message = $.brx.utils.processFail(response) 
//                    || 'Ошибка обновления данных';
//                this.setMessage(message, true);
//            },this))
//
//            .always($.proxy(function(){
//               this.hideSpinner();
//               this.showMessage();
//            },this));

        }

    });
}(jQuery, _));

