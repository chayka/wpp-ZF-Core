(function($){
    $.declare('brx.Pagination', $.brx.View, {
        options:{
            totalPages: 0,
            currentPage: 1,
            packSize: 10,
            hrefTemplate: '#page<%= page %>',
            pageCallback: null
        },
        
        postCreate: function(){
            this.options.totalPages = parseInt(this.options.totalPages);
            this.options.currentPage = parseInt(this.options.currentPage);
            this.options.packSize = parseInt(this.options.packSize);
            this.render();
        },
        
        render: function(){
//            var packNumber = Math.floor(this.get('currentPage') / this.get('packSize'));
//            var totalPacks = Math.floor(this.get('totalPages') / this.get('packSize'));
            var current = this.getCurrentPage();
            var packSize = this.getPackSize();
            var totalPages = this.getTotalPages();
            var packStart = 1;
            var packFinish = totalPages;

            this.$el
                .text('')
                .css('display', totalPages>1?'block':'none');
                ;

            var ul = $('<ul></ul>').appendTo(this.$el);
            if(packSize < totalPages){
                packStart = current - Math.floor((packSize -1)/ 2);
                packFinish = current + Math.ceil((packSize -1)/ 2);
                var offset = 0;
                if(packStart<1){
                    offset = 1 - packStart;
                }
                if(packFinish>totalPages){
                    offset = totalPages - packFinish;
                }
                packStart+=offset;
                packFinish+=offset;
            }

            ul.append(this.renderItem(current-1, '&larr;'));
            if(packStart > 1){
                ul.append(this.renderItem(1));
            }
            if(packStart > 2){
                ul.append(this.renderItem(packStart - 1, '...'));
            }
            for(var i = packStart; i <= packFinish; i++){
                ul.append(this.renderItem(i));
            }
            if(totalPages - packFinish >= 2){
                ul.append(this.renderItem(packFinish + 1, '...'));
            }
            if(totalPages > packFinish){
                ul.append(this.renderItem(totalPages));
            }
            ul.append(this.renderItem(current+1, '&rarr;'));
            
            return this;
        },
        
        renderItem: function(page, text){
            page = parseInt(page);
            text = text || page;
            var href = this.getHref(page);
            var cls = '';
            if(page == this.getCurrentPage()){
                cls = 'active';
            }
            if(page < 1 || page > this.getTotalPages()){
                cls = 'disabled'
            }
            var item = $('<a></a>')
                .attr('href', href)
                .html(text)
                ;
            if(_.isFunction(this.get('pageCallback'))){
                item.click(this.get('pageCallback'));
            }
            return $('<li></li>').append(item).addClass(cls);
        },
        
        setTotalPages: function(total, render){
            this.set('totalPages', parseInt(total));

            if(_.isUndefined(render) || render){
                this.render();
            }
            
            return this;
        },
        
        getTotalPages: function(){
            return parseInt(this.get('totalPages'));
        },
        
        setCurrentPage: function(page, render){
            this.set('currentPage', parseInt(page));

            if(_.isUndefined(render) || render){
                this.render();
            }
            
            return this;
        },
        
        getCurrentPage: function(){
            return parseInt(this.get('currentPage'));
        },
        
        setPackSize: function(packSize, render){
            this.set('packSize', parseInt(packSize));

            if(_.isUndefined(render) || render){
                this.render();
            }
            
            return this;
        },
        
        getPackSize: function(){
            return parseInt(this.get('packSize'));
        },
        
        setHrefTemplate: function(hrefTemplate, render){
            this.set('hrefTemplate', hrefTemplate);

            if(_.isUndefined(render) || render){
                this.render();
            }
            
            return this;
        },
        
        getHref: function(page){

            if(page >= 1 && page <= this.getTotalPages() && this.get('hrefTemplate')){
                return _.template(this.get('hrefTemplate'), {page: page});
            }
            
            return '#';
            
        }
        
    })
}(jQuery));

