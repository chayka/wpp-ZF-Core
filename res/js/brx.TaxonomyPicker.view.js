(function( $, _ ) {
    _.declare( "brx.TaxonomyPicker", $.brx.View, {
 
        // These options will be used as defaults
        options: { 
            elementAsTemplate: true,
            taxonomy: 'category',
            viewMode: 'all',
            modal: 0,
            modalOptions: {},
            max: 10,
            title: 'Выберите категории',
            forbidLevels: []
        },

        postCreate: function() {
//            this.set('taxonomy', this.$el.attr('taxonomy')||'category');
//            this.set('title', this.$el.attr('title')||'Выберите категории');
            this.set('max', parseInt(this.get('max'))||10);
            this.set('ids', this.get('hiddenInput').val()?
                this.get('hiddenInput').val().split(','):[]);
//            this.set('forbidLevels', this.$el.attr('forbidLevels')?
//                this.$el.attr('forbidLevels').split(','):[]);
            for(var i = 0; i < this.get('forbidLevels').length; i++){
                this.options.forbidLevels[i]=parseInt(this.options.forbidLevels[i]);
            }
            this.get('optionsList').find('input[type=checkbox]').each($.proxy(function(key, element){
                var input = $(element);
                var label = input.next();
                var li = input.parent();
                var handle = input.prev();
                handle.click(function(){
                    $(this).parent().toggleClass('closed open')
                    .trigger($.Event('resize'));
                });
                input.change($.proxy(this.checkedOption, this));
//                li.hover(function(event){
//                    $(this).addClass('state_highlight');
//                }, function(event){
//                    $(this).removeClass('state_highlight');
//                });
                li.attr('data-content', label.text().toLowerCase());
                li.attr('data-label', label.text());
            },this));
            
            if(this.get('forbidLevels').length){
                var max = Math.max.apply( Math, this.get('forbidLevels') );

                for(var i = 0 ; i < this.get('forbidLevels').length ; i++){
                    var level = this.options.forbidLevels[i];
                    var selector = 'ul.options > li';
                    for(x = 1; x<= level; x++){
                        selector += ' > ul.children > li';
                    }
                    selector+=' > input:checkbox';
                    this.get('boxOptions').find(selector)
                    .each(function(key, element){
                        var input = $(element);
                        var handle = input.next();
                        handle.click(function(){
                            $(this).parent().toggleClass('closed open')
                            .trigger($.Event('resize'));
                        });
                        input.hide()
                            .removeAttr('checked')
                            .removeAttr('id')
                            .remove();
                    });
                }
                
            }
            
            this.get('inputSearch').placeholder({text: 'Быстрый поиск...'});
            
            if(this.get('ids').length){
                this.checkOptions(this.get('ids'));
            }else{
                this.checkedOption();
                this.get('hiddenInput').val(this.get('ids').join(','));
            }
            this.renderSelectedOptions();
            this.get('inputSearch').placeholder('val', '');
            this.searchOptions();
            this.$el.show();
            
        },
        
        searchOptions: function(){
            var term = this.get('inputSearch').placeholder('val').toLowerCase();
            this.get('optionsList').find('li.contains').each(function(key, element){
                var li = $(element);
//                if(!term.length || !li.is("li[data-content*='"+term+"']")){
                    li.find('> label').text(li.attr('data-label'));
//                }
            });
            this.get('optionsList').find('li').removeClass('contains found');
            if(term.length){
                this.set('viewMode', 'search');
                this.get('optionsList').removeClass('mode_all mode_selected').addClass('mode_search');
//                this.get('optionsList').find('li').hide();
                var count = this.get('optionsList').find("li[data-content*='"+term+"']")
                    .each(function(key, element){
                        var li = $(element);
                        li.addClass('contains');
                        var re = new RegExp(term, 'gi');
                        li.find('> label').html(li.attr('data-label').replace(re, function(m){return '<b>'+m+'</b>';}));
                        while('LI' === li[0].nodeName){
//                            li.show();
                            li.addClass('found');
                            li = li.parent().parent();
                        }
                    }).length;
                if(count){
                    this.get('boxNothing').hide();
                    this.get('optionsList').show();
                }else{
                    this.get('boxNothing').show();
                    this.get('optionsList').hide();
                }
                console.info('search: '+ term+" found: "+count);
                
            }else{
                this.set('viewMode', 'all');
                this.get('optionsList').removeClass('mode_search mode_selected').addClass('mode_all');
                this.get('boxNothing').hide();
                this.get('optionsList').show();
                this.showAll();
//                this.get('optionsList').find('li').show();
            }
            this.renderLinks();
        },
        
        checkedOption: function(){
            console.log(' checkedOption max: '+this.get('max'));
            this.get('optionsList').find('li.selected').removeClass('selected');
            this.set('ids', []);
            var checkedOptions = this.get('optionsList').find('input[type=checkbox]:checked');
            checkedOptions.each($.proxy(function(id, element){
                var input =$(element);
                this.pushId(input.val());
                var li = input.parent();
                while('LI' === li[0].nodeName){
                    li.addClass('selected');
                    li = li.parent().parent();
                }
            }, this));
            if(this.get('max')){
                console.log('max: '+this.get('max'));
                var left = this.get('max') - checkedOptions.length;
                if(left<=0){
                    this.get('optionsList').find('input[checked!=checked]').attr('disabled', 'disabled');
                }else{
                    this.get('optionsList').find('input[disabled]').removeAttr('disabled');
                }
                this.get('viewCountSelected').text(checkedOptions.length);
                this.get('boxCountSelected').show().css('color', left?'inherit':'maroon');
                this.get('viewCountLeft').text(left);
                this.get('boxCountLeft').show().css('color', left?'inherit':'maroon');

            }else{
                console.log('!max: '+this.get('max'));
                this.get('optionsList').find('input[disabled]').removeAttr('disabled');
                this.get('boxCountSelected').hide();
                this.get('boxCountLeft').hide();
                
            }
            console.info('selected: '+this.get('ids').join(','));
            this.renderLinks();
        },
        
        checkOptions: function(ids){
            console.dir({ids: ids});
            this.get('optionsList').find("li input:checkbox:checked")
                .each($.proxy(function(key, element){
                    var input = $(element);
                    input.unbind('change')
                        .removeAttr('checked')
                        .change($.proxy(this.checkedOption, this));
                },this));
                
            for(var i = 0 ; i<ids.length; i++){
                var id = '#cb-'+this.get('taxonomy')+'-'+ids[i];
                this.get('optionsList').find(id)
                    .unbind('change')
                    .attr('checked','checked')
                    .change($.proxy(this.checkedOption, this));
            }
            this.checkedOption();
        },
        
        checkOption: function(id){
            id = '#cb-'+this.get('taxonomy')+'-'+id;
            this.get('optionsList').find(id)
                .attr('checked','checked');
            
        },
        
        uncheckOption: function(id){
            id = '#cb-'+this.get('taxonomy')+'-'+id;
            this.get('optionsList').find(id)
                .removeAttr('checked');
            
        },
                
        deleteOption: function(id){
            var ids = this.get('ids');
            ids = _.without(ids, id);
            this.checkOptions(ids);
            this.renderSelectedOptions();
            this.get('hiddenInput').val(this.get('ids').join(',')).trigger('change');
        },
        
        pushId: function(id){
            this.options.ids.push(id);
        },
        
        showAll: function(){
            this.set('viewMode', 'all');
            this.get('optionsList').removeClass('mode_search mode_selected').addClass('mode_all');
            this.get('boxNothing').hide();
//            this.get('optionsList').show().find('li').show();
            this.renderLinks();
        },
        
        showSelected: function(){
            this.set('viewMode', 'selected');
            this.get('optionsList').removeClass('mode_all mode_search').addClass('mode_selected');
            this.get('optionsList').find('li').removeClass('selected');
//            this.get('optionsList').find('li').hide();
            var count = this.get('optionsList').find("li input:checkbox:checked")
                .each(function(key, element){
                    var li = $(element).parent();
                    while('LI' === li[0].nodeName){
//                        li.show();
                        li.addClass('selected');
                        li = li.parent().parent();
                    }
                }).length;
                
            this.renderLinks();
        },
        
        renderLinks: function(){
            var isSelected = this.get('ids').length;
            var isSearch = this.get('inputSearch').placeholder('val').length;
            switch(this.get('viewMode')){
                case 'all':
                    this.get('linkShowAll').hide();
                    this.get('linkShowSelected').css('display', isSelected?'inline':'none');
                    this.get('linkShowSearch').hide();
                    this.get('boxNothing').hide();
                    this.get('optionsList').show();
                    break;
                case 'search':
                    this.get('linkShowAll').hide();
                    this.get('linkShowSelected').css('display', isSelected?'inline':'none');
                    this.get('linkShowSearch').hide();
                    break;
                case 'selected':
                    this.get('linkShowAll').css('display', isSearch?'none':'inline');
                    this.get('linkShowSelected').hide();
                    this.get('linkShowSearch').css('display', isSearch?'inline':'none');
                    this.get('boxNothing').hide();
                    this.get('optionsList').show();
                    break;
            }
        },
        
        renderSelectedOptions: function(){
            var ids = this.get('ids');
            this.get('boxSelected').text('').css('display', ids.length?'block':'none');
            for(var i = 0; i < ids.length;i++){
                var selector = '#li-'+this.get('taxonomy')+'-'+ids[i]+' label';
                var title = this.get('optionsList').find(selector).html();
                var deleteButton = $('<span class="ui-icon ui-icon-closethick">Удалить</span>').click($.proxy(this.deleteOption, this, ids[i]));
                $('<li></li>').html(title).prepend(deleteButton).appendTo(this.get('boxSelected'));
            }
        },
        
        linkSelectClicked: function(){
            this.set('initialIds', this.get('ids'));
            this.checkOptions(this.get('ids'));
            var options = $.extend({
                title: this.get('title'),
                modal: this.get('modal'),
                css:{
                    width: '400px'
                }
                
            }, this.get('modalOptions'));
            $.brx.Modals.show(this.get('boxOptions'), options);
//            this.get('boxOptions').dialog({
//                title: this.get('title'),
//                width: 400,
//                modal: this.get('modal')
//            });
            this.get('inputSearch').blur();
        },
        
        buttonOkClicked: function(){
//            this.get('boxOptions').dialog('close');
            $.brx.Modals.hide(this.get('boxOptions'));
            this.get('hiddenInput').val(this.get('ids').join(',')).trigger('change');
            this.renderSelectedOptions();
        },
        
        buttonCancelClicked: function(){
//            this.get('boxOptions').dialog('close');
            $.brx.Modals.hide(this.get('boxOptions'));
            var ids = this.get('initialIds');
            this.checkOptions(ids);
            this.renderSelectedOptions();
            
        },
        
        destroy: function() {
            // In jQuery UI 1.8, you must invoke the destroy method from the base widget
//            $.Widget.prototype.destroy.call( this );
        // In jQuery UI 1.9 and above, you would define _destroy instead of destroy and not call the base method
        }
    });
}( jQuery, _ ) );