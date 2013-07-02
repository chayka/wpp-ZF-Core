<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdminController
 *
 * @author borismossounov
 */
class ZFCore_AdminController extends Zend_Controller_Action{

    public function init(){
    }
    
    public function indexAction(){
//        Util::turnRendererOff();
        wp_enqueue_style('zf-core-admin', ZF_CORE_URL.'res/css/bem-zf_core_admin.less');
        wp_enqueue_style('admin-setupForm');
        wp_enqueue_script('backbone-brx-optionsForm');
//        echo "Hi, i'm admin area";
    }
    
    public function emailOptionsAction(){
        wp_enqueue_style('admin-setupForm');
        wp_enqueue_script('jquery-brx-setupForm');

//        $this->view->template = $options['template'] = get_option('EmailHelper.template', '', true);
        $this->view->mail_from = $options['mail_from'] = get_option('EmailHelper.mail_from', 'postmaser@'.Util::serverName());
        $this->view->mail_from_name = $options['mail_from_name'] = get_option('EmailHelper.mail_from_name', Util::serverName());
        $this->view->mailer = $options['mailer'] = get_option('EmailHelper.mailer', 'php');
        $this->view->smtp_host = $options['smtp_host'] = get_option('EmailHelper.smtp_host', 'localhost');
        $this->view->smtp_port = $options['smtp_port'] = get_option('EmailHelper.smtp_port', '25');
        $this->view->smtp_ssl = $options['smtp_ssl'] = get_option('EmailHelper.smtp_ssl', 'none');
        $this->view->smtp_auth = $options['smtp_auth'] = get_option('EmailHelper.smtp_auth', false);
        $this->view->smtp_user = $options['smtp_user'] = get_option('EmailHelper.smtp_user', '');
        $this->view->smtp_pass = $options['smtp_pass'] = get_option('EmailHelper.smtp_pass', '');

        $this->view->options = $options;
        
    }
    
    public function updateEmailOptionsAction(){
        Util::turnRendererOff();

//        $template = InputHelper::getParam('template');
        $mail_from = InputHelper::getParam('mail_from', 'postmaser@'.$_SERVER['SERVER_NAME']);
        $mail_from_name = InputHelper::getParam('mail_from_name', $_SERVER['SERVER_NAME']);
        $mailer = InputHelper::getParam('mailer', 'php');
        $smtp_host = InputHelper::getParam('smtp_host', 'localhost');
        $smtp_port = InputHelper::getParam('smtp_port', '25');
        $smtp_ssl  = InputHelper::getParam('smtp_ssl', 'none');
        $smtp_auth  = InputHelper::getParam('smtp_auth', false);
        $smtp_user  = InputHelper::getParam('smtp_user', '');
        $smtp_pass  = InputHelper::getParam('smtp_pass', '');

//        update_option('EmailHelper.template', $template);
        update_option('EmailHelper.mail_from', $mail_from);
        update_option('EmailHelper.mail_from_name', $mail_from_name);
        update_option('EmailHelper.mailer', $mailer);
        update_option('EmailHelper.smtp_host', $smtp_host);
        update_option('EmailHelper.smtp_port', $smtp_port);
        update_option('EmailHelper.smtp_ssl', $smtp_ssl);
        update_option('EmailHelper.smtp_auth', $smtp_auth);
        update_option('EmailHelper.smtp_user', $smtp_user);
        update_option('EmailHelper.smtp_pass', $smtp_pass);
        
        JsonHelper::respond(array(
//            'template' => get_option('EmailHelper.template', '', true),
            'mail_from' => get_option('EmailHelper.mail_from', 'postmaster@'.$_SERVER['SERVER_NAME']),
            'mail_from_name' => get_option('EmailHelper.mail_from_name', $_SERVER['SERVER_NAME']),
            'mailer' => get_option('EmailHelper.mailer', 'php'),
            'smtp_host' => get_option('EmailHelper.smtp_host', 'localhost'),
            'smtp_port' => get_option('EmailHelper.smtp_port', '25'),
            'smtp_ssl' => get_option('EmailHelper.smtp_ssl', 'none'),
            'smtp_auth' => get_option('EmailHelper.smtp_auth', false),
            'smtp_user' => get_option('EmailHelper.smtp_user', ''),
            'smtp_pass' => get_option('EmailHelper.smtp_pass', ''),
        ));
        
    }
    
    public function jqueryUiThemeAction(){
        $themeUrl = InputHelper::getParam('theme_url');
        $theme = InputHelper::getParam('theme');
        if($theme){
            OptionHelper::setOption('jQueryUI.theme', $theme);
            OptionHelper::setOption('jQueryUI.themeUrl', $themeUrl);
//            header('Location: '.$_SERVER['REQUEST_URI']);
        }

        $root = ZF_CORE_PATH.'res/css/jquery-ui/';
        $themes = scandir($root);
        foreach($themes as $key=>$theme){
            if($theme=='.' || $theme=='..' || !is_dir($root.$theme)){
                unset($themes[$key]);
            }
        }
        $themes = array_values($themes);
        $theme = OptionHelper::getOption('jQueryUI.theme', 'smoothness');
        $themeUrl = OptionHelper::getOption('jQueryUI.themeUrl');
  
        $this->view->themes = $themes;
        $this->view->theme = $theme;
        $this->view->themeUrl = $themeUrl;
        wp_enqueue_style('admin-jquery-ui-theme', ZF_CORE_URL.'res/css/bem-admin_jquery_ui_theme.less');
    }
    
    public function phpinfoAction(){
        
    }
    
    public function wpHooksAction(){
        $tables = array();
        $columns = array();
        
        global $wp_filter;
        
        foreach($wp_filter as $tag=>$filters){
            $table = array();
            foreach ($filters as $priority=>$filterSet){
                foreach($filterSet as $func=>$implementation){
                    $function = $implementation['function'];
                    $callback = $function;
                    $ref = '';
                    if(is_array($function)){
                        list($cls, $method) = $function;
                        $delimiter = '::';
                        if(is_object($cls)){
                            $delimiter = '->';
                            $cls = get_class($cls);
                        }
                        $callback = sprintf('%s %s %s', $cls, $delimiter, $method);
                    }else{
//                        $r = $this->ReflectionFunctionFactory($function);
//                        $r = new ReflectionMethod("wpp_BRX_SearchEngine", 'addMetaBoxSearchOptions');
//                        $file = $r->getFileName();
//                        $startLine = $r->getStartLine();
//                        $ref = sprintf('%s (%d)', $file, $startLine);
                    }
                    $table[]=array(
                        'priority' => $priority,
                        'callback' => $callback,
                        'args' => $implementation['accepted_args'],
//                        'reflection' => $ref,
                    );
                }
            }
            usort($table, function($a, $b){
                $a = $a['priority'];
                $b = $b['priority'];
                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
            });
            $tag = urldecode($tag);
            $tables[$tag]=$table;
        }
        
        ksort($tables);
        
        $this->view->tables=$tables;
    }
    
    public function blockadeOptionsAction(){
        wp_enqueue_style('admin-setupForm');
        wp_enqueue_script('backbone-brx-optionsForm');
        
    }
    
}
