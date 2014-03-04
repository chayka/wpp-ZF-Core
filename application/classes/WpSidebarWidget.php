<?php

require_once 'Zend/View.php';

class WpSidebarWidget extends WP_Widget {
    
    protected static $args;
    
    protected $scriptPath;

    public static function getArgs(){
        return self::$args;
    }
    
    public static function getTitle(){
        return self::$args['title'];
    }
    
    public static function getDefault($data, $key, $default = ''){
        $value = $default;
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (is_array($data)) {
            if (isset($data[$key])) {
                $value = $data[$key];
            }
        }

        return $value;
    }
    
    function __construct($id = 'widget-zf_core', $name = 'Base Widget', 
        $widget_ops = array(
            'classname' => 'widget-zf_core',
            'description' => "Base wideget"
        )) {
        parent::__construct($id, $name, $widget_ops);
        $this->alt_option_name = $id;

    }
    
    public function getScriptPath() {
        return $this->scriptPath;
    }

    public function setScriptPath($scriptPath) {
        $this->scriptPath = $scriptPath;
    }
        
    protected function getView($instance){
        $view = new Zend_View();
        $view->setScriptPath($this->getScriptPath());//WPT_JURCATALOGBY_PATH.'application/views/scripts'
        $view->assign($instance);
        $view->widget = $this;
        return $view;
    }

    function widget($args, $instance) {
        self::$args = $instance;
        extract($args);
        $beforeWidget = self::getDefault($args, 'before_widget');
        $beforeTitle = self::getDefault($args, 'before_title');
        $afterTitle = self::getDefault($args, 'after_title');
        $afterWidget = self::getDefault($args, 'after_widget');
        $title = apply_filters( 'widget_title', self::getDefault($instance, 'title') );
        $view = $this->getView($instance);
        $tpl = sprintf('sidebar/%s-view.phtml', $this->id_base);
        $content = $view->render($tpl);

        if($content){
            echo $beforeWidget;
            if ( ! empty( $title ) ){
                echo $beforeTitle . $title . $afterTitle;
            }
            
            echo $content;

            echo $afterWidget;
        }
    }

    function flush() {
        wp_cache_delete($this->id_base, 'widget');
    }
    
    
    
    function update($newInstance, $oldInstance) {
        $instance = $oldInstance;
        foreach($newInstance as $key=>$value){
            $instance[$key] = InputHelper::filter($value, $key);
        }
        $this->flush();

        $alloptions = wp_cache_get('alloptions', 'options');
        if (isset($alloptions[$this->id_base])){
            delete_option($this->id_base);
        }
        
        return $instance;
    }

    function form($instance) {
        $view = $this->getView($instance);
        $tpl = sprintf('sidebar/%s-form.phtml', $this->id_base);
        try{
        echo $view->render($tpl);
        }  catch (Exception $e){
            return parent::form($instance);
        }
    }
    
    public static function registerWidget(){
        $item = new self();
        register_widget(get_class($item));
    }

}
