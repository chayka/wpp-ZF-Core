(function($,_){
    _.declare('brx.ajax',{
        errorHandlers: {},
    
        addErrorHandler: function(id, handler){
            $.brx.ajax.errorHandlers[id] = handler;
        },
    
        handleError: function(code, message){
            var res = false;
            for(var id in $.brx.ajax.errorHandlers){
                var handler = $.brx.ajax.errorHandlers[id];
                res = res || handler(code, message);
            }

            return res;
        },
    
        handleErrors: function(data){
            if(!data){
                return {'empty_response': 'Empty response'};
            }
            if('mass_errors' == data.code){
                for(var code in data.message){
                    if($.brx.ajax.handleError(code, data.message[code])){
                        delete data.message[code];
                    }
                }
                return data.message;
            }

            var errors = {};
            if(!$.brx.ajax.handleError(data.code, data.message)){
                errors[data.code] = data.message;
            }
            return errors;
        },

        processFail: function(response){
            if(!response.responseText){
                return 'Empty response';
            }
            var m = response.responseText?response.responseText.match(/<body[^>]*>([\s\S]*)<\/body>/m):null;
            m = m?m:response.responseText?response.responseText.match(/<br\s*\/>\s*<b>[^<]+<\/b>\:\s*(.*)<br\s*\/>/m):null;
            var message = m?m[1].trim():null;
            return message;
        }
        
    });
}(jQuery, _));

