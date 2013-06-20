(function($){
    
    $.declare('brx.SingleSpinner', $.brx.View, {
        tagName: 'div',
        className: 'brx_single_spinner',
        
        options:{
            message: 'Loading...',
            count: 1
        },
        
        postCreate: function(){
            var counterView = $('<div class="spinner_counter"></div>');
            this.set('counterView', counterView);
            var msgView = $('<div class="spinner_message"></div>');
            this.set('msgView', msgView);
            this.$el.text('').append(counterView).append(msgView);
            this.render();
        },
        
        render: function(){
            this.setCount();
            this.setMessage(this.get('message'));
        },
                
        show: function(message){
            message = message || 'Loading...';
            this.set('message', message);
            this.$el.show('fade', {}, 300);
        },
        
        hide: function(){
            this.$el.hide('fade', {}, 300, $.proxy(function(){
                this.set('message', '');
                this.render();
            }, this));
        },
                
        setMessage: function(message){
            this.set('message', message);
            this.get('msgView').html(message);
        },
                
        getMessage: function(){
            return this.get('message');
        },
        
        setCount: function(count){
            count = count === undefined ? this.getInt('count', 0):count;
            this.setInt('count', count);
            this.get('counterView').text(this.getCount()>1?this.getCount():'');
        },
        
        incCount: function(){
            this.setCount(this.getCount()+1);
        },
        
        decCount: function(){
            this.setCount(this.getCount()-1);
        },
        
        getCount: function(){
            return this.getInt('count');
        }
    });
    
    $.declare('brx.MultiSpinner', $.brx.View, {
        
        tagName: 'div',
        
        className: 'brx_multi_spinner',
        
        options:{
            processes: {}
        },
        
        postCreate: function(){
            this.$el.hide();
            this.listenTo(Backbone.Events, 'brx.MultiSpinner.show', $.proxy(this.show, this));
            this.listenTo(Backbone.Events, 'brx.MultiSpinner.hide', $.proxy(this.hide, this));
//            this.unbind('mouseenter').mouseenter($.proxy(function(){
//                this.$el.addClass('hover');
//            }, this));
        },
                
        render: function(){
            var processes = this.get('processes');
//            var totalGroups = _.keys(processes).length;
            var total = 0;
            for(var id in processes){
                var process = processes[id];
                total += process.getCount();
            }
            this.get('counterView').text(total>1?total:'');
            if(total){
                this.$el.show('fade', {}, 300);
            }else{
                this.$el.hide('fade', {}, 300);
            }
        },
                
        getProcess: function(id, title){
            if(!this.options.processes){
                this.options.processes = {};
            }
            if(!_.has(this.options.processes, id)){
                if(title){
                    var spinner = new $.brx.SingleSpinner({message: title, count: 0});
                    spinner.$el.appendTo(this.get('spinnersBox'));
                    this.options.processes[id] = spinner;
                }else{
                    return null;
                }
            }else{
                this.options.processes[id].setMessage(title);
            }
            
            
            return this.options.processes[id];
        },
                
        deleteProcess: function(id){
            if(!this.options.processes){
                this.options.processes = {};
            }
            if(_.has(this.options.processes, id)){
                if(_.keys(this.options.processes).length <= 1){
                    this.options.processes[id].$el.hide('fade', {}, 300, $.proxy(function(){
                        this.options.processes[id].remove();
                        delete(this.options.processes[id]);
                        this.render();
                    }, this))
                }else{
                    this.options.processes[id].remove();
                    delete(this.options.processes[id]);
                    this.render();
                }
            }
        },
                
        show: function(message, id){
            console.dir({'spinner.show': arguments});
            id = id || '*';
            var process = this.getProcess(id, message);
            process.incCount();
            this.render();
        },
        
        hide: function(id){
            console.dir({'spinner.hide': arguments});
            id = id || '*';
            var process = this.getProcess(id);
            if(process){
                process.decCount();
                if(!process.getCount()){
                    this.deleteProcess(id);
                }
            }
            this.render();
        }
        
    });
}(jQuery));


