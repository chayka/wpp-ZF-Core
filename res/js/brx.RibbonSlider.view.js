(function($){
    $.declare('brx.RibbonSlider', $.brx.View, {
        options:{
           direction: 'vertical' 
        },
        
        postCreate: function(){
            
        },
                
        render: function(){
    
        },
                
        isVertical: function(){
            return this.get('direction')==='vertical';
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
        
    })
}(jQuery));


