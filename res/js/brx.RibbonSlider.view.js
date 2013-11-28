(function($, _){
    _.declare('brx.RibbonSlider', $.brx.View, {
        options:{
           direction: 'vertical',
           items: {}
        },
        
        postCreate: function(){
            
        },
                
        render: function(){
    
        },
        
        getDirection: function(){
            if(this.get('direction')==='auto'){
                var slider = this.get('slider');
                var itemViews = slider.find('.brx-ribbon_slider-item');
                
                var direction = slider.width() === itemViews.width() ? 'vertical':'horizontal';
                
                this.$el.removeClass('vertical horizontal').addClass(direction);
            }
            return this.get('direction');
        },
                
        isVertical: function(){
            return this.getDirection();
//            return this.get('direction')==='vertical';
        },
        
        addItem: function(el, key){
            var $el = $(el);
            $el.addClass('brx-ribbon_slider-item');
            $el.appendTo(this.get('slider'));
            this.options.items[key]=$el;
        },
                
        removeItem: function(key){
            var $el = _.getItem(this.options.items, key);
            if($el){
                $el.remove();
                delete this.options.items[key];
            }
        },
        
        getItem: function(key){
            return _.getItem(this.options.items, key);
        },
  
        slide: function(sign){
            var slider = this.get('slider');
            var currentTopOrLeft = parseInt(slider.css(this.isVertical()?'top':'left'));
            var itemViews = slider.find('.brx-ribbon_slider-item');
            var itemSize = this.isVertical()?itemViews.height():itemViews.width();
            var itemMargin = parseInt(itemViews.css(this.isVertical()?'margin-bottom':'margin-right'));
            itemSize+=itemMargin;
            var itemCount = itemViews.length;
            var ribbonSize = this.isVertical()?this.get('ribbon').height():this.get('ribbon').width();
            var itemsSeen = Math.floor((ribbonSize+itemMargin) / itemSize);
            var offset = itemsSeen * itemSize;
            if(sign < 0){
                offset*=-1;
            }
            console.dir({slider:this.get('slider')});
            var newTopOrLeft = currentTopOrLeft - offset;
            if(newTopOrLeft >= 0){
                newTopOrLeft = 0;
                this.get('buttonSlidePrev').css('opacity', 0);
            }else{
                this.get('buttonSlidePrev').css('opacity', 1);
            }
            var maxTopOrLeft = -(itemCount* itemSize - itemMargin - ribbonSize);
            if(newTopOrLeft <= maxTopOrLeft){
                newTopOrLeft = maxTopOrLeft;
                this.get('buttonSlideNext').css('opacity', 0);
            }else{
                this.get('buttonSlideNext').css('opacity', 1);
            }
            this.get('slider').css({'top': newTopOrLeft+'px'});

        },
                
        slidePrev: function(){
            this.slide(-1);
        },

        slideNext: function(){
            this.slide(1);
        }
        
    });
}(jQuery, _));


