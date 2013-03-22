<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AutocompleteController
 *
 * @author borismossounov
 */
class ZFCore_AutocompleteController extends Zend_Controller_Action{

    public function init(){
        Util::turnRendererOff();
    }

    public function taxonomyAction(){
        global $wpdb;
        $taxonomy = InputHelper::getParam('taxonomy');
        $term = InputHelper::getParam('term');
        $results = $term ? $wpdb->get_results($wpdb->prepare("
                SELECT t.term_id AS id, t.term_id AS value, t.name AS label FROM $wpdb->term_taxonomy AS tt 
                LEFT JOIN $wpdb->terms AS t USING(term_id)
                WHERE taxonomy = %s AND name LIKE %s", $taxonomy, "$term%"
        )):array();
        die( JsonHelper::encode($results));
    }
    
    public function userAction(){
        global $wpdb;
        $term = InputHelper::getParam('term');
        $users = UserModel::selectUsers("search=$term*");
        $results = array();
        foreach($users as $user){
            $results[] = array(
                'id' => $user->getId(),
                'value' => $user->getLogin(),
                'label' => $user->getLogin().' : '.$user->getEmail(),
//                'user' => $user
            );
        }
        die( JsonHelper::encode($results));
    }
    
}