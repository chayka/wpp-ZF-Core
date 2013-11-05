(function( $, _ ) {
//    $.widget( "mcc.AdmissionWizard", $.brx.form, {
    _.declare( "brx.Wizard", $.brx.FormView, {
//    $.mcc.AdmissionWizard = $.brx.FormView.extend({
        options: { 
            elementAsTemplate: true,
            url: '',
            currentScreen: 0,
            summaryReached: false,
            screens: {},
            screenFields: {},
            model: null
        },
        
        
        
        // Set up the widget
        postCreate: function() {
            for (var field in this.options.fields){
                this.initField(field);
                this.setupFieldChecks(field);
//                this.inputs(field).bind('focus click',$.proxy(function(event){
//                    var field = event.currentTarget.name;
//                    this.setFormFieldStateClear(field);
//                }, this));
                var screenId = this.getFieldScreenId(field);
                if($.brx.utils.empty(this.options.screenFields[screenId])){
                    this.options.screenFields[screenId]=[];
                }
                this.options.screenFields[screenId].push(field);
            }
            
            this.get('wizardBox').show();
            this.get('completedBox').hide();
            this.prerenderProgressBar();
            this.render();
            this.gotoScreen();
            
        },
        
        prerenderProgressBar: function(){
            this.get('progressBar').html('');
            var total = this.getTotalScreens();
            var barsize = Math.floor((this.get('progressBar').innerWidth() - 30) / (total - 1));
            for(var i = 0; i < total; i++){
                if(i){
                    var bar = $('<div class="bar"></div>')
                        .css('width', barsize+'px')
                        .appendTo(this.get('progressBar'));
                }
                var step = $('<div class="step"></div>')
                        .text(i+1)
                        .appendTo(this.get('progressBar'))
                        .tooltip({title: this.getScreenTitle(i)});
            }
        },
        
        renderProgressBar: function(number){
            this.get('progressBar').find('.step')
                .removeClass('step-passed step-current')
                .each($.proxy(function(e, elem){
                    if(e < this.getScreenNumber()){
                        $(elem).addClass('step-passed');
                    }else if(e === this.getScreenNumber()){
                        $(elem).addClass('step-current');
                    }
                }, this));
            this.get('progressBar').find('.bar')
                .removeClass('bar-passed')
                .each($.proxy(function(e, elem){
                    if(e < this.getScreenNumber()){
                        $(elem).addClass('bar-passed');
                    }
                }, this));
        },

        render: function(){
            this.get('screenTitleView').text(
                'Step '+(this.getScreenNumber()+1) 
                + ' of '+ this.getTotalScreens() + ': ' 
                + this.getScreenTitle()
            );
            if(this.isFirstScreen()){
                this.buttons('previous').hide();
            }else{
                this.buttons('previous').show();
            }
            if(this.isLastScreen()){
                this.set('summaryReached', true);
                this.buttons('summary').hide();
                this.buttons('next').hide();
                this.buttons('submit').show();
            }else{
                this.buttons('next').show();
                this.buttons('submit').hide();
                if(this.get('summaryReached')){
                    this.buttons('summary').show();
                }else{
                    this.buttons('summary').hide();
                }
            }
            this.renderScreen();
        },
        
        isFirstScreen: function(){
            return 0 === this.getInt('currentScreen');
            
        },

        isLastScreen: function(){
            var totalScreens = this.getTotalScreens();
            return totalScreens - 1 === this.getInt('currentScreen');
            
        },
        
        getTotalScreens: function(){
            return _.keys(this.get('screens')).length;
        },
        
        getScreenNumber: function(){
            return this.getInt('currentScreen') || 0;
        },
        
        getScreenBox: function(number){
            number = number || this.getScreenNumber();
            var keys = _.keys(this.get('screens'));
            var key = keys[number];
            return this.get('screens')[key];
        },
        
        getScreenTitle: function(number){
            number = number || this.getScreenNumber();
            var title = this.getScreenBox(number).attr('title');
            return title;
        },
        
        renderScreen: function(number){
            number = number || this.getScreenNumber();
            var renderer = this.getScreenBox(number).attr('render');
            if(renderer){
                var callback = $.proxy(this[renderer], this);
                callback();
            }
        },
        
        validateScreen: function(number){
            number = number || this.getScreenNumber();
            var validator = this.getScreenBox(number).attr('validate');
            if(validator){
                var callback = $.proxy(this[validator], this);
                return callback();
            }
            
            return true;
            
        },
        
        gotoScreen: function(number){
            number = number || 0;
            this.set('currentScreen', number);
            var totalScreens = this.getTotalScreens();
            if (number < 0){
                number = 0;
            }else if(number >= this.getTotalScreens()){
                number = totalScreens - 1;
            }
            this.get('screenContainer').find('.screen').hide();
            this.getScreenBox(number).show();
            this.renderProgressBar(number);
            this.render();
        },
        
        gotoPrevious: function(){
            var screen = this.get('currentScreen');
            if(!this.get('summaryReached') || this.validateScreen(screen)){
                this.gotoScreen(screen-1);
            }
        },
        
        gotoNext: function(){
            var screen = this.get('currentScreen');
            if(this.validateScreen(screen)){
                this.gotoScreen(screen + 1);
            }
        },
        
        gotoSummary: function(){
            var screen = this.get('currentScreen');
            if(this.validateScreen(screen)){
                this.gotoScreen(this.getTotalScreens()-1);
            }
        },
        
        renderSummary: function(){
            this.get('summaryBox').text('');
            var i = 0;
            for(var id in this.get('screenFields')){
//                var screen = this.$el.find('#'+id);
                var fields = this.get('screenFields')[id];
                var screenSummaryBox = $('<div class="summary_screen"></div>');
                var button = $('<button class="btn btn-danger btn-mini"></button>')
                    .text('Edit')
                    .click($.proxy(this.gotoScreen, this, i))
                    .appendTo(screenSummaryBox);
                
                for(var f in fields){
                    var fieldId = fields[f];
                    console.log(fieldId);
                    var fieldBox = $('<div class="form_field"></div>');
                    var labelBox = $('<label></label>')
                                .text(this.labels(fieldId).text())
                                .appendTo(fieldBox);
                    var valueBox = $('<div class="form_field-value"></div>')
                                .text(this.getFieldVisibleValue(fieldId))
                                .appendTo(fieldBox);
                    $('<div class="clearfloat"></div>').appendTo(fieldBox);
                    screenSummaryBox.append(fieldBox);
                }
                this.get('summaryBox').append(screenSummaryBox);
                i++;
            }
        },
        
        getFieldScreenId: function(fieldId){
            var parent = this.fields(fieldId).parent();
            while(!parent.is('body, .screen')){
                parent = parent.parent();
            }
            return parent.attr('id') || '*';
        },
        
        getScreenFieldIds: function(number){
            return fields = this.get('screenFields')[this.getScreenBox(number).attr('id')];
        },
        
        submitData: function(event){
            event.preventDefault();
            var data = {};
            for (var field in this.options.fields){
                data[field] = this.getFieldValue(field);
            }
            this.clearMessage();
            this.showSpinner('Submitting data...');
            this.get('model').save(data, {
                success: $.proxy(function(model, response, options){
                    this.hideSpinner();
                    console.dir({'application success': {
                            'model': model,
                            'response': response,
                            'options' : options
                        }});
                    if(0 === response.code){
//                        this.set('setupOptions', data.payload);
                        this.render();
                        this.showCompletion();
                    }else{
                          this.handleAjaxErrors(response);
                    }
                    this.showMessage();
                    
                }, this),
                
                error: $.proxy(function(model, xhr, options){
                    this.hideSpinner();
                    console.dir({'application error': {
                            'model': model,
                            'xhr': xhr,
                            'options' : options
                        }});
                    var message = $.brx.utils.processFail(xhr) 
                        || 'Data submission failed';
                    this.setMessage(message, true);
                    this.showMessage();
                    
                }, this)
            });

        },
        
        showCompletion: function(){
            var renderer = this.get('wizardBox').attr('render');
            if(renderer){
                renderer = $.proxy(this[renderer], this);
                renderer();
            }
            this.get('wizardBox').hide();
            this.get('completedBox').show();
            
        },
        
        destroy: function(){
            
        }
    });
}(jQuery, _));


