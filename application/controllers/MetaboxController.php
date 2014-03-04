<?php

class ZF_Core_MetaboxController extends Zend_Controller_Action{

    public function init(){
    }

    public function updateAction(){
        Util::turnRendererOff();
        $metaBoxId = InputHelper::getParam('meta_box_id');
        if(!$metaBoxId){
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        $postId = Util::getItem($_POST, 'post_ID');

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $nonce = Util::getItem($_POST, $metaBoxId.'_nonce');
        if (!wp_verify_nonce($nonce, $metaBoxId)) {
            return;
        }

        $params = InputHelper::getParams();
        foreach($params as $key => $value){
            $match = array();
            
            if(preg_match('%^metabox_([\w\d_]+)$%i', $key, $match)){
                if($value){
                    PostModel::updatePostMeta($postId, $match[1], $value);
                }else{
                    PostModel::deletePostMeta($postId, $match[1]);
                }
            }
        }
        
        return;
    }
    
    public function contentFragmentAction(){
        global $post;
        
        $zfPost = PostModel::unpackDbRecord($post);
        wp_nonce_field( 'content_fragment', 'content_fragment_nonce' );
//        Util::print_r($meta);
        $meta = array();
        $this->view->linkTo = $meta['link_to'] = $zfPost->getMeta('content_fragment_link_to');

        $this->view->meta = $meta;
        
        $this->view->postId = $post->ID;
        $this->view->zfPost = $zfPost;
        
    }
    
}