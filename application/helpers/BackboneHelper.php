<?php

class BackboneHelper{
    
    protected static $initialized = array();

    public static function isInitialized($id){
        return Util::getItem(self::$initialized, $id);
    }
    
    public static function getView($populateId = ''){
        wp_enqueue_script('backbone-wp-modells');
//        wp_print_scripts();
        $view = new Zend_View();
        $view->setScriptPath(ZF_CORE_PATH.'application/views/scripts/backbone/');
        if($populateId){
            $view->populateId = $populateId;
            self::setInitialized($populateId);
        }
        return $view;
    }
    
    public static function setInitialized($id, $value = true) {
        self::$initialized[$id] = $value;
    }

    public static function populateUser($userOrId = null, $populateId = ''){
        $user = null;
        if($userOrId){
            $user = ($userOrId instanceof UserModel)?$userOrId:  UserModel::selectById($userOrId);
            $populateId = $populateId?$populateId:'wp.user_'.$user->getId();
        }else{
            $user = UserModel::currentUser();
            $populateId = $populateId?$populateId:'wp.currentUser';
        }
        if(self::isInitialized($populateId)) {
            return;
        }
        $view = self::getView($populateId);
        $view->user = $user;
        echo $view->render('user.phtml');
    }
    
    public static function populateUsers($users = null, $populateId = 'wp.users'){
        if(self::isInitialized($populateId)) {
            return;
        }
        $view = self::getView($populateId);
        $view->users=array_values($users?$users:UserModel::getUserCacheById());
        echo $view->render('users.phtml');
    }
    
    public static function populatePost($zfPost = null, $populateId = ''){
        global $post;
        $user = null;
        if($zfPost){
            if(is_object($zfPost)){
                $zfPost = ($zfPost instanceof PostModel)?$zfPost:  PostModel::unpackDbRecord($zfPost);
                $populateId = $populateId?$populateId:'wp.post_'.$zfPost->getId();
            }
        }else{
            $zfPost = PostModel::unpackDbRecord($post);
            $populateId = $populateId?$populateId:'wp.currentPost';
        }
        if(self::isInitialized($populateId)) {
            return;
        }
        $view = self::getView($populateId);
        $view->post = $zfPost;
        echo $view->render('post.phtml');
    }
    
    public static function populatePosts($posts = null, $populateId = 'wp.posts'){
        if(self::isInitialized('wp.posts')) {
            return;
        }
        $view = self::getView($populateId);
        $view->posts=array_values($posts?$posts:PostModel::getPostsCacheById());
        echo $view->render('posts.phtml');
    }
    
    public static function populateComments($comments = null, $populateId = 'wp.comments'){
        if(self::isInitialized($populateId)) {
            return;
        }
        $view = self::getView($populateId);
        $view->comments=array_values($comments?$comments:CommentModel::getCommentsCacheById());
        echo $view->render('comments.phtml');
    }
}