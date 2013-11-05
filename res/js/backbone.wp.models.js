(function( $, _ ) {
    
    _.declare('wp.PostModel', $.brx.Model, {

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
                comment_count: 0,
                reviews_count: 0,
                post_mime_type: '',
                href: '',
                terms: [],
                comments: []
            };
            
        },
        
        collectionFields: ['terms', 'comments'],
        
        dateFields: ['post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt'],
        
        userIdAttribute: 'post_author',
                
        strings: {
//            course_mode:{
//                'FT': 'full-time',
//                'OL': 'online'
//            },
        },
        
        initialize: function(){
        },
        
//        getString: function(attr, defaultValue){
//            if(_.isUndefined(defaultValue)){
//                defaultValue = 'unknown';
//            }
//            return this.get(attr)?_.getItem(this, 'strings.'+attr+'.'+this.get(attr), defaultValue):defaultValue;
//        },

        setUserId: function(val){
            return this.set('post_author', parseInt(val));
        },
                
        getUserId: function(){
            return parseInt(this.get('post_author', 0));
        },
        
        setParentId: function(val){
            return this.set('post_parent', parseInt(val));
        },
                
        getParentId: function(){
            return parseInt(this.get('post_parent', 0));
        },
        
        setType: function(val){
            return this.set('post_type', val);
        },
                
        getType: function(){
            return this.get('post_type', 0);
        },
        
        setSlug: function(val){
            return this.set('post_name', val);
        },
                
        getSlug: function(){
            return this.get('post_name', '');
        },
        
        setTitle: function(val){
            return this.set('post_title', val);
        },
                
        getTitle: function(){
            return this.get('post_title', '');
        },
        
        setContent: function(val){
            return this.set('post_content', val);
        },
                
        getContent: function(){
            return this.get('post_content', 0);
        },
        
        setExcerpt: function(val){
            return this.set('post_excerpt', val);
        },
                
        getExcerpt: function(){
            return this.get('post_excerpt', 0);
        },
        
        setDate: function(val){
            return this.set('post_date', val);
        },
                
        getDate: function(){
            return this.get('post_date', null);
        },
        
        setStatus: function(val){
            return this.set('post_status', val);
        },
                
        getStatus: function(){
            return this.get('post_status', 0);
        },
        
        setDateGmt: function(val){
            return this.set('post_date_gmt', val);
        },
                
        getDateGmt: function(){
            return this.get('post_date_gmt', null);
        },
        
        setModified: function(val){
            return this.set('post_modified', val);
        },
                
        getModified: function(){
            return this.get('post_modified', null);
        },
        
        setModifiedGmt: function(val){
            return this.set('post_modified_gmt', val);
        },
                
        getModifiedGmt: function(){
            return this.get('post_modified_gmt', null);
        },
                
        setPingStatus: function(val){
            return this.set('ping_status', val);
        },
                
        getPingStatus: function(){
            return this.get('ping_status', 'open');
        },
        
        setToPing: function(val){
            return this.set('to_ping', val);
        },
                
        getToPing: function(){
            return this.get('to_ping', '');
        },
        
        setPinged: function(val){
            return this.set('pinged', val);
        },
                
        getPinged: function(){
            return this.get('pinged', '');
        },
        
        setMenuOrder: function(val){
            return this.set('menu_order', val);
        },
                
        getMenuOrder: function(){
            return this.get('menu_order', 0);
        },
        
        setCommentStatus: function(val){
            return this.set('comment_status', val);
        },
                
        getCommentStatus: function(){
            return this.get('comment_status', 'open');
        },
        
        setCommentCount: function(val){
            return this.set('comment_count', val);
        },
                
        getCommentCount: function(){
            return this.get('comment_count', 0);
        },
        
        setReviewsCount: function(val){
            return this.set('reviews_count', val);
        },
                
        getReviewsCount: function(){
            return this.get('reviews_count', 0);
        },
        
        setMimeType: function(val){
            return this.set('post_mime_type', val);
        },
                
        getMimeType: function(){
            return this.get('post_mime_type', '');
        }, 
                
        getHref: function(){
            return this.get('href');
        },
                
        getImageData: function(size){
            if(size){
                return this.get('image.'+size);
            }
            
            return this.get('image');
        },
        
        getImageData_Thumbnail: function(){
            return this.getImageData('thumbnail');
        },
        
        getImageData_Medium: function(){
            return this.getImageData('medium');
        },
        
        getImageData_Large: function(){
            return this.getImageData('large');
        },
        
        getImageData_Full: function(){
            return this.getImageData('full');
        },
        
        getThumbData: function(size){
            if(size){
                return this.get('thumbnail.'+size);
            }
            
            return this.get('thumbnail');
        },
        
        getThumbData_Thumbnail: function(){
            return this.getThumbData('thumbnail');
        },
        
        getThumbData_Medium: function(){
            return this.getThumbData('medium');
        },
        
        getThumbData_Large: function(){
            return this.getThumbData('large');
        },
        
        getThumbData_Full: function(){
            return this.getThumbData('full');
        }
        
    });

    _.declare('wp.PostModels', $.brx.Collection, {

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

    _.declare('wp.CommentModel', $.brx.Model, {

        urlRoot: '/api/comment-model',
        
        // Default attributes for the todo item.
        defaults: function() {
            return {
//                id: 0,
                comment_post_ID: 0,
                comment_author: '',
                comment_author_email: '',
                comment_author_url: '',
                comment_content: '',
                comment_karma: '',
                comment_karma_delta: '',
                comment_approved: '0',
                comment_agent: null,
                comment_parent: 0,
                comment_type: null,
                comment_date: null,
                comment_date_gmt: null,
                user_id: 0
            };
            
        },
        
        dateFields: ['comment_date', 'comment_date_gmt'],
        
        strings: {
//            course_mode:{
//                'FT': 'full-time',
//                'OL': 'online'
//            },
        },
        
        initialize: function(){
        },
        
//        getString: function(attr, defaultValue){
//            if(_.isUndefined(defaultValue)){
//                defaultValue = 'unknown';
//            }
//            return this.get(attr)?_.getItem(this, 'strings.'+attr+'.'+this.get(attr), defaultValue):defaultValue;
//        },

        setPostId: function(val){
            return this.set('comment_post_ID', parseInt(val));
        },
                
        getPostId: function(){
            return parseInt(this.get('comment_post_ID', 0));
        },
        
        setAuthor: function(val){
            return this.set('comment_author', val);
        },
                
        getAuthor: function(){
            var user = this.getUser();
            if(user){
                return user.getDisplayName() || user.getLogin();
            }
            return this.get('comment_author', '');
        },
        
        setEmail: function(val){
            return this.set('comment_author_email', val);
        },
                
        getEmail: function(){
            var user = this.getUser();
            if(user){
                return user.getEmail();
            }
            return this.get('comment_author_email', '');
        },
        
        setUrl: function(val){
            return this.set('comment_author_url', val);
        },
                
        getUrl: function(){
            var user = this.getUser();
            if(user){
                return user.getUrl();
            }
            return this.get('comment_author_url', '');
        },
        
        setUserId: function(val){
            return this.set('user_id', parseInt(val));
        },
                
        getUserId: function(){
            return parseInt(this.get('user_id', 0));
        },
                
        getUser: function(){
            if(this.getUserId()){
                var users = _.getVar('$.wp.users');
                if(users){
                    return users.get(this.getUserId());
                }
            }
            
            return null;
        },
        
        setContent: function(val){
            return this.set('comment_content', val);
        },
                
        getContent: function(){
            return this.get('comment_content', '');
        },
        
        setKarma: function(val){
            return this.setInt('comment_karma', val);
        },
                
        getKarma: function(){
            return this.getInt('comment_karma', 0);
        },
        
        setKarmaDelta: function(val){
            return this.setInt('comment_karma_delta', val);
        },
                
        getKarmaDelta: function(){
            return this.getInt('comment_karma_delta', 0);
        },
        
        setApproved: function(val){
            return this.set('comment_approved', val);
        },
                
        getApproved: function(){
            return this.get('comment_approved', false);
        },
        
        setAgent: function(val){
            return this.set('comment_agent', val);
        },
                
        getAgent: function(){
            return this.get('comment_agent', 0);
        },
        
        setParentId: function(val){
            return this.set('comment_parent', parseInt(val));
        },
                
        getParentId: function(){
            return parseInt(this.get('comment_parent', 0));
        },
        
        setType: function(val){
            return this.set('comment_type', val);
        },
                
        getType: function(){
            return this.get('comment_type', 0);
        },
        
        setDate: function(val){
            return this.set('comment_date', val);
        },
                
        getDate: function(){
            return this.get('comment_date', null);
        },
        
        setDateGmt: function(val){
            return this.set('comment_date_gmt', val);
        },
                
        getDateGmt: function(){
            return this.get('comment_date_gmt', null);
        },
        
        getAuthorName: function(){
            var user = this.getUserId()?$.wp.users.get(this.getUserId()):null;
            var name = user?user.getDisplayName():this.getAuthor();
            return name?name:'- unknown -';
        },
                
        vote: function(delta, callback){
            var url = delta > 0? 
                '/api/comment/vote-up/':
                '/api/comment/vote-down/';
            $.ajax(url, {
                data:{
                    id: this.id
                },
                dataType: 'json',
                type: 'post'
            })

            .done($.proxy(function(data){
                if(0 === data.code){
                    this.set(data.payload);
                }else{
                    var message = data.message 
                        || 'Voting failed';
                    $.brx.modalAlert(message);
                }
            },this))

            .fail($.proxy(function(response){
                var message = $.brx.utils.processFail(response) 
                    || 'Voting failed';
                $.brx.modalAlert(message);//'Пароль изменен');
            },this))

            .always($.proxy(function(){
                if(_.isFunction(callback)){
                    callback.apply(null, arguments);
                }
            },this));
        },
                
        voteUp: function(callback){
            this.vote(1, callback);
        },
                
        voteDown: function(callback){
            this.vote(-1, callback);
        }
    });

    _.declare('wp.CommentModels', $.brx.Collection, {

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

    _.declare('wp.UserModel', $.brx.Model, {

        urlRoot: '/api/user-model',
        
        // Default attributes for the todo item.
        defaults: function() {
            return {
//                id: 0,
                user_login: '',
                user_nicename: '',
                user_email: '',
                user_url: '',
                user_registered: '',
                user_status: '',
                display_name: '',
                first_name: '',
                last_name: '',
                description: '',
                role: 'guest',
                jabber: '',
                aim: '',
                yim: '',
                profile_link: ''
            };
            
        },
        
        dateFields: ['user_registered'],
        
        strings: {
//            course_mode:{
//                'FT': 'full-time',
//                'OL': 'online'
//            },
        },
        
        initialize: function(){
        },
        
//        getString: function(attr, defaultValue){
//            if(_.isUndefined(defaultValue)){
//                defaultValue = 'unknown';
//            }
//            return this.get(attr)?_.getItem(this, 'strings.'+attr+'.'+this.get(attr), defaultValue):defaultValue;
//        },
        
        setLogin: function(val){
            return this.set('user_login', val);
        },
                
        getLogin: function(){
            return this.get('user_login', '');
        },
        
        setNicename: function(val){
            return this.set('user_nicename', val);
        },
                
        getNicename: function(){
            return this.get('user_nicename', '');
        },
        
        setEmail: function(val){
            return this.set('user_email', val);
        },
                
        getEmail: function(){
            return this.get('user_email', '');
        },
        
        setUrl: function(val){
            return this.set('user_url', val);
        },
                
        getUrl: function(){
            return this.get('user_url', '');
        },
        
        setDtRegistered: function(val){
            return this.set('user_registered', val);
        },
                
        getDtRegistered: function(){
            return this.get('user_registered', null);
        },
        
        setStatus: function(val){
            return this.set('user_status', val);
        },
                
        getStatus: function(){
            return this.get('user_status', 0);
        },
        
        setDisplayName: function(val){
            return this.set('display_name', val);
        },
                
        getDisplayName: function(){
            return this.get('display_name', '');
        },
        
        setFirstName: function(val){
            return this.set('user_login', val);
        },
                
        getLastName: function(){
            return this.get('user_login', '');
        },
        
        setDescription: function(val){
            return this.set('description', val);
        },
                
        getDescription: function(){
            return this.get('description', '');
        },
        
        setRole: function(val){
            return this.set('role', val);
        },
                
        getRole: function(){
            return this.get('role', 'guest');
        },
        
        setJabber: function(val){
            return this.set('jabber', val);
        },
                
        getJabber: function(){
            return this.get('jabber', '');
        },
        
        setAim: function(val){
            return this.set('aim', val);
        },
                
        getAim: function(){
            return this.get('aim', '');
        },
        
        setYim: function(val){
            return this.set('yim', val);
        },
                
        getYim: function(){
            return this.get('yim', '');
        },
                
        getProfileLink: function(){
            return this.get('profile_link', '');
        }
        
    });

    _.declare('wp.UserModels', $.brx.Collection, {

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

}(jQuery, _));