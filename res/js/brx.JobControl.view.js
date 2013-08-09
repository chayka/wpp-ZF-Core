(function($){
    $.declare('brx.JobControl', $.brx.View, {
        options:{
            jobId: '',
            perIteration: 10,
            total: 100,
            processed: -1,
            state: ''
        },
        
        postCreate: function(){
            console.dir({'brx.JobControl': this});
            
            this.get('inputs.perIteration').change($.proxy(function(){
                this.setPerIteration(this.get('inputs.perIteration').val());
            },this)).change();
        },
                
        render: function(){
            var processed = this.getProcessed();
            var total = this.getTotal();
            if(processed < 0){
                this.get('progresslabel').text('Подключение...');
                this.get('progressbar').value(false);
            }else if(total){
                var value = Math.floor(processed / total * 100);
                var text = processed + ' / ' + total + ' (' + value + '%)';
                this.get('progresslabel').text(text);
                this.get('progressbar').value(value);
            }else{
                this.get('progresslabel').text('Операция завершена');
                this.get('progressbar').value(100);
            }
        },
                
        setPerIteration: function(val){
            return this.setInt('perIteration', val);
        },
                
        getPerIteration: function(){
            return this.getInt('perIteration');
        },
                
        setTotal: function(val){
            return this.setInt('total', val);
        },
                
        getTotal: function(){
            return this.getInt('total');
        },
                
        setProcessed: function(val){
            return this.setInt('processed', val);
        },
                
        getProcessed: function(){
            return this.getInt('processed');
        },
               
        isFinished: function(){
            return this.getTotal() === this.getProcessed();
        },
                
        setProgress: function(processed, total){
            this.setProcessed(processed);
            this.setTotal(total || this.getTotal() || 100);
            if(this.isFinished()){
                this.setState('finished');
            }
            this.render();
            return this;
        },
                
        setState: function(state){
            this.$el.removeClass('running paused finished').addClass(state);
            if(state === 'running'){
                this.get('inputs.perIteration').attr('disabled', 'disabled');
            }else{
                this.get('inputs.perIteration').removeAttr('disabled');
            }
            return this.set('state', state);
        },
                
        getState: function(){
            return this.get('state');
        },
                
        addLogMessage: function(message){
            var box = $('<div class="message"></div').text(message);
            this.get('boxOutput').prepend(box);
        },
        
        clearLog: function(){
            this.get('boxOutput').empty();
        },
        
        start: function(){
            Backbone.Events.trigger('JobControl.start', this.get('jobId'));
        },
                
        started: function(){
            this.setState('running');
        },
                
        pause: function(){
            Backbone.Events.trigger('JobControl.pause', this.get('jobId'));
        },
          
        paused: function(){
            this.setState('paused');
        },
                
        resume: function(){
            Backbone.Events.trigger('JobControl.resume', this.get('jobId'));
        },
                
        resumed: function(){
            this.setState('running');
        },
                
        stop: function(){
            Backbone.Events.trigger('JobControl.stop', this.get('jobId'));
        },
                
        stopped: function(){
            this.setState('');
        }
    });
}(jQuery));