<?php

class WidgetHelper {
    protected static $scriptPaths = array();
    
    public static function addScriptPath($path){
        if(!in_array($path, self::$scriptPaths)){
            self::$scriptPaths[]=$path;
        }
    }
    
    public static function renderWidget($data, $tpl, $js, $css = null){
        
        $view = new Zend_View();
        $view->setScriptPath(ZF_CORE_APPLICATION_PATH.'/views/scripts');
        foreach(self::$scriptPaths as $path){
            $view->addScriptPath($path);
        }
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
    
    public static function renderSingleSpinner($populate=''){
        self::renderWidget(array('populate'=>$populate), 'widgets/brx.SingleSpinner.view.phtml', 'backbone-brx-spinners');
    }
    
    public static function renderMultiSpinner($populate='$.brx.multiSpinner'){
        self::renderWidget(array('populate'=>$populate), 'widgets/brx.MultiSpinner.view.phtml', 'backbone-brx-spinners');
    }
    
    public static function renderPagination(){
        
    }
    
    public static function renderJobControl($params){
        self::renderWidget($params, 'widgets/brx.JobControl.view.phtml', 'backbone-brx-jobControl');
    }
    
    public static function renderTaxonomyPicker($params){
        self::renderWidget($params, 'widgets/brx.TaxonomyPicker.view.phtml', 'backbone-brx-taxonomyPicker');
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
    
    public static function renderRibbonSlider($items = array(), $direction='auto', $htmlAttrs = array()){
        self::renderWidget(array('items'=>$items, 'direction'=>$direction, 'htmlAttrs'=>$htmlAttrs), 'widgets/brx.RibbonSlider.view.phtml', 'backbone-brx-ribbonSlider');
    }
    
    public static function renderRibbonSliderHorizontal($items){
        self::renderRibbonSlider($items, 'horizontal');
    }
    
    public static function renderRibbonSliderVertical($items){
        self::renderRibbonSlider($items, 'vertical');
    }
    
    public static function renderCountDownTimer($dtDeadline, $callToAction='', $deadlineMessage=''){
//        Util::print_r($dtDeadline);
        self::renderWidget(array(
            'deadline'=>$dtDeadline,
            'callToAction'=>$callToAction,
            'deadlineMessage'=>$deadlineMessage,
        ), 'widgets/brx.CountDown.view.phtml', 'backbone-brx-countDown');
    }
    
}
