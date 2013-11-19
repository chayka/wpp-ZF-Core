(function($,_) {

    _.declare('brx.AnalogDigit', $.brx.View, {
        options: {
            offset_left: 5,
            offset_number: 4,
            image: null,
            context: null,
            canvas: null,
            ready: true,
            format: '',
            value: 0
        },
        
        nlsNamespace: 'brx.CountDown',
        


        postCreate: function(){
            
            var canvas = $('<canvas></canvas>').css({
                position: 'absolute',
                left: -5,
                top: 0
            }).appendTo(this.$el)[0];

            canvas.width = $(this.$el).width() + this.getInt('offset_left') * 2 + 3;
            canvas.height = $(this.$el).height();

            this.set('canvas', canvas);
            
            var context = canvas.getContext('2d');
            this.set('context', context);
            
            this.set('value', parseInt(this.$('span').text(), 10));
            
            if (document.createElement('canvas').getContext) {
                var image = $('<img />').appendTo(this.$el).load($.proxy(function(){
                    this.set('ready', true);
                    this.render(this.getValue(), 11);
                }, this))
                .attr('src', '/wp-content/plugins/wpp-ZF-Core/res/img/analog-digit-sprites.png');

                this.set('image', image[0]);
            } else {
                this.$('span').show();
            }
            

        },
                
        getContext: function(){
            return this.get('context');
        },
                
        getImage: function(){
            return this.get('image');
        },
                
        getCanvas: function(){
            return this.get('canvas');
        },
                
        getValue: function(){
            return this.getInt('value');
        },
                
        setValue: function(value, animate){
            if(undefined === animate){
                animate = true;
            }
            if(animate){
                this.animate(value);
            }else{
                this.render(value, value);
            }
        },
                                
        drawNumberLine: function(position, number, line) {
            var image = this.getImage();
            this.getContext().drawImage(image, number * 25, line, 25, 1, position * 25 + this.getInt('offset_left') + this.getInt('offset_number'), line, 25, 1);
        },
                
        drawNumberBottom: function(position, number) {
            var image = this.getImage();
            this.getContext().drawImage(image, number * 25, 25, 25, 23, position * 25 + this.getInt('offset_left') + this.getInt('offset_number'), 25, 25, 23);
        },
                
        drawNumberTop: function(position, number) {
            var image = this.getImage();
            this.getContext().drawImage(image, number * 25, 0, 25, 23, position * 25 + this.getInt('offset_left') + this.getInt('offset_number'), 0, 25, 23);
        },
                
        render: function(to, step) {
            var from = this.getValue();
            step = step || 0;
            if(!this.get('ready')){
                return;
            }
            var canvas = this.getCanvas();
            var context = this.getContext(),
                offsets = [
                    {w: 2, h: -2},
                    {w: 4, h: -5},
                    {w: 6, h: -9},
                    {w: 8, h: -14},
                    {w: 10, h: -20},
                    {w: 0, h: 0},
                    {w: 10, h: -20},
                    {w: 8, h: -14},
                    {w: 6, h: -9},
                    {w: 4, h: -5},
                    {w: 2, h: -2},
                    {w: 0, h: 0}
                ],
                width = canvas.width,
                nwidth = width + offsets[step].w,
                maxscale = nwidth / width,
                height = 23,
                nheight = height + offsets[step].h,
                scale_y = nheight / height,
                dig_col = parseInt($(canvas).parents('div.c-block')[0].className.substr(-1), 10);
        
            this.set('context', context);
            
            from = from.toString();
            to = to.toString();

            while (from.length < dig_col) {
                from = '0' + from;
            }
            while (to.length < dig_col) {
                to = '0' + to;
            }

            context.clearRect(0, 0, width, canvas.height);
            context.save();

            for (var j = 0; j <= to.length - 1; j++) {
                this.drawNumberTop(j, to[j]);
            }

            if (step === 11) {
                for (var j = 0; j <= to.length - 1; j++) {
                    this.drawNumberBottom(j, to[j]);
                }
                return;
            }


            for (var j = 0; j <= from.length - 1; j++) {
                this.drawNumberBottom(j, from[j]);
            }

            // draw bg
            context.drawImage(this.getImage(), 0, 96 + step * 48, width - 5, 48, 0, 0, width - 5, 48);
            context.drawImage(this.getImage(), 125, 96 + step * 48, 4, 48, width - 5, 0, 5, 48);

            for (var i = 0; i < 24; i++) {
                if (step < 5) {
                    context.setTransform(maxscale - (maxscale - 1) * i / 23, 0, 0, scale_y, -(nwidth - width) / 2 * (1 - i / 23), -offsets[step].h);
                    for (var j = 0; j <= from.length - 1; j++) {
                        this.drawNumberLine(j, from[j], i);
                    }
                }

                if (step > 5) {
                    context.setTransform(1 + (maxscale - 1) * i / 23, 0, 0, scale_y, -(nwidth - width) / 2 * (i / 23), -offsets[step].h);
                    for (var j = 0; j <= to.length - 1; j++) {
                        this.drawNumberLine(j, to[j], 25 + i);
                    }
                }
                context.restore();
            }
            context.setTransform(1, 0, 0, 1, 0, 0);

            // draw blick
            context.drawImage(this.getImage(), 130, 96 + step * 48, width - 5, 48, 0, 0, width - 5, 48);
            context.drawImage(this.getImage(), 255, 96 + step * 48, 4, 48, width - 5, 0, 5, 48);
        },

        animate: function(/*from,*/ to, step) {
            step = step || 0;
            var from = this.getInt('value');
            this.render(to, step);
            step++;
            if (step > 11){
                this.setInt('value', to);
                this.$('span').text(to);
                this.trigger('change', to);
                Backbone.Events.trigger('brx.AnalogDigit.change', to, this);
//                var title = window.moment.fn._lang.relativeTime(this.getValue(), true, this.get('format'), true);
//                var m = /^\d+\s(.+)$/.exec(title);
//                title = _.getItem(m, 1);
                return;
            }

            setTimeout($.proxy(function() {
                this.animate(/*from,*/ to, step);
            }, this), 50 - 20 * step / 11);
        }
               
    });
    
    _.declare('brx.CountDown', $.brx.View, {
        options:{
            showDeadline: null,
            outdated: null
        },
                
                
        postCreate: function(){
            if(this.get('outdated')){
                this.showDeadline();
            }else{
                this.listenTo(  Backbone.Events, 'brx.AnalogDigit.change', 
                                $.proxy(this.renderTitles, this));
                this.renderTitles();
                this.eachSecond();
            }
        },
                
        renderTitle: function(id){
            var digit = this.get(id);
            var title = window.moment.fn._lang.relativeTime(digit.getValue(), true, digit.get('format'), true);
            var m = /^\d+\s(.+)$/.exec(title);
            title = _.getItem(m, 1);
            digit.$el.parent().find('.etitle').text(title);

            return title;
        },
                
        renderTitles: function() {
            this.renderTitle('seconds_cnt');
            this.renderTitle('minutes_cnt');
            this.renderTitle('hourses_cnt');
            this.renderTitle('days_cnt');
        },
                
        showDeadline: function(){
            var heightTimer = 0;
            if(this.$('.timer').is(':visible')){
                heightTimer = this.$('.timer').height();
                this.$('.deadline_message').css('height', heightTimer + 'px');
                this.$('.deadline_message').css('line-height', heightTimer + 'px');
            }
            if(this.get('showDeadline')){
                this.$('.timer').hide('fade', {}, 600, $.proxy(function(){
                    this.$('.deadline_message').show('fade', {}, 600);
                }, this));
//                alert('111');
            }
        },

        eachSecond: function() {
            var from = this.get('seconds_cnt').getValue();
            var to = from - 1;

            if (from <= 0) {
                to = 59;

                // minutes animate
                var minutes_from = parseInt(this.get('minutes_cnt').getValue(), 10);
                var minutes_to = minutes_from - 1;

                if (minutes_from <= 0) {
                    minutes_to = 59;

                    // hourses animate
                    var hourses_from = parseInt(this.get('hourses_cnt').getValue(), 10);
                    var hourses_to = hourses_from - 1;

                    if (hourses_from <= 0) {
                        hourses_to = 23;

                        var days_from = parseInt(this.get('days_cnt').getValue(), 10);
                        var days_to = days_from - 1;

                        if (days_from === 0) {
                            this.showDeadline();
                            return;
                        }
                        this.get('days_cnt').animate(days_to);
                    }
                    this.get('hourses_cnt').animate(hourses_to);
                }

                this.get('minutes_cnt').animate(minutes_to);
            }

            this.get('seconds_cnt').animate(to);

            from--;
            if (from < 0) {
                from = 59;
            }

            setTimeout($.proxy(this.eachSecond, this), 1000);
        }
                
        
    });

//    if (document.createElement('canvas').getContext) {
//        var image = $('<img />').load(function() {
//            var image = this,
//                    offset_left = 5,
//                    offset_number = 4;
//
//            function drawNumberLine(context, position, number, line) {
//                context.drawImage(image, number * 25, line, 25, 1, position * 25 + offset_left + offset_number, line, 25, 1);
//            }
//
//            function drawNumberBottom(context, position, number) {
//                context.drawImage(image, number * 25, 25, 25, 23, position * 25 + offset_left + offset_number, 25, 25, 23);
//            }
//
//            function drawNumberTop(context, position, number) {
//                context.drawImage(image, number * 25, 0, 25, 23, position * 25 + offset_left + offset_number, 0, 25, 23);
//            }
//
//            function cf_draw(from, to, canvas, step) {
//                var context = canvas.getContext('2d'),
//                        offsets = [
//                    {w: 2, h: -2},
//                    {w: 4, h: -5},
//                    {w: 6, h: -9},
//                    {w: 8, h: -14},
//                    {w: 10, h: -20},
//                    {w: 0, h: 0},
//                    {w: 10, h: -20},
//                    {w: 8, h: -14},
//                    {w: 6, h: -9},
//                    {w: 4, h: -5},
//                    {w: 2, h: -2},
//                    {w: 0, h: 0}
//                ],
//                        width = canvas.width,
//                        nwidth = width + offsets[step].w,
//                        maxscale = nwidth / width,
//                        height = 23,
//                        nheight = height + offsets[step].h,
//                        scale_y = nheight / height,
//                        dig_col = parseInt($(canvas).parents('div.c-block')[0].className.substr(-1), 10);
//
//                from = from.toString();
//                to = to.toString();
//
//                while (from.length < dig_col) {
//                    from = '0' + from;
//                }
//                while (to.length < dig_col) {
//                    to = '0' + to;
//                }
//
//
//                context.clearRect(0, 0, width, canvas.height);
//                context.save();
//
//                for (var j = 0; j <= to.length - 1; j++) {
//                    drawNumberTop(context, j, to[j]);
//                }
//
//                if (step === 11) {
//                    for (var j = 0; j <= to.length - 1; j++) {
//                        drawNumberBottom(context, j, to[j]);
//                    }
//                    return;
//                }
//
//
//                for (var j = 0; j <= from.length - 1; j++) {
//                    drawNumberBottom(context, j, from[j]);
//                }
//
//                // draw bg
//                context.drawImage(image, 0, 96 + step * 48, width - 5, 48, 0, 0, width - 5, 48);
//                context.drawImage(image, 125, 96 + step * 48, 4, 48, width - 5, 0, 5, 48);
//
//                for (var i = 0; i < 24; i++) {
//                    if (step < 5) {
//                        context.setTransform(maxscale - (maxscale - 1) * i / 23, 0, 0, scale_y, -(nwidth - width) / 2 * (1 - i / 23), -offsets[step].h);
//                        for (var j = 0; j <= from.length - 1; j++) {
//                            drawNumberLine(context, j, from[j], i);
//                        }
//                    }
//
//                    if (step > 5) {
//                        context.setTransform(1 + (maxscale - 1) * i / 23, 0, 0, scale_y, -(nwidth - width) / 2 * (i / 23), -offsets[step].h);
//                        for (var j = 0; j <= to.length - 1; j++) {
//                            drawNumberLine(context, j, to[j], 25 + i);
//                        }
//                    }
//                    context.restore();
//                }
//                context.setTransform(1, 0, 0, 1, 0, 0);
//
//                // draw blick
//                context.drawImage(image, 130, 96 + step * 48, width - 5, 48, 0, 0, width - 5, 48);
//                context.drawImage(image, 255, 96 + step * 48, 4, 48, width - 5, 0, 5, 48);
//            }
//
//            function cf_animate(from, to, canvas, step) {
//                cf_draw(from, to, canvas, step);
//                step++;
//                if (step > 11)
//                    return;
//
//                setTimeout(function() {
//                    cf_animate(from, to, canvas, step);
//                }, 50 - 20 * step / 11);
//            }
//
//            function seconds(from, canvas) {
//                var to = from - 1;
//
//                if (from <= 0) {
//                    to = 59;
//
//                    // minutes animate
//                    var minutes_from = parseInt($('span', minutes_cnt).text(), 10);
//                    var minutes_to = minutes_from - 1;
//
//                    if (minutes_from <= 0) {
//                        minutes_to = 59;
//
//                        // hourses animate
//                        var hourses_from = parseInt($('span', hourses_cnt).text(), 10);
//                        var hourses_to = hourses_from - 1;
//
//                        if (hourses_from <= 0) {
//                            hourses_to = 23;
//
//                            var days_from = parseInt($('span', days_cnt).text(), 10);
//                            var days_to = days_from - 1;
//
//                            if (days_from === 0) {
//                                return;
//                            }
//                            cf_animate(days_from, days_to, $('canvas', days_cnt)[0], 0);
//                            $('span', days_cnt).text(days_to);
//                        }
//                        cf_animate(hourses_from, hourses_to, $('canvas', hourses_cnt)[0], 0);
//                        $('span', hourses_cnt).text(hourses_to);
//                    }
//
//                    cf_animate(minutes_from, minutes_to, $('canvas', minutes_cnt)[0], 0);
//                    $('span', minutes_cnt).text(minutes_to);
//                }
//
//                cf_animate(from, to, canvas, 0);
//
//                from--;
//                if (from < 0) {
//                    from = 59;
//                }
//
//                setTimeout(function() {
//                    seconds(from, canvas);
//                }, 1000);
//            }
//
//
//            $('div.bl-inner').each(function() {
//                var canvas = $('<canvas></canvas>').css({
//                    position: 'absolute',
//                    left: -5,
//                    top: 0
//                }).appendTo(this)[0];
//
//                canvas.width = $(this).width() + offset_left * 2 + 3;
//                canvas.height = $(this).height();
//
//                cf_draw(parseInt($('span', this).text(), 10), parseInt($('span', this).text(), 10), canvas, 11);
//            });
//
//            var     seconds_cnt = $('div.time .bl-inner:last'),
//                    minutes_cnt = $('div.time .bl-inner:eq(2)'),
//                    hourses_cnt = $('div.time .bl-inner:eq(1)'),
//                    days_cnt = $('div.time .bl-inner:eq(0)');
//
//            seconds(parseInt($('span', seconds_cnt).text(), 10), $('canvas', seconds_cnt)[0]);
//        });
//        image.attr('src', 'i/sprites.png');
//    } else {
//        $('div.bl-inner span').show();
//    }
}(jQuery, _));
