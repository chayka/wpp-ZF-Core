<?php

class WidgetHelper {
    
    public static function renderWidget($data, $tpl, $js, $css = null){
        
        $view = new Zend_View();
        $view->setScriptPath(ZF_CORE_APPLICATION_PATH.'/views/scripts');
        if($data){
            foreach($data as $key=>$value){
                $view->assign($key, $value);
            }
        }
        echo $view->render($tpl);
        
        if($js){
            if(is_array($js)){
                foreach($js as $handle=>$src){
                    if(is_int($handle)){
                        wp_enqueue_script($src);
                    }else{
                        wp_enqueue_script($handle, $src);
                    }
                }
            }else{
                wp_enqueue_script($js);
                if(!$css && $css!==false){
                    wp_enqueue_style($js);
                }
            }
        }
        
        if($css){
            if(is_array($css)){
                foreach($css as $handle=>$src){
                    if(is_int($handle)){
                        wp_enqueue_style($src);
                    }else{
                        wp_enqueue_style($handle, $src);
                    }
                }
            }else{
                wp_enqueue_style($css);
            }
        }
    }
    
    public static function renderPagination(){
        
    }
    
    public static function renderJobControl($params){
        self::renderWidget($params, 'widgets/brx.JobControl.view.phtml', 'backbone-brx-jobControl');
    }
    
    public static function renderAttachmentPicker($params){
        $attachments = PostModel::query()
                ->postType_Attachment()
                ->authorIdIn(get_current_user_id())
                ->noPaging()
//                ->postParentId(Util::getItem($params, 'postId'))
                ->postStatus_Inherit()
                ->order_ASC()
                ->select();
        $validExtensions = Util::getItem($params, 'validExtensions');
        if($validExtensions){
            foreach($attachments as $i => $attachment){
                $file = get_attached_file( $attachment->getId());
                $ext = $file && preg_match('/\.([^.]+)$/', $file, $matches) ? strtolower($matches[1]) : false;
                if(!$ext || !in_array($ext, $validExtensions)){
                    unset($attachments[$i]);
                }
            }
            $attachments = array_values($attachments);
        }
        $params['attachments']=$attachments;
        $params['total']=  PostModel::postsFound();
        self::renderWidget($params, 'widgets/brx.AttachmentPicker.view.phtml', 'backbone-brx-attachmentPicker');
    }
    
}
