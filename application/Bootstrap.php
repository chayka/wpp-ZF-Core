<?php

class ZFCore_Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    const MODULE = 'ZFCore';

    public function run(){
        $this->setupRouting();
        $front = Util::getFront();
        WpHelper::getInstance();
        parent::run();
    }
    
    public function setupRouting(){
        $front = Util::getFront();
        $cd = $front->getControllerDirectory();
        $front->addControllerDirectory($cd['default'], self::MODULE);        
        
        $router = $front->getRouter();

        $router->addRoute(self::MODULE, new Zend_Controller_Router_Route(':controller/:action/*', array('controller' => 'index', 'action'=>'index', 'module'=>self::MODULE)));
        $router->addRoute('autocomplete-taxonomy', new Zend_Controller_Router_Route('autocomplete/taxonomy/:taxonomy', array('controller' => 'autocomplete', 'action'=>'taxonomy', 'module'=>self::MODULE)));

//        $router->addRoute('api', new Zend_Controller_Router_Route('api/:controller/:action', array('controller' => 'index', 'action'=>'index')));
//
//        $router->addRoute('questions', new Zend_Controller_Router_Route('questions/:mode/:page', array('controller' => 'question', 'action'=>'questions', 'mode'=>'', 'page'=>1)));
//        $router->addRoute('unanswered', new Zend_Controller_Router_Route('unanswered/:mode/:page', array('controller' => 'question', 'action'=>'unanswered', 'mode'=>'', 'page'=>1)));
//        $router->addRoute('view-question', new Zend_Controller_Router_Route('question/:id/:slug/:mode/:type/:item', array('controller' => 'question', 'action'=>'view', 'slug'=>'', 'mode'=>'', 'type' => 'question', 'item'=>0 )));
//        $router->addRoute('ask-question', new Zend_Controller_Router_Route('ask-question/', array('controller' => 'question', 'action'=>'ask')));
//
//        $router->addRoute('users', new Zend_Controller_Router_Route('users/:mode/:page', array('controller' => 'user', 'action'=>'users', 'mode'=>'', 'page'=>1)));
//        $router->addRoute('view-user', new Zend_Controller_Router_Route('user/:id/:login/:tab/:page', array('controller' => 'user', 'action'=>'profile', 'id'=>0, 'login'=>'', 'tab'=>'basic', 'page'=>1)));
//
//        $router->addRoute('articles', new Zend_Controller_Router_Route('articles/:mode/:page', array('controller' => 'article', 'action'=>'articles', 'mode'=>'new', 'page'=>1)));
//        $router->addRoute('view-article', new Zend_Controller_Router_Route('article/:id/:slug/', array('controller' => 'article', 'action'=>'article', 'id'=>0, 'login'=>'')));
//
//        $router->addRoute('tags', new Zend_Controller_Router_Route('tags/:taxonomy/:mode', array('controller' => 'tag', 'action'=>'tags', 'taxonomy'=>null,'mode'=>'new')));
//        $router->addRoute('tag', new Zend_Controller_Router_Route('tag/:taxonomy/:term/:scope/:mode/:page', array('controller' => 'tag', 'action'=>'tag', 'scope'=>'all','mode'=>'new', 'page'=>1)));
//
//        $router->addRoute('search', new Zend_Controller_Router_Route('search/:scope/', array('controller' => 'search', 'action'=>'lucene', 'scope'=>'all')));
//        $router->addRoute('profession', new Zend_Controller_Router_Route('profession/:term/:scope/:mode/:page', array('controller' => 'tag', 'action'=>'tag', 'taxonomy'=>'profession','scope'=>'questions','mode'=>'new', 'page'=>1)));
//        $router->addRoute('professions', new Zend_Controller_Router_Route('professions/:page/', array('controller' => 'tag', 'action'=>'tags', 'taxonomy'=>'profession', 'page'=>1)));
//
//        $router->addRoute('work', new Zend_Controller_Router_Route('work/:term/:scope/:mode/:page', array('controller' => 'tag', 'action'=>'tag', 'taxonomy'=>'work','scope'=>'questions','mode'=>'new', 'page'=>1)));
//        $router->addRoute('works', new Zend_Controller_Router_Route('works/:page/', array('controller' => 'tag', 'action'=>'tags', 'taxonomy'=>'work', 'page'=>1)));
//
//        $router->addRoute('equipment', new Zend_Controller_Router_Route('equipment/:term/:scope/:mode/:page', array('controller' => 'tag', 'action'=>'tag', 'taxonomy'=>'equipment','scope'=>'questions','mode'=>'new', 'page'=>1)));
//        $router->addRoute('equipments', new Zend_Controller_Router_Route('equipments/:page/', array('controller' => 'tag', 'action'=>'tags', 'taxonomy'=>'equipment', 'page'=>1)));
//
//        $router->addRoute('location', new Zend_Controller_Router_Route('location/:term/:scope/:mode/:page', array('controller' => 'tag', 'action'=>'tag', 'taxonomy'=>'location','scope'=>'questions','mode'=>'new', 'page'=>1)));
//        $router->addRoute('locations', new Zend_Controller_Router_Route('locations/:page/', array('controller' => 'tag', 'action'=>'tags', 'taxonomy'=>'location', 'page'=>1)));
//
//        $router->addRoute('material', new Zend_Controller_Router_Route('material/:term/:scope/:mode/:page', array('controller' => 'tag', 'action'=>'tag', 'taxonomy'=>'material','scope'=>'questions','mode'=>'new', 'page'=>1)));
//        $router->addRoute('materials', new Zend_Controller_Router_Route('materials/:page/', array('controller' => 'tag', 'action'=>'tags', 'taxonomy'=>'material', 'page'=>1)));

//        $router->addRoute('publish-answer', new Zend_Controller_Router_Route('publish-answer/', array('controller' => 'answer', 'action'=>'publish')));
//        $router->addRoute('my', new Zend_Controller_Router_Route('profiles/:login/', array('controller' => 'user', 'action'=>'profile')));
    /*    $router->addRoute('404', new Zend_Controller_Router_Route('404/', array('controller' => 'error', 'action'=>'error404')));
        $router->addRoute('movies', new Zend_Controller_Router_Route('movies/:action/', array('controller' => 'movies', 'action'=>'index')));
        $router->addRoute('tasks', new Zend_Controller_Router_Route('tasks/:action/:id', array('controller' => 'tasks', 'action'=>'index', 'id'=>'0')));
        $router->addRoute('crawler', new Zend_Controller_Router_Route('crawler/:action/', array('controller' => 'crawler', 'action'=>'index')));
        $router->addRoute('download', new Zend_Controller_Router_Route('download/:id', array('controller' => 'search', 'action'=>'download', 'id'=>'0')));
        $router->addRoute('home', new Zend_Controller_Router_Route('home/', array('controller' => 'index', 'action'=>'home')));
        $router->addRoute('help', new Zend_Controller_Router_Route('help/', array('controller' => 'index', 'action'=>'help')));
        $router->addRoute('help-advanced', new Zend_Controller_Router_Route('help-advanced/', array('controller' => 'index', 'action'=>'help-advanced')));
        $router->addRoute('about', new Zend_Controller_Router_Route('about/', array('controller' => 'index', 'action'=>'about')));
        $router->addRoute('cloud', new Zend_Controller_Router_Route('cloud/', array('controller' => 'index', 'action'=>'cloud')));
        $router->addRoute('feedback', new Zend_Controller_Router_Route('feedback/', array('controller' => 'index', 'action'=>'feedback')));
        $router->addRoute('agreement', new Zend_Controller_Router_Route('agreement/', array('controller' => 'index', 'action'=>'agreement')));
        $router->addRoute('dle', new Zend_Controller_Router_Route_Regex('^([\w\d_]+)\.php', array('controller' => 'index', 'action'=>'dle')));*/
    }

}

