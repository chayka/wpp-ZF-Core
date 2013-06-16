(function($){
    $.brx = $.brx || {};
    $.brx.utils = $.brx.utils || {};

    $.brx.utils.timeouts={};
    $.brx.utils.delayedCall = function(callId, timeout, call){
        var handle = $.brx.utils.timeouts[callId];
        if(handle){
            clearTimeout(handle);
        }
        $.brx.utils.timeouts[callId] = setTimeout(call, timeout);
    }

    $.brx.utils.empty = function(value){
        return 	!value
        ||	value == ""
        ||	value == "undefined"
        ||	value == null
        ||	value == "NaN"
        ||	value == 0
        ||	value == "0"
        ||	value == {}
        ||	value == []
    ;
    }

    $.brx.utils.getRadioInputValue = function(name){
        return type = parseInt($('input:radio[name='+name+']:checked').val());
    }
        
    $.brx.utils.setRadioInputValue = function(name, value){
        $('input:radio[name='+name+'][value='+value+']').attr('checked',true);            
    }

    $.brx.utils.getCheckboxInputState = function(jCheckbox, fValue, tValue){
        fValue = fValue || 0;
        tValue = jCheckbox.val() || tValue || 1;
        return jCheckbox.is(':checked')?tValue:fValue;
    }
        
    $.brx.utils.setCheckboxInputState = function(jCheckbox, state){
        jCheckbox.attr('checked', !$.brx.utils.empty(state));
    }


    $.fn.remoteAutocomplete = function(url){
        return this
        // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                $( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            source: function( request, response ) {
                $.getJSON( url, {
                    term: request.term.split( /,\s*/ ).pop()
                }, response );
            },
            search: function() {
                // custom minLength
                var term = this.value.split( /,\s*/ ).pop();
                if ( term.length < 2 ) {
                    return false;
                }
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var terms = this.value.split( /,\s*/ );
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push( ui.item.label );
                // add placeholder to get the comma-and-space at the end
                terms.push( "" );
                this.value = terms.join( ", " );
                return false;
            },
            open: function( event, ui ) {
                console.dir({'autocomplete.open':{
                        event: event, 
                        ui:ui, 
                        'this':this, 
                        autocomplete: $(this).data('autocomplete')}});
                $(this).data('autocomplete').menu.next();
//                var terms = this.value.split( /,\s*/ );
//                // remove the current input
//                terms.pop();
//                // add the selected item
//                terms.push( ui.item.label );
//                // add placeholder to get the comma-and-space at the end
//                terms.push( "" );
//                this.value = terms.join( ", " );
//                return false;
            }
        });            
    }
    
    $.brx.utils.MD5=function(s){function L(k,d){return(k<<d)|(k>>>(32-d))}function K(G,k){var I,d,F,H,x;F=(G&2147483648);H=(k&2147483648);I=(G&1073741824);d=(k&1073741824);x=(G&1073741823)+(k&1073741823);if(I&d){return(x^2147483648^F^H)}if(I|d){if(x&1073741824){return(x^3221225472^F^H)}else{return(x^1073741824^F^H)}}else{return(x^F^H)}}function r(d,F,k){return(d&F)|((~d)&k)}function q(d,F,k){return(d&k)|(F&(~k))}function p(d,F,k){return(d^F^k)}function n(d,F,k){return(F^(d|(~k)))}function u(G,F,aa,Z,k,H,I){G=K(G,K(K(r(F,aa,Z),k),I));return K(L(G,H),F)}function f(G,F,aa,Z,k,H,I){G=K(G,K(K(q(F,aa,Z),k),I));return K(L(G,H),F)}function D(G,F,aa,Z,k,H,I){G=K(G,K(K(p(F,aa,Z),k),I));return K(L(G,H),F)}function t(G,F,aa,Z,k,H,I){G=K(G,K(K(n(F,aa,Z),k),I));return K(L(G,H),F)}function e(G){var Z;var F=G.length;var x=F+8;var k=(x-(x%64))/64;var I=(k+1)*16;var aa=Array(I-1);var d=0;var H=0;while(H<F){Z=(H-(H%4))/4;d=(H%4)*8;aa[Z]=(aa[Z]|(G.charCodeAt(H)<<d));H++}Z=(H-(H%4))/4;d=(H%4)*8;aa[Z]=aa[Z]|(128<<d);aa[I-2]=F<<3;aa[I-1]=F>>>29;return aa}function B(x){var k="",F="",G,d;for(d=0;d<=3;d++){G=(x>>>(d*8))&255;F="0"+G.toString(16);k=k+F.substr(F.length-2,2)}return k}function J(k){k=k.replace(/rn/g,"n");var d="";for(var F=0;F<k.length;F++){var x=k.charCodeAt(F);if(x<128){d+=String.fromCharCode(x)}else{if((x>127)&&(x<2048)){d+=String.fromCharCode((x>>6)|192);d+=String.fromCharCode((x&63)|128)}else{d+=String.fromCharCode((x>>12)|224);d+=String.fromCharCode(((x>>6)&63)|128);d+=String.fromCharCode((x&63)|128)}}}return d}var C=Array();var P,h,E,v,g,Y,X,W,V;var S=7,Q=12,N=17,M=22;var A=5,z=9,y=14,w=20;var o=4,m=11,l=16,j=23;var U=6,T=10,R=15,O=21;s=J(s);C=e(s);Y=1732584193;X=4023233417;W=2562383102;V=271733878;for(P=0;P<C.length;P+=16){h=Y;E=X;v=W;g=V;Y=u(Y,X,W,V,C[P+0],S,3614090360);V=u(V,Y,X,W,C[P+1],Q,3905402710);W=u(W,V,Y,X,C[P+2],N,606105819);X=u(X,W,V,Y,C[P+3],M,3250441966);Y=u(Y,X,W,V,C[P+4],S,4118548399);V=u(V,Y,X,W,C[P+5],Q,1200080426);W=u(W,V,Y,X,C[P+6],N,2821735955);X=u(X,W,V,Y,C[P+7],M,4249261313);Y=u(Y,X,W,V,C[P+8],S,1770035416);V=u(V,Y,X,W,C[P+9],Q,2336552879);W=u(W,V,Y,X,C[P+10],N,4294925233);X=u(X,W,V,Y,C[P+11],M,2304563134);Y=u(Y,X,W,V,C[P+12],S,1804603682);V=u(V,Y,X,W,C[P+13],Q,4254626195);W=u(W,V,Y,X,C[P+14],N,2792965006);X=u(X,W,V,Y,C[P+15],M,1236535329);Y=f(Y,X,W,V,C[P+1],A,4129170786);V=f(V,Y,X,W,C[P+6],z,3225465664);W=f(W,V,Y,X,C[P+11],y,643717713);X=f(X,W,V,Y,C[P+0],w,3921069994);Y=f(Y,X,W,V,C[P+5],A,3593408605);V=f(V,Y,X,W,C[P+10],z,38016083);W=f(W,V,Y,X,C[P+15],y,3634488961);X=f(X,W,V,Y,C[P+4],w,3889429448);Y=f(Y,X,W,V,C[P+9],A,568446438);V=f(V,Y,X,W,C[P+14],z,3275163606);W=f(W,V,Y,X,C[P+3],y,4107603335);X=f(X,W,V,Y,C[P+8],w,1163531501);Y=f(Y,X,W,V,C[P+13],A,2850285829);V=f(V,Y,X,W,C[P+2],z,4243563512);W=f(W,V,Y,X,C[P+7],y,1735328473);X=f(X,W,V,Y,C[P+12],w,2368359562);Y=D(Y,X,W,V,C[P+5],o,4294588738);V=D(V,Y,X,W,C[P+8],m,2272392833);W=D(W,V,Y,X,C[P+11],l,1839030562);X=D(X,W,V,Y,C[P+14],j,4259657740);Y=D(Y,X,W,V,C[P+1],o,2763975236);V=D(V,Y,X,W,C[P+4],m,1272893353);W=D(W,V,Y,X,C[P+7],l,4139469664);X=D(X,W,V,Y,C[P+10],j,3200236656);Y=D(Y,X,W,V,C[P+13],o,681279174);V=D(V,Y,X,W,C[P+0],m,3936430074);W=D(W,V,Y,X,C[P+3],l,3572445317);X=D(X,W,V,Y,C[P+6],j,76029189);Y=D(Y,X,W,V,C[P+9],o,3654602809);V=D(V,Y,X,W,C[P+12],m,3873151461);W=D(W,V,Y,X,C[P+15],l,530742520);X=D(X,W,V,Y,C[P+2],j,3299628645);Y=t(Y,X,W,V,C[P+0],U,4096336452);V=t(V,Y,X,W,C[P+7],T,1126891415);W=t(W,V,Y,X,C[P+14],R,2878612391);X=t(X,W,V,Y,C[P+5],O,4237533241);Y=t(Y,X,W,V,C[P+12],U,1700485571);V=t(V,Y,X,W,C[P+3],T,2399980690);W=t(W,V,Y,X,C[P+10],R,4293915773);X=t(X,W,V,Y,C[P+1],O,2240044497);Y=t(Y,X,W,V,C[P+8],U,1873313359);V=t(V,Y,X,W,C[P+15],T,4264355552);W=t(W,V,Y,X,C[P+6],R,2734768916);X=t(X,W,V,Y,C[P+13],O,1309151649);Y=t(Y,X,W,V,C[P+4],U,4149444226);V=t(V,Y,X,W,C[P+11],T,3174756917);W=t(W,V,Y,X,C[P+2],R,718787259);X=t(X,W,V,Y,C[P+9],O,3951481745);Y=K(Y,h);X=K(X,E);W=K(W,v);V=K(V,g)}var i=B(Y)+B(X)+B(W)+B(V);return i.toLowerCase()};
    
    $.brx.utils.gravatar = function(email, size) {

        var MD5=function(s){function L(k,d){return(k<<d)|(k>>>(32-d))}function K(G,k){var I,d,F,H,x;F=(G&2147483648);H=(k&2147483648);I=(G&1073741824);d=(k&1073741824);x=(G&1073741823)+(k&1073741823);if(I&d){return(x^2147483648^F^H)}if(I|d){if(x&1073741824){return(x^3221225472^F^H)}else{return(x^1073741824^F^H)}}else{return(x^F^H)}}function r(d,F,k){return(d&F)|((~d)&k)}function q(d,F,k){return(d&k)|(F&(~k))}function p(d,F,k){return(d^F^k)}function n(d,F,k){return(F^(d|(~k)))}function u(G,F,aa,Z,k,H,I){G=K(G,K(K(r(F,aa,Z),k),I));return K(L(G,H),F)}function f(G,F,aa,Z,k,H,I){G=K(G,K(K(q(F,aa,Z),k),I));return K(L(G,H),F)}function D(G,F,aa,Z,k,H,I){G=K(G,K(K(p(F,aa,Z),k),I));return K(L(G,H),F)}function t(G,F,aa,Z,k,H,I){G=K(G,K(K(n(F,aa,Z),k),I));return K(L(G,H),F)}function e(G){var Z;var F=G.length;var x=F+8;var k=(x-(x%64))/64;var I=(k+1)*16;var aa=Array(I-1);var d=0;var H=0;while(H<F){Z=(H-(H%4))/4;d=(H%4)*8;aa[Z]=(aa[Z]|(G.charCodeAt(H)<<d));H++}Z=(H-(H%4))/4;d=(H%4)*8;aa[Z]=aa[Z]|(128<<d);aa[I-2]=F<<3;aa[I-1]=F>>>29;return aa}function B(x){var k="",F="",G,d;for(d=0;d<=3;d++){G=(x>>>(d*8))&255;F="0"+G.toString(16);k=k+F.substr(F.length-2,2)}return k}function J(k){k=k.replace(/rn/g,"n");var d="";for(var F=0;F<k.length;F++){var x=k.charCodeAt(F);if(x<128){d+=String.fromCharCode(x)}else{if((x>127)&&(x<2048)){d+=String.fromCharCode((x>>6)|192);d+=String.fromCharCode((x&63)|128)}else{d+=String.fromCharCode((x>>12)|224);d+=String.fromCharCode(((x>>6)&63)|128);d+=String.fromCharCode((x&63)|128)}}}return d}var C=Array();var P,h,E,v,g,Y,X,W,V;var S=7,Q=12,N=17,M=22;var A=5,z=9,y=14,w=20;var o=4,m=11,l=16,j=23;var U=6,T=10,R=15,O=21;s=J(s);C=e(s);Y=1732584193;X=4023233417;W=2562383102;V=271733878;for(P=0;P<C.length;P+=16){h=Y;E=X;v=W;g=V;Y=u(Y,X,W,V,C[P+0],S,3614090360);V=u(V,Y,X,W,C[P+1],Q,3905402710);W=u(W,V,Y,X,C[P+2],N,606105819);X=u(X,W,V,Y,C[P+3],M,3250441966);Y=u(Y,X,W,V,C[P+4],S,4118548399);V=u(V,Y,X,W,C[P+5],Q,1200080426);W=u(W,V,Y,X,C[P+6],N,2821735955);X=u(X,W,V,Y,C[P+7],M,4249261313);Y=u(Y,X,W,V,C[P+8],S,1770035416);V=u(V,Y,X,W,C[P+9],Q,2336552879);W=u(W,V,Y,X,C[P+10],N,4294925233);X=u(X,W,V,Y,C[P+11],M,2304563134);Y=u(Y,X,W,V,C[P+12],S,1804603682);V=u(V,Y,X,W,C[P+13],Q,4254626195);W=u(W,V,Y,X,C[P+14],N,2792965006);X=u(X,W,V,Y,C[P+15],M,1236535329);Y=f(Y,X,W,V,C[P+1],A,4129170786);V=f(V,Y,X,W,C[P+6],z,3225465664);W=f(W,V,Y,X,C[P+11],y,643717713);X=f(X,W,V,Y,C[P+0],w,3921069994);Y=f(Y,X,W,V,C[P+5],A,3593408605);V=f(V,Y,X,W,C[P+10],z,38016083);W=f(W,V,Y,X,C[P+15],y,3634488961);X=f(X,W,V,Y,C[P+4],w,3889429448);Y=f(Y,X,W,V,C[P+9],A,568446438);V=f(V,Y,X,W,C[P+14],z,3275163606);W=f(W,V,Y,X,C[P+3],y,4107603335);X=f(X,W,V,Y,C[P+8],w,1163531501);Y=f(Y,X,W,V,C[P+13],A,2850285829);V=f(V,Y,X,W,C[P+2],z,4243563512);W=f(W,V,Y,X,C[P+7],y,1735328473);X=f(X,W,V,Y,C[P+12],w,2368359562);Y=D(Y,X,W,V,C[P+5],o,4294588738);V=D(V,Y,X,W,C[P+8],m,2272392833);W=D(W,V,Y,X,C[P+11],l,1839030562);X=D(X,W,V,Y,C[P+14],j,4259657740);Y=D(Y,X,W,V,C[P+1],o,2763975236);V=D(V,Y,X,W,C[P+4],m,1272893353);W=D(W,V,Y,X,C[P+7],l,4139469664);X=D(X,W,V,Y,C[P+10],j,3200236656);Y=D(Y,X,W,V,C[P+13],o,681279174);V=D(V,Y,X,W,C[P+0],m,3936430074);W=D(W,V,Y,X,C[P+3],l,3572445317);X=D(X,W,V,Y,C[P+6],j,76029189);Y=D(Y,X,W,V,C[P+9],o,3654602809);V=D(V,Y,X,W,C[P+12],m,3873151461);W=D(W,V,Y,X,C[P+15],l,530742520);X=D(X,W,V,Y,C[P+2],j,3299628645);Y=t(Y,X,W,V,C[P+0],U,4096336452);V=t(V,Y,X,W,C[P+7],T,1126891415);W=t(W,V,Y,X,C[P+14],R,2878612391);X=t(X,W,V,Y,C[P+5],O,4237533241);Y=t(Y,X,W,V,C[P+12],U,1700485571);V=t(V,Y,X,W,C[P+3],T,2399980690);W=t(W,V,Y,X,C[P+10],R,4293915773);X=t(X,W,V,Y,C[P+1],O,2240044497);Y=t(Y,X,W,V,C[P+8],U,1873313359);V=t(V,Y,X,W,C[P+15],T,4264355552);W=t(W,V,Y,X,C[P+6],R,2734768916);X=t(X,W,V,Y,C[P+13],O,1309151649);Y=t(Y,X,W,V,C[P+4],U,4149444226);V=t(V,Y,X,W,C[P+11],T,3174756917);W=t(W,V,Y,X,C[P+2],R,718787259);X=t(X,W,V,Y,C[P+9],O,3951481745);Y=K(Y,h);X=K(X,E);W=K(W,v);V=K(V,g)}var i=B(Y)+B(X)+B(W)+B(V);return i.toLowerCase()};

        var size = size || 80;

        return 'http://www.gravatar.com/avatar/' + $.brx.utils.MD5(email) + '?s=' + size + '&d=identicon&r=G';
    }// http://www.gravatar.com/avatar/b1e2c214e5a21103e162e5dd9b8e9a24.jpg?s=16
    //    http://1.gravatar.com/avatar/b1e2c214e5a21103e162e5dd9b8e9a24?s=16&d=identicon&r=G
    //    http://1.gravatar.com/avatar/b1e2c214e5a21103e162e5dd9b8e9a24?s=16d=identicon&r=G

    $.brx.utils.objLength = function(obj){
        var len = 0;
        for(var key in obj){
            len++;
        }
        return 	len;
    }


    $.brx.utils.getItem = function(obj, key, defaultValue){
        return $.brx.utils.empty(obj[key])?defaultValue:obj[key];
    }

    $.brx.utils.passwordStrength = function(password){
        var security = 0;
        var message = '';
        if(password.length >= this.minLength){
            security++;
            if(password.match(/[A-Z]/) && password.match(/[a-z]/)){
                security++;
            }
            if(password.match(/\w/) && password.match(/\d/)){
                security++;
            }
            if(password.match(/[^\w\d]/)){
                security++;
            }
//            if(security == 1){
//                message = this.nls.msgWeakPassword;
//            }else if(security == 2){
//                message = this.nls.msgGoodPassword;
//            }else if(security >= 3){
//                message = this.nls.msgStrongPassword;
//            }
        }else{
//            message = this.nls.msgPasswordIsTooShort.replace(/_minLength_/, this.minLength);
        }

//        return {
//            'security': security, 
//            'message': message
//        };
        return security
    }

    $.brx.utils.handleResponse = function(data){
        var result = {};
        try{
            result = dojo.fromJson(response);
        }catch(e){
            result = {
                payload: '',
                code: -1,
                message: e.message
            }
        }

        return result;
    }
    
    $.brx.utils.errorHandlers = {};
    
    $.brx.utils.addErrorHandler = function(id, handler){
        $.brx.utils.errorHandlers[id] = handler;
    }
    
    $.brx.utils.handleError = function(code, message){
        var res = false;
        for(var id in $.brx.utils.errorHandlers){
            var handler = $.brx.utils.errorHandlers[id];
            res = res || handler(code, message);
        }
        
        return res;
//        switch(code){
//            case 'auth_required':
//                message = message || 
//                'Для выполнения данной операции необходимо авторизоваться на сайте';
//                window.modalDialog(message, null, {
//                    title: 'Требуется авторизация',
//                    buttons: [
//                        {text: 'Продолжить анонимно', click: function(){
//                                $(this).dialog('close');
//                        }},
//                        {text: 'Зарегистрироваться', click: function(){
//                                $(document).trigger('authForm.join');
//                                $(this).dialog('close');
//                        }},
//                        {text: 'Войти', click: function(){
//                                $(document).trigger('authForm.login');
//                                $(this).dialog('close');
//                        }}
//                    ],
//                    width: 400
//                });
//                return true;
//            case 'permission_required':
//                message = message || 
//                'У вас недостаточно прав для выполнения данной операции';
//                window.modalAlert(message);
//                return true;
//            case 'reputation_required':
//                break;
//        }
//        return false;
    }
    
    $.brx.utils.handleErrors = function(data){
        if('mass_errors' == data.code){
            for(var code in data.message){
                if($.brx.utils.handleError(code, data.message[code])){
                    delete data.message[code];
                }
            }
            return data.message;
        }

        var errors = {};
        if(!$.brx.utils.handleError(data.code, data.message)){
            errors[data.code] = data.message;
        }
        return errors;
    }

    $.brx.utils.processFail = function(response){
        if(!response.responseText){
            return 'Empty response';
        }
        var m = response.responseText?response.responseText.match(/<body[^>]*>([\s\S]*)<\/body>/m):null;
        m = m?m:response.responseText?response.responseText.match(/<br\s*\/>\s*<b>[^<]+<\/b>\:\s*(.*)<br\s*\/>/m):null;
        var message = m?m[1].trim():null;
        return message;
    }
    
    $.brx.utils.loadPage = function(loc){
        loc = window.location.href;
        var m = loc.match(/^[^#]*/);
        if(m){
            loc = m[0];
        }
        window.location = loc;
    }

    $.brx.utils.strCapitalize = function(str){
        var m = str.match(/\w[\w\d]*/g);
        for(var i in m){
            var strr = m[i];
            var re = new RegExp(strr,'gi');
            var rep = strr[0].toUpperCase() + strr.substr(1);
            str = str.replace(re, rep);
        }

        return str;
    }

    $.brx.utils.trim = function(str){
        return str.replace(/(^\s+)|(\s+$)/g, "");
    }

    $.brx.utils.truncate = function(str, length){
        var truncated = $.brx.utils.trim(str);
        if(str.length > length){
            var boundary = /\s/g;
            boundary.lastIndex = length-1;
            if(boundary.test(truncated)){
                length = boundary.lastIndex;
            }

            truncated = $.brx.utils.trim(str.substr(0, length)) + '...';
        }
        return truncated;
    }

    $.brx.utils.nl2br = function(str){
        return str.replace(/([^>])\n/g, '$1<br/>');
    }


    $.brx.utils.html = {};

    $.brx.utils.html.addSelectOption = function(domSelect, domOption){
        try{
            domSelect.add(domOption,null); // standards compliant
        }catch(ex){
            domSelect.add(domOption); // IE only
        }
    }

    $.brx.utils.html.clearSelect = function(domSelect){
        while(domSelect.options.length > 0){
            domSelect.remove(domSelect.options.length - 1);
        }		
    }

    $.brx.utils.html.renderSelect = function(domSelect, options, zeroOption){
        $.brx.utils.html.clearSelect(domSelect);
        var option = null;
        if(!$.brx.utils.empty(zeroOption)){
            option = document.createElement('option');
            option.value = $.brx.utils.getItem(zeroOption, 'id', 0);
            option.label = $.brx.utils.getItem(zeroOption, 'title', zeroOption);
            if(!$.browser.msie){
                option.innerHTML = $.brx.utils.getItem(zeroOption, 'title', zeroOption);
            }
            $.brx.utils.html.addSelectOption(domSelect, option);
        }
        if(!$.brx.utils.empty(options)){
            for(var i in options){
                var opt = options[i];
                option = document.createElement('option');
                option.value = $.brx.utils.getItem(opt, 'id', i);
                option.label = $.brx.utils.getItem(opt, 'title', opt);
                if(!$.browser.msie){
                    option.innerHTML = $.brx.utils.getItem(opt, 'title', opt);
                }
                $.brx.utils.html.addSelectOption(domSelect, option);
            }
        }

    }
    
    $.brx.utils.round = function (num, dec) {
        return Math.round(num * Math.pow(10, dec)) / Math.pow(10, dec);
    }
    
    $.brx.utils.fixDateTimezone = function (date) {
        date.setHours(date.getHours(), -date.getTimezoneOffset());
        return date;
    }    
/*
 * class FormatHelper{
    /**
     * 1            1       1
     * 100          100     100
     * 1000         1k      1k
     * 1200         1.2k    1.2k
     * 1289         1.3k    1.29k
     * 12340        12k     12.3k
     * 123456       123k    123k
     * 1234567      1.2m    1.23m
     * 12345678     12m     12.3m
     * 
     * @param int $number
     * @param type $precision 

public static function simplifiedNumber($number, $precision = 2){
        $suffixes = array('k', 'M');
        $suffix = '';
        while($number > 1000 && count($suffixes)){
            $suffix = array_shift($suffixes);
            $number /= 1000;    // 1.289 | 12.345 | 123.456
        }
        $num = $number;
        while ($precision){
            $num /= 10;
            $precision--;
            if($num < 1){
                break;
            }
        }
        
        $number = round($number, $precision);
        
        return $number.$suffix;
    }
}

 */    
    $.brx.utils.simplifiedNumber = function(number, precision, suffixes){
        if(undefined == precision){
            precision = 2;
        }
        suffixes = suffixes || ['k', 'M'];
        var suffix = '';
        while(number >= 1000 && suffixes.length){
            suffix = suffixes.shift();
            number /= 1000;
        }
        var num = number;
        while(precision){
            num /= 10;
            precision--;
            if(num < 1){
                break;
            }
        }
        
        number = $.brx.utils.round(number, precision);
        return number+suffix;
    }

})(jQuery);

