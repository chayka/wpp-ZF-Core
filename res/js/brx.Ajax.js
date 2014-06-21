(function($,_){
    _.declare('brx.Ajax',{
        errorHandlers: {},
    
        addErrorHandler: function(id, handler){
            $.brx.Ajax.errorHandlers[id] = handler;
        },
    
        handleError: function(code, message, payload){
            var res = false;
            for(var id in $.brx.Ajax.errorHandlers){
                var handler = $.brx.Ajax.errorHandlers[id];
                res = res || handler(code, message, payload);
            }

            return res;
        },
    
        handleErrors: function(data){
            if(!data){
                return {'empty_response': 'Empty response'};
            }
            if('mass_errors' == data.code){
                for(var code in data.message){
                    if($.brx.Ajax.handleError(code, data.message[code], data.payload)){
                        delete data.message[code];
                    }
                }
                return data.message;
            }

            var errors = {};
            if(!$.brx.Ajax.handleError(data.code, data.message, data.payload)){
//                console.dir({'handleErrors.data': data});
                errors[data.code] = data.message;
            }
            return errors;
        },

        processResponse: function(response, defaultMessage){
            var message = defaultMessage || null;
            var code = 1;
            if(!response || !response.length){
                message = 'Empty response';
            }else if(!_.isUndefined(response.payload)){
                return response;
            }else{
                try{
                    var response = $.parseJSON(response);
                    return response
                }catch(e){
                    var m = response.match(/<body[^>]*>([\s\S]*)<\/body>/m);
                    m = m?m:response.match(/<br\s*\/>\s*<b>[^<]+<\/b>\:\s*(.*)<br\s*\/>/m);
//                    ||response.match(/<br\s*\/>\s*<font[^>]*>(.*)<\/font>\s*$/m)
//                    ||response.match(/<br\s*\/>\s*<font[^>]*>(.*)$/m);
                    message = m?m[1].trim():defaultMessage;
                }
            }
            return {code: code, message: message, payload: null};
        },
        
        detectArgXHR: function(args){
            for(var i=0; i<args.length; i++){
                var arg = args[i];
                if(arg && !_.isUndefined(arg.responseText)){
                    return arg;
                }
                if(arg && !_.isUndefined(arg.xhr) && !_.isUndefined(arg.xhr.responseText)){
                    return arg.xhr;
                }
            }
            
            return null;
        },
        
        detectArgModel: function(args){
            for(var i=0; i<args.length; i++){
                var arg = args[i];
                if(arg && !_.isUndefined(arg.id)){
                    return arg;
                }
            }
            
            return null;
        },
        
        detectArgData: function(args){
            for(var i=0; i<args.length; i++){
                var arg = args[i];
                if(arg && !_.isUndefined(arg.payload)){
                    return arg;
                }
            }
            
            return null;
        },
        
        prepare: function(options){
            var defaults = {
            };
            
            options = options || {};
            
            options = _.extend(defaults, options);
            console.dir({this: this});
            var spinner = _.getItem(options, 'spinner', null);
            var spinnerId = _.getItem(options, 'spinnerId');
            var spinnerFieldId = _.getItem(options, 'spinnerFieldId');
            var spinnerMessage = _.getItem(options, 'spinnerMessage', 'Processing...');
            var errorMessage = _.getItem(options, 'errorMessage', 'Operation failed');
            var showMessage = _.getItem(options, 'showMessage', true);
            var start = _.getItem(options, 'start', true);
            
            var send = _.getItem(options, 'send');
            var success = _.getItem(options, 'success');
            var error = _.getItem(options, 'error');
            var complete = _.getItem(options, 'complete');
            
            options = _.omit(options, [
                'spinner', 'spinnerId', 'spinnerMessage', 'spinnerFieldId', 
                'errorMessage'
            ]);
            
            var sendHandler = $.proxy(function(){
                var result = true;
                if(_.isFunction(send)){
                    result = send.apply(this);
                }
                if(result){
                    if(spinner!==false){
                        if(spinner){
                            spinner.show(spinnerMessage);
                        }else if(spinnerFieldId && _.isFunction(this.showFieldSpinner)){
                            this.showFieldSpinner(spinnerFieldId, spinnerMessage);
                        }else if(_.isFunction(this.showSpinner)){
                            this.showSpinner(spinnerMessage, spinnerId);
                        }
                    }
                    if(_.isFunction(this.clearMessage)){
                        this.clearMessage();
                    }
                }
                return result;
            }, this);
            
            if(start){
                if(!sendHandler.apply(this)){
                    return false;
                };
            }else{
                options.send = sendHandler;
            }
            
            var errorHandler = $.proxy(function(/*jqXHR, textStatus, errorThrown*/){
                var xhr = $.brx.Ajax.detectArgXHR(arguments);
//                for(var i=0; i<arguments.length; i++){
//                    var arg = arguments[i];
//                    if(arg && !_.isUndefined(arg.responseText)){
//                        xhr = arg;
//                        break;
//                    }
//                }
                console.dir({errorHandler: arguments});
                var data = $.brx.Ajax.processResponse(xhr.responseText, errorMessage);
                var errors = $.brx.Ajax.handleErrors(data);
                var message = errorMessage;
                for(var i in errors){
                    message = errors[i] || errorMessage;
                    break;
                }
                if(_.isFunction(this.processErrors)){
                    this.processErrors(errors);
                }else if(_.isFunction(this.setMessage)){
                    this.setMessage(message, true);   
                }else{
//                    $.brx.modalAlert(message, '', 'modal_alert');
                    $.brx.Modals.alert(message);
                }
                
                if(_.isFunction(error)){
                    error.apply(this, arguments);
                }                
            }, this);
            
//            options.error = error?[error, errorHandler]:errorHandler;
            options.error = errorHandler;
            
            var successHandler = $.proxy(function(/*jqXHR, textStatus, errorThrown*/){
                var data = $.brx.Ajax.detectArgData(arguments);

                if(data && data.code === 0 && _.isFunction(success)){
                    success.apply(this, arguments);
                }else{
                    errorHandler.apply(this, arguments);
                }
            }, this);
            
            options.success = successHandler;
            
            
            var completeHandler = $.proxy(function(jqXHR, textStatus){
                if(spinner!==false){
                    if(spinner){
                        spinner.hide();
                    }else if(spinnerFieldId && _.isFunction(this.hideFieldSpinner)){
                        this.hideFieldSpinner(spinnerFieldId);
                    }else if(_.isFunction(this.hideSpinner)){
                        this.hideSpinner(spinnerId);
                    }
                }
                if(showMessage && _.isFunction(this.showMessage)){
                    this.showMessage();                
                }
                if(complete && _.isFunction(complete)){
                    complete.apply(this, arguments);
                }
            }, this);

//            options.complete = complete?[complete, completeHandler]:completeHandler;
            options.complete = completeHandler;
            
            return options;
            
        },        
        
        request: function(url, options){
            
            var defaults = {
                dataType: 'json',
                type: 'post'
            };
            
            options = $.brx.Ajax.prepare.apply(this, [options]);
            
            options = _.extend(defaults, options);
            
            
            return $.ajax(url, options);
        },
        
        setupIframeForm: function(options){
            var response,
                returnReponse,
                form,
                status = true,
                iframe,
                iframeID;
            
            var defaults = {
                iframeID: 'iframe-post-form', // Iframe ID.
                send: function() {
                    return true;
                }, // Form onsubmit.
                success: function(response){
                    
                },
                error: function(response){
                    
                },
                complete: function(response) {
                }    // After response from the server has been received.
            };

            options = $.brx.Ajax.prepare.apply(this, [_.extend(options, {start: false})]);

            options = $.extend(defaults, options);

            form = _.getItem(options, 'form');
            
            if(!form){ 
                return null;
            }
            
            iframeID = _.getItem(options, 'iframeID');
            var send = _.getItem(options, 'send');
            var success = _.getItem(options, 'success');
            var error = _.getItem(options, 'error');
            var complete = _.getItem(options, 'complete');

            // Add the iframe.
            iframe = $('#' + iframeID);
            if (!iframe.length)
            {
                iframe = $('<iframe id="' + iframeID + '" name="' + iframeID + '" style="display:none" />');
                $('body').append(iframe);
            }

            // Target the iframe.
            form.attr('target', iframeID);


            // Submit listener.
            form.submit(function()
            {
                // If status is false then abort.
                status = send.apply(this);

                if (status === false)
                {
                    return status;
                }


                iframe.load(function()
                {
                    response = iframe.contents().find('body');
                    console.dir({'post form': {
                            arguments: arguments,
                            response: response
                        }});
                    var responseText = response.html();
                    var data = $.brx.Ajax.processResponse(responseText);
                    var xhr = {responseText: responseText};

                    if(data.code===0){
                        success.apply(null, [data, xhr]);
                    }else{
                        error.apply(null, [xhr]);
                    }
                    complete.apply(null, [xhr])
//                    success.apply(xhr);
                    iframe.unbind('load');


                    setTimeout(function()
                    {
                        response.html('');
                    }, 1);
                });
            });
        }


    });
}(jQuery, _));

