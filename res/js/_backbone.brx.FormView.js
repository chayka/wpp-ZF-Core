(function($) {
    
    $.brx.FormView = $.brx.View.extend({
        
        options: { 
            fields: {},
            inputs: {},
            labels: {},
            hints: {},
            buttons: {},
            message: {text: '', isError: false}
        },
        
        initialize: function(options){
            $.brx.View.prototype.initialize.apply(this, arguments);
            $(document).bind('tinyMCE.initialized', $.proxy(this.onTinyMceInit, this));
        },
        
        onTinyMceInit: function(){
            this.render();
        },

        initField: function(key, selector){
            if(selector){
                this.options.fields[key] = this.element.find(selector);
            }
            if(this.options.fields[key]){
                var field = this.options.fields[key];
                this.options.inputs[key] = this.options.inputs[key]||field.find('input, textarea, select');
                this.options.labels[key] = this.options.labels[key]||field.find('label:first');
                this.options.hints[key] = this.options.hints[key]||field.find('div.form_field-tips');
            }else{
                console.warn('initField('+key+', '+selector+')');
            }
        },
        
        fields: function(name, field){
            name = 'fields.'+name;
            return this.option(name, field);
        },
        
        inputs: function(name, field){
            name = 'inputs.'+name;
            return this.option(name, field);
        },
        
        labels: function(name, field){
            name = 'labels.'+name;
            return this.option(name, field);
        },
        
        hints: function(name, field){
            name = 'hints.'+name;
            return this.option(name, field);
        },
        
        buttons: function(name, button){
            name = 'buttons.'+name;
            return this.option(name, button);
        },
        
        disableInputs: function(event){
            for(id in this.options.inputs){
                this.options.inputs[id].attr('disabled', true);
            }
        },

        enableInputs: function(event){
            for(id in this.options.inputs){
                this.options.inputs[id].removeAttr('disabled');
            }
        },
        
        disableButtons: function(event){
            for(id in this.options.buttons){
                this.options.buttons[id].attr('disabled', true);
            }
        },

        enableButtons: function(event){
            for(id in this.options.buttons){
                this.options.buttons[id].removeAttr('disabled');
            }
        },

        getRadioValue: function(fieldId, name){
            name = name || fieldId;
            var strValue = $('input:radio[name='+name+']:checked', this.options.fields[fieldId]).val();
            var intValue = parseInt(strValue);
            return isNaN(intValue)? strValue: intValue;
        },
        
        setRadioValue: function(value, fieldId, name){
            name = name || fieldId;
            $('input:radio[name='+name+'][value='+value+']', this.options.fields[fieldId]).attr('checked',true);            
        },
        
        getCheckboxState: function(inputId, fValue, tValue){
            var jCheckbox = this.options.inputs[inputId];
            fValue = fValue || 0;
            tValue = jCheckbox.val() || tValue || 1;
            return jCheckbox.is(':checked')?tValue:fValue;
        },
        
        setCheckboxState: function(inputId, state){
            var jCheckbox = this.options.inputs[inputId];
            jCheckbox.attr('checked', !$.brx.utils.empty(state));
        },
        
        getTinyMceContent: function(editorId){
            if(!$.brx.utils.empty(window.tinyMCE)
            && !$.brx.utils.empty(window.tinyMCE.editors[editorId])){
                return window.tinyMCE.editors[editorId].getContent();    
            }else if(!$.brx.utils.empty(this.options.inputs[editorId])){
                return this.options.inputs[editorId].val();
            }
            return '';
        },
        
        storeTinyMce:function(editorId){
            var stored = window.tinymce.editors[editorId].iframeHTML;
        },
        
        restoreTinyMce: function(editorId){
            if(window.tinymce && window.tinymce.editors && window.tinymce.editors[editorId]){
                var m = window.tinymce.editors[editorId].iframeHTML.match(/<html[^>]*>([\s\S]*)<\/html>/mi);
                var html = m[1];
                var iframe = $(window.tinymce.editors[editorId].contentAreaContainer).find('iframe');
                iframe.attr('src', 'javascript:window.tinymce.editors.'+editorId+'.iframeHTML');
            }
        },
        
        setTinyMceContent: function(editorId, content){
            if(!$.brx.utils.empty(window.tinyMCE)
            && !$.brx.utils.empty(window.tinyMCE.editors[editorId])){
                window.tinyMCE.editors[editorId].setContent(content);            
            }else if(!$.brx.utils.empty(this.options.inputs[editorId])){
                this.options.inputs[editorId].val(content);
            }
        },
        
        setupRemoteAutoComplete: function(jInput, url){
            jInput.remoteAutocomplete(url);
        },
        
        render: function(){
        },
        
        stored: function(id){
            window.storedInput = window.storedInput || {};
            return $.brx.utils.getItem(window.storedInput, id, null);
        },
        
        unstore: function(id){
            window.storedInput = window.storedInput || {};
            if(window.storedInput[id]){
                delete window.storedInput[id];
            }
        },
        
        showSpinner: function(text){
            window.showSpinner(text);
        },
        
        hideSpinner: function(){
            window.hideSpinner();
        },
        
        showFieldSpinner: function(fieldId, text){
            this.options.inputs[fieldId].addClass('ui-autocomplete-loading');
            this.setFormFieldStateHint(fieldId, text);
        },
        
        hideFieldSpinner: function(fieldId){
            this.options.inputs[fieldId].removeClass('ui-autocomplete-loading');
            this.options.hints[fieldId].text('');
        },
        
        setMessage: function(message, isError){
            this.options.message.text = message;
            this.options.message.isError = isError;
        },
        
        clearMessage: function(){
            this.options.message.text = '';
            this.options.message.isError = false;
        },
        
        showMessage: function(){
            if(this.options.message.text.length){
                $.brx.modalAlert(this.options.message.text, '', this.options.message.isError? 'modal_alert':'modal_info');
            }
        },
        
        hideMessage: function(){
        },
        
        setFormFieldState: function(fieldId, message, isError){
            var field = this.options.fields[fieldId];
            var label = this.options.labels[fieldId];
            var input = this.options.inputs[fieldId];
            var tips = this.options.hints[fieldId];
            
            var state = 'clear';
            if(message){
                state = isError?'error':'hint';
            }
            switch(state){
                case 'clear':
                    input.removeClass( "ui-state-error" );
                    tips.text('').removeClass( "ui-state-highlight");
                    break;
                case 'error' :
                    input.addClass( "ui-state-error" );
                    tips.html( message ).addClass('form_field-tips_error');
                    break;
                case 'hint' :
                    input.removeClass( "ui-state-error" );
                    tips.html( message ).removeClass('form_field-tips_error');
                    break;
            }
        },
        
        setFormFieldStateClear: function(fieldId){
            this.setFormFieldState(fieldId);
        },
        
        setFormFieldStateError: function(fieldId, message){
            this.setFormFieldState(fieldId, message, true);
        },
        
        setFormFieldStateHint: function(fieldId, message){
            this.setFormFieldState(fieldId, message, false);
        },
        
        clearField: function(fieldId){
            this.setFormFieldState(fieldId);
            this.options.inputs[fieldId].val('');
        },
        
        clearForm: function(){
            for(var id in this.options.fields){
                this.clearField(id);
            }
        },
        
        getFieldVisibleValue: function(fieldId){
            if(this.inputs(fieldId).is('select')){
                return this.inputs(fieldId).find('option:selected').text();
            }
            if(this.inputs(fieldId).is('input[type=radio]')){
                return this.fields(fieldId).find('input[type=radio]:checked').next().text();
            }
            if(this.inputs(fieldId).is('input[type=checkbox]')){
                return this.fields(fieldId).find('input[type=chekbox]:checked').next().text();
            }
            if(!$.brx.utils.empty(window.tinyMCE) &&
                !$.brx.utils.empty(window.tinyMCE.editors[fieldId])){
                return this.getTinyMceContent(fieldId);
            }
            return this.inputs(fieldId).data('placeholder')?
                this.inputs(fieldId).data('placeholder').val():
                this.inputs(fieldId).val();
        },
        
        getFieldValue: function(fieldId){
            if(this.inputs(fieldId).is('select')){
                return this.inputs(fieldId).val();
            }
            if(this.inputs(fieldId).is('input[type=radio]')){
                return this.fields(fieldId).find('input[type=radio]:checked').val();
            }
            if(this.inputs(fieldId).is('input[type=checkbox]')){
                return this.fields(fieldId).find('input[type=chekbox]:checked').val();
            }
            if(!$.brx.utils.empty(window.tinyMCE) &&
                !$.brx.utils.empty(window.tinyMCE.editors[fieldId])){
                return this.getTinyMceContent(fieldId);
            }
            return this.inputs(fieldId).data('placeholder')?
                this.inputs(fieldId).data('placeholder').val():
                this.inputs(fieldId).val();
        },

        checkLength: function ( fieldId, fieldLabel, min, max, messageTemplate ) {
            fieldLabel = fieldLabel || this.labels(fieldId).text().replace(':', '');
            min = min || this.fields(fieldId).attr('check-length-min') || 0;
            max = max || this.fields(fieldId).attr('check-length-max') || 0;
            messageTemplate = messageTemplate 
                || this.fields(fieldId).attr('check-length-message')
                || "Длина значения должна быть от <%= min %> до <%= max => символов."
            var message = _.template(messageTemplate, {min: min, max: max, label: fieldLabel}); 
            var input = this.options.inputs[fieldId];
            if ( max && input.val().length > max || min && input.val().length < min ) {
                this.setFormFieldStateError(fieldId, message);
                return false;
            } else {
                return true;
            }
        },

        checkRegexp: function ( fieldName, regexp, errorMessage ) {
            regexp = regexp 
                || new RegExp(this.fields(fieldName).attr('check-regexp-pattern'),this.fields(fieldName).attr('check-regexp-modifiers'));
            errorMessage = errorMessage 
                || this.fields(fieldName).attr('check-regexp-message')
                || 'Неверный формат';
            var input = this.options.inputs[fieldName];
            if ( !( regexp.test( input.val() ) ) ) {
                this.setFormFieldStateError(fieldName, errorMessage );
                return false;
            } else {
                return true;
            }
        },
    
        checkEmail: function (fieldName, errorMessage){
            errorMessage = errorMessage 
                || this.fields(fieldName).attr('check-email')
                || "(образец: master@potroydom.by)";
            return this.checkRegexp( fieldName, /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i, errorMessage );
        },
		
        checkPassword: function (fieldId, min, errorTemplate){
            min = min || this.fields(fieldName).attr('check-pass-min') || 0;
            errorTemplate = errorTemplate 
                || this.fields(fieldName).attr('check-pass-message')
                || "Пароль должен быть не короче <%= min %> символов."
            var errorMessage = _.template(errorTemplate, {min: min});
            var input = this.options.inputs[fieldId];
            if ( input.val().length < min ) {
                this.setFormFieldStateError(fieldId, errorMessage);
                return false;
            } else {
                return true;
            }
        },
        
        checkPasswordMatch: function(pass1FieldId, pass2FieldId, errorMessage){
            pass2FieldId = pass2FieldId
                || this.fields(pass1FieldId).attr('check-pass-match-id');

            errorMessage = errorMessage 
                || this.fields(pass1FieldId).attr('check-pass-match-message')
                || "Введенные пароли отличаются";
            var inputPass1 = this.options.inputs[pass1FieldId];
            var inputPass2 = this.options.inputs[pass2FieldId];
            if ( inputPass1.val() != inputPass2.val()) {
//                this.setFormFieldStateError(pass1FieldId, errorMessage );
                this.setFormFieldStateError(pass2FieldId, errorMessage );
                return false;
            } else {
                return true;
            }
        },
        
        checkRequired: function ( fieldName, errorMessage ) {
            errorMessage = errorMessage 
                || this.fields(fieldName).attr('check-required')
                || "Необходимо заполнить";
            var input = this.options.inputs[fieldName];
            var val = this.getFieldValue(fieldName);
            if ( !val ) {
                this.setFormFieldStateError(fieldName, errorMessage );
                return false;
            } else {
                this.setFormFieldStateClear(fieldName);
                return true;
            }
        },

        checkRequiredTinyMce: function ( fieldName, errorMessage ) {
            errorMessage = errorMessage 
                || this.fields(fieldName).attr('check-required')
                || "Необходимо заполнить";
            var value = this.getTinyMceContent(fieldName).replace(/<[^>]+>/g).trim();
            var valid = value.length > 0;
            if ( !valid ) {
                this.setFormFieldStateError(fieldName, errorMessage );
                return false;
            } else {
                return true;
            }
        },
        
        checkField: function(fieldId){
            valid = true;
            value = this.getFieldValue(fieldId);
            if(valid && this.fields(fieldId).attr('check-required')){
                valid = valid && this.checkRequired(fieldId);
            }else if(!value){
                return true;
            }
            if(valid && this.fields(fieldId).attr('check-email')){
                valid = valid && this.checkEmail(fieldId);
            }
            if(valid && this.fields(fieldId).attr('check-regexp-pattern')){
                valid = valid && this.checkRegexp(fieldId);
            }
            if(valid && this.fields(fieldId).attr('check-length-min') || this.fields(fieldId).attr('check-length-max')){
                valid = valid && this.checkLength(fieldId);
            }
            if(valid && this.fields(fieldId).attr('check-pass-min')){
                valid = valid && this.checkPassword(fieldId);
            }
            if(valid && this.fields(fieldId).attr('check-pass-match-id')){
                valid = valid && this.checkPasswordMatch(fieldId);
            }
            return valid
        },

        checkFields: function(fieldIds){
            var valid = true;
            for(var i in fieldIds){
                var fieldId = fieldIds[i];
                valid = this.checkField(fieldId) && valid;
            }
            
            return valid;
        },

        handleAjaxErrors: function(data){
            this.processErrors($.brx.utils.handleErrors(data));
        },
        
        processErrors: function(errors){
            console.dir({'processErrors': errors});
            for(key in errors){
                var errorMessage = errors[key];
                var field = 'messageBox';
                if(!$.brx.utils.empty(this.options.fields[key])){
                    field = key;
                }
                if(field!='messageBox'){
                    this.setFormFieldStateError(field, errorMessage );
                }else{
                    this.setMessage(errorMessage, true);
                }
            }
        }
        
    });
    
}(jQuery));

