<?php

class WP_Customize_Textarea_Control extends WP_Customize_Control{
    
    public function __construct($manager, $id, $args = array()) {
        parent::__construct($manager, $id, $args);
        if(!isset($args['type'])){
            $this->type = 'textarea';
        }
    }
    
    protected function render_content() {
        switch( $this->type ) {
            case 'textarea':
                ?>
                <label>
                        <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                        <textarea <?php $this->link(); ?> style="width: 98%; resize: vertical;"><?php echo esc_attr( $this->value() ); ?></textarea>
                </label>
                <?php
                break;
            default:
                parent::render_content();
        }

    }
}

