(function( $ ) {
    
    $.declare('wp.PostModel', $.brx.Model, {

        urlRoot: '/api/post-model',
        
        // Default attributes for the todo item.
        defaults: function() {
            return {
//                id: 0,
                post_author: 0,
                post_parent: 0,
                post_type: 'post',
                post_name: '',
                post_title: '',
                post_content: '',
                post_excerpt: '',
                post_status: null,
                post_date: null,
                post_date_gmt: null,
                post_modified: null,
                post_modified_gmt: null,
                ping_status: '',
                to_ping: '',
                pinged: '',
                menu_order: '',
                comment_status: '',
                terms: [],
                comments: []
            };
            
        },
        
        collectionFields: ['terms', 'comments'],
        
        dateFields: ['post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt'],
                
        strings: {
//            course_mode:{
//                'FT': 'full-time',
//                'OL': 'online'
//            },
        },
        
        initialize: function(){
        },
        
        getString: function(attr, defaultValue){
            if(_.isUndefined(defaultValue)){
                defaultValue = 'unknown';
            }
            return this.get(attr)?_.getItem(this, 'strings.'+attr+'.'+this.get(attr), defaultValue):defaultValue;
        }

    });

    $.declare('wp.PostModels', $.brx.Collection, {

        url: '/api/post-model/',

        model: $.wp.PostModel,
        
        total: 0,
        page: 1,

        parse: function(response, options){
            this.total = parseInt(response.payload.total);
            this.page = parseInt(response.payload.page);
            return response.payload.items;
        }

    });

    $.declare('wp.CommentModel', $.brx.Model, {

        urlRoot: '/api/comment-model',
        
        // Default attributes for the todo item.
        defaults: function() {
            return {
//                id: 0,
                post_id: 0,
                author: '',
                email: '',
                url: '',
                user_id: 0,
                content: '',
                karma: '',
                approved: null,
                agent: null,
                parent_id: null,
                type: null,
                date: null,
            };
            
        },
        
        dateFields: ['date', 'date_gmt'],
        
        strings: {
//            course_mode:{
//                'FT': 'full-time',
//                'OL': 'online'
//            },
        },
        
        initialize: function(){
        },
        
        getString: function(attr, defaultValue){
            if(_.isUndefined(defaultValue)){
                defaultValue = 'unknown';
            }
            return this.get(attr)?_.getItem(this, 'strings.'+attr+'.'+this.get(attr), defaultValue):defaultValue;
        }

    });

    $.declare('wp.CommentModels', $.brx.Collection, {

        url: '/api/comment-model/',

        model: $.wp.CommentModel,
        
        total: 0,
        page: 1,

        parse: function(response, options){
            this.total = parseInt(response.payload.total);
            this.page = parseInt(response.payload.page);
            return response.payload.items;
        }

    });

    $.declare('wp.UserModel', $.brx.Model, {

        urlRoot: '/api/user-model',
        
        // Default attributes for the todo item.
        defaults: function() {
            return {
//                id: 0,
                post_id: 0,
                author: '',
                email: '',
                url: '',
                user_id: 0,
                content: '',
                karma: '',
                approved: null,
                agent: null,
                parent_id: null,
                type: null,
                date: null,
            };
            
        },
        
        dateFields: ['date', 'date_gmt'],
        
        strings: {
//            course_mode:{
//                'FT': 'full-time',
//                'OL': 'online'
//            },
        },
        
        initialize: function(){
        },
        
        getString: function(attr, defaultValue){
            if(_.isUndefined(defaultValue)){
                defaultValue = 'unknown';
            }
            return this.get(attr)?_.getItem(this, 'strings.'+attr+'.'+this.get(attr), defaultValue):defaultValue;
        }

    });

    $.declare('wp.UserModels', $.brx.Collection, {

        url: '/api/user-model/',

        model: $.wp.UserModel,
        
        total: 0,
        page: 1,

        parse: function(response, options){
            this.total = parseInt(response.payload.total);
            this.page = parseInt(response.payload.page);
            return response.payload.items;
        }

    });

}(jQuery));