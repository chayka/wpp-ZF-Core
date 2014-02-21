(function($, _){
    _.declare('brx.RibbonSlider', $.brx.View, {
        options:{
           direction: 'vertical',
           offset: 0,
           items: {},
           currentPage: 0,
        },
        
        postCreate: function(){
            this.render();
            this.get('slider').swipe({
                triggerOnTouchEnd : true,
                swipeStatus : $.proxy(this.onSwipe, this),
                excludedElements: '',//"label, button, input, select, textarea, a, .noSwipe",
                allowPageScroll:"vertical"                
            });
        },
                
        render: function(){
            this.renderNavVisibility();
            if(this.get('direction')!=='auto'){
                this.$el.addClass(this.get('direction'));
            }
//            this.get('slider').css('width',);
        },
        
        getDirection: function(){
            if(this.get('direction')==='auto'){
                var slider = this.get('slider');
                var itemViews = slider.find('.brx-ribbon_slider-item');
                console.dir('');
                var direction = slider.width() === itemViews.width() ? 'vertical':'horizontal';
                console.dir({getDirection:{
                    direction: direction,
                    slider_width: slider.width(),
                    itemViews_width: itemViews.width()
                }});
                
                this.$el.removeClass('vertical horizontal').addClass(direction);
                return direction;
            }
            return this.get('direction');
        },
                
        isVertical: function(){
            return this.getDirection()==='vertical';
//            return this.get('direction')==='vertical';
        },
        
        addItem: function(el, key){
            var $el = $(el);
            $el.addClass('brx-ribbon_slider-item');
            $el.appendTo(this.get('slider'));
            this.options.items[key]=$el;
            this.renderNavVisibility();
        },
                
        removeItem: function(key){
            var $el = _.getItem(this.options.items, key);
            if($el){
                $el.remove();
                delete this.options.items[key];
                this.renderNavVisibility();
            }
        },
        
        getItem: function(key){
            return _.getItem(this.options.items, key);
        },
        
        
        renderNavVisibility: function(currentTopOrLeft){
            var slider = this.get('slider');
            if(currentTopOrLeft === undefined){
                currentTopOrLeft = parseInt(slider.css(this.isVertical()?'top':'left'));
            }
            var itemViews = slider.find('.brx-ribbon_slider-item');
            var itemCount = itemViews.length;
            var itemSize = 0;
            var itemMargin = 0;
            var ribbonSize = 0;
            var itemsSeen = 0;
            var totalPages = 0;
            var currentPage = 0;
            if(itemCount){
                itemSize = this.isVertical()?itemViews.height():itemViews.width();
                itemMargin = parseInt(itemViews.css(this.isVertical()?'margin-bottom':'margin-right'));
                itemSize+=itemMargin;
                ribbonSize = this.isVertical()?this.get('ribbon').height():this.get('ribbon').width();
                itemsSeen = Math.floor((ribbonSize+itemMargin) / itemSize);
                totalPages = Math.ceil(itemCount / itemsSeen);
                currentPage = -Math.floor(currentTopOrLeft / itemSize / itemsSeen);
                this.renderPages(currentPage, totalPages);
            }
            if(!this.isVertical() && itemSize){
                slider.css('width', (itemSize * itemCount)+'px');
            }
            var ribbonSize = this.isVertical()?this.get('ribbon').height():this.get('ribbon').width();
            console.dir({slider:this.get('slider')});
            if(currentTopOrLeft >= 0){
                this.get('buttonSlidePrev').css('opacity', 0);
            }else{
                this.get('buttonSlidePrev').css('opacity', 1);
            }
            var maxTopOrLeft = -(itemCount* itemSize - itemMargin - ribbonSize);
            if(currentTopOrLeft <= maxTopOrLeft){
                this.get('buttonSlideNext').css('opacity', 0);
            }else{
                this.get('buttonSlideNext').css('opacity', 1);
            }
        },
        
        renderPages: function(current, total){
            var box = this.get('pagesBox');
            var pages = box.find('li');
            if(pages.length < total){
                for(var i = pages.length; i < total; i++){
                    var page = $('<li data-slide-ribbon-to="'+i+'" class=""></li>');
                    page.click($.proxy(function(e){
                        console.dir({e: e});
                        var li = e.currentTarget;
                        var p = parseInt($(li).attr('data-slide-ribbon-to'));
                        this.slideToPage(p);
                    }, this));
                    page.appendTo(box);
                }
            }else if(pages.length > total){
                for(var i = pages.length-1; i>=total; i--){
                    box.find('li[data-slide-ribbon-to='+i+']').remove();
                }
            }
            pages.removeClass('active');
            box.find('li[data-slide-ribbon-to='+current+']').addClass('active');
            box.css('opacity', total>1?1:0);
            this.setInt('currentPage', current);
        },
        
        scroll: function(distance, duration){
            if(duration === undefined){
                duration = 600;
            }
            var v = (duration/1000).toFixed(1) + "s";
            this.get('slider').css("transition-duration", v);              
            this.get('slider').css("-webkit-transition-duration", v);              
            this.get('slider').css("-moz-transition-duration", v);              
            this.get('slider').css("-o-transition-duration", v);              
//            this.get('slider').css(this.isVertical()?'top':'left', distance+'px');
            v = this.isVertical()?
                "translate3d(0px,"+distance +"px,0px)":
                "translate3d("+distance +"px,0px,0px)";
            this.get('slider').css("transform", v);
            this.get('slider').css("-webkit-transform", v);
            this.get('slider').css("-moz-transform", v);
            this.get('slider').css("-o-transform", v);
        },
        
        scrollOffset: function(offset, duration){
            var currentTopOrLeft = this.getInt('offset');
            this.scroll(currentTopOrLeft - offset, duration);
        },
        
        slideToPage: function(page){
            console.log('page: '+page);
            var current = this.getInt('currentPage');
            var offset = page - current;
            this.slide(offset);
        },
        
        slide: function(sign){
            sign = sign || 0;
            var slider = this.get('slider');
            var currentTopOrLeft = this.getInt('offset');//= parseInt(slider.css(this.isVertical()?'top':'left'));
            var itemViews = slider.find('.brx-ribbon_slider-item');
            var itemSize = this.isVertical()?itemViews.height():itemViews.width();
            var itemMargin = parseInt(itemViews.css(this.isVertical()?'margin-bottom':'margin-right'));
            itemSize+=itemMargin;
            var itemCount = itemViews.length;
            var ribbonSize = this.isVertical()?this.get('ribbon').height():this.get('ribbon').width();
            var itemsSeen = Math.floor((ribbonSize+itemMargin) / itemSize);
            var offset = sign?itemsSeen * itemSize:0;
//            if(sign < 0){
//                offset*=-1;
//            }
            offset*=sign;
            console.dir({slider:this.get('slider')});
            var newTopOrLeft = currentTopOrLeft - offset;
            if(newTopOrLeft >= 0){
                newTopOrLeft = 0;
            }
            var maxTopOrLeft = -(itemCount* itemSize - itemMargin - ribbonSize);
            if(newTopOrLeft <= maxTopOrLeft){
                newTopOrLeft = maxTopOrLeft;
            }
//            this.get('slider').css(this.isVertical()?'top':'left', newTopOrLeft+'px');
            this.renderNavVisibility(newTopOrLeft);
            this.setInt('offset', newTopOrLeft);
            this.scroll(newTopOrLeft);
        },
                
        slidePrev: function(){
            this.slide(-1);
        },

        slideNext: function(){
            this.slide(1);
        },
        
        onSwipe: function(event, phase, direction, distance, fingers){
            switch(phase){
                case 'move':
                    var controlDirection = this.getDirection();
                    if('left' === direction && 'horizontal' === controlDirection
                    || 'top' === direction && 'vertical' === controlDirection){
                        this.scrollOffset(distance, 0);
                    }
                    if('right' === direction && 'horizontal' === controlDirection
                    || 'bottom' === direction && 'vertical' === controlDirection){
                        this.scrollOffset(-distance, 0);
                    }
                    break;
                case 'cancel':
                    this.slide();
                    break;
                case 'end':
                    var controlDirection = this.getDirection();
                    if('left' === direction && 'horizontal' === controlDirection
                    || 'top' === direction && 'vertical' === controlDirection){
                        this.slideNext();
                    }
                    if('right' === direction && 'horizontal' === controlDirection
                    || 'bottom' === direction && 'vertical' === controlDirection){
                        this.slidePrev();
                    }
                    break;
            }
        }
        
    });
}(jQuery, _));


