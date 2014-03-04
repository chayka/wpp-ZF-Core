<?php

class WP_Widget_ZF extends WP_Widget {
    
    protected static $args;

    public static function getArgs(){
        return self::$args;
    }
    
    public static function getTitle(){
        return self::$args['title'];
    }
    
    function __construct($id = 'zf_app_widget', $name = 'ZF App Response', 
        $widget_ops = array(
            'classname' => 'WP_Widget_ZF',
            'description' => "Zend Framework App response"
        )) {
        parent::__construct($id, $name, $widget_ops);
        $this->alt_option_name = $id;

        add_action('save_post', array(&$this, 'flush'));
        add_action('deleted_post', array(&$this, 'flush'));
        add_action('switch_theme', array(&$this, 'flush'));
    }

    function widget($args, $instance) {
        $cache = wp_cache_get($this->id_base, 'widget');

        if (!is_array($cache))
            $cache = array();

        if (!isset($args['widget_id']))
            $args['widget_id'] = $this->id;

        if (isset($cache[$args['widget_id']])) {
            echo $cache[$args['widget_id']];
            return;
        }

        ob_start();
        $output = '';
        self::$args = $instance;
        extract($args);

        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
        $request_uri = empty($instance['uri']) ? '/' : $instance['uri'];
        $tmp = $_SERVER['REQUEST_URI'];
        try {
//            echo include WPP_ANOTHERGURU_PATH . 'zf-app/public/index.php';
            echo ZF_Query::processRequest($request_uri);
        } catch (Exception $e) {
            $_SERVER['REQUEST_URI'] = $tmp;
            echo '(' . $e->getMessage() . ')';
        }

//        echo $after_widget;
        // Reset the global $the_post as this query will have stomped on it
        wp_reset_postdata();

//		endif;

        $cache[$args['widget_id']] = ob_get_flush();
        wp_cache_set($this->id_base, $cache, 'widget');
    }

    function flush() {
        wp_cache_delete($this->id_base, 'widget');
    }
    
    public function generateUri($action, $params = array()){
        $uri = '/widget/'.$action.'/';
        $pieces = array();
        foreach($params as $key => $value){
            if($value){
                $pieces[] = $key.'/'.$value;
            }
        }
        $uri.=join('/', $pieces);
        return $uri;
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['uri'] = strip_tags($new_instance['uri']);
        $this->flush();

        $alloptions = wp_cache_get('alloptions', 'options');
        if (isset($alloptions[$this->id_base]))
            delete_option($this->id_base);

        return $instance;
    }

    function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $uri = isset($instance['uri']) ? esc_attr($instance['uri']) : '/';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('uri'); ?>"><?php _e('ZF uri:'); ?></label>
            <input id="<?php echo $this->get_field_id('uri'); ?>" name="<?php echo $this->get_field_name('uri'); ?>" type="text" value="<?php echo $uri; ?>" /></p>

        <?php
    }

}

add_action( 'widgets_init', create_function( '', 'register_widget( "WP_Widget_ZF" );' ) );

//class ZF_Widget_Articles extends WP_Widget_ZF{
//    public $action = 'articles'; 
//    public $modes = array(
//        '0'=>'По умолчанию',
//        'new'=>'Новые',
//        'votes'=>'Популярные',
//        'active'=>'Обсуждаемые'
//    );
//    
//    public function __construct($id = 'zf_articles', $name = 'ZF: Статьи', $opts = array(
//            'classname' => 'ZF_Widget_Articles',
//            'description' => "Записи из раздела Статьи"
//        )) {
//        parent::__construct($id, $name, $opts);
//    }
//    function update($new_instance, $instance) {
//        $instance['count'] = strip_tags($new_instance['count']);
//        $instance['mode'] = strip_tags($new_instance['mode']);
//        $params = array_intersect_key($new_instance, array_fill_keys(array(
//            'count',
//            'mode',
//        ), null));
//        $new_instance['uri'] = $this->generateUri($this->action, $params);
//        
//        return parent::update($new_instance, $instance);
//    }
//
//    function form($instance) {
//        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
////        $uri = isset($instance['uri']) ? esc_attr($instance['uri']) : '/';
//        $count = isset($instance['count']) ? esc_attr($instance['count']) : '5';
//        $mode = isset($instance['mode']) ? esc_attr($instance['mode']) : '0';
//        ?>
            <p><label for="//<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="//<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

            <p><label for="//<?php echo $this->get_field_id('count'); ?>"><?php _e('Count:'); ?></label>
            <input id="//<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></p>

            <p><label for="//<?php echo $this->get_field_id('mode'); ?>"><?php _e('Mode:'); ?></label>
            <select id="//<?php echo $this->get_field_id('mode'); ?>" name="<?php echo $this->get_field_name('mode'); ?>">
            //<?php foreach($this->modes as $value=>$label):?>    
                <option value="//<?php echo $value;?>" <?php if($value == $mode):?>selected="selected"<?php endif;?>><?php echo $label?></option>
            //<?php endforeach;?>
            </select>
            </p>
        //<?php
//    }
//}

