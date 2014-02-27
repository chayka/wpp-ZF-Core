<?php

require_once 'application/helpers/WpDbHelper.php';
require_once 'application/helpers/JsonHelper.php';
require_once 'application/helpers/InputHelper.php';
require_once 'application/models/taxonomies/TermQueryModel.php';
//require_once 'application/helpers/LuceneHelper.php';

class TermModel implements DbRecordInterface, JsonReadyInterface, InputReadyInterface /*, LuceneReadyInterface*/{

    protected $wpTerm;
//    protected $wpRelation;
    
    public function __construct() {
        $this->wpTerm = new stdClass();
        $this->wpRelation = new stdClass();
    }
    
    public static function getDbIdColumn() {
        return 'term_id';
    }

    public static function getDbTable() {
        global $wpdb;
        return $wpdb->term_taxonomy;
    }

    public function getId() {
        return $this->getRelationId();
    }

    public function setId($val){
        return $this->setRelationId($val);
    }
    
    public function getTermId() {
        return Util::getItem($this->wpTerm, 'term_id', 0);
    }

    public function setTermId($val){
        $this->wpTerm->term_id = intval($val);
        return $this;
    }
    
    public function getName() {
        return Util::getItem($this->wpTerm, 'name', '');
    }

    public function setName($val){
        $this->wpTerm->name = $val;
        return $this;
    }
    
    public function getSlug() {
        return Util::getItem($this->wpTerm, 'slug', '');
    }

    public function setSlug($val){
        $this->wpTerm->slug = $val;
        return $this;
    }
    
    public function getGroup() {
        return Util::getItem($this->wpTerm, 'term_group', 0);
    }

    public function setGroup($val){
        $this->wpTerm->term_group = intval($val);
        return $this;
    }
    
    public function getRelationId() {
        return Util::getItem($this->wpTerm, 'term_taxonomy_id', 0);
    }

    public function setRelationId($val){
        $this->wpTerm->term_taxonomy_id = intval($val);
        return $this;
    }
    
    public function getTaxonomy() {
        return Util::getItem($this->wpTerm, 'taxonomy', '');
    }

    public function setTaxonomy($val){
        $this->wpTerm->taxonomy = $val;
        return $this;
    }
    
    public function getDescription() {
        return Util::getItem($this->wpTerm, 'description', '');
    }

    public function setDescription($val){
        $this->wpTerm->description = $val;
        return $this;
    }
    
    public function getParentId() {
        return Util::getItem($this->wpTerm, 'parent', 0);
    }

    public function setParentId($val){
        $this->wpTerm->parent = intval($val);
        return $this;
    }
    
    public function getCountPerTaxonomy() {
        return Util::getItem($this->wpTerm, 'count', 0);
    }

    public function setCountPerTaxonomy($val){
        $this->wpTerm->count = intval($val);
        return $this;
    }
    
    public function getHref(){
        return get_term_link($this->wpTerm, $this->getTaxonomy());
    }
    
    public function __get($name) {
        return Util::getItem($this->wpTerm, $name);
    }

//    public function __set($name, $value) {
//        $this->wpTerm->$name = $value;
//    }

    
    public function insert() {
        if(!$this->getId() && !$this->getName()){
            throw new Exception('TermModel: no term set', 1);
        }
        if(!$this->getTaxonomy()){
            throw new Exception('TermModel: no taxonomy set', 2);
        }
        $res = wp_insert_term($this->getId()?$this->getId():$this->getName(), 
                $this->getTaxonomy(), 
                $this->packDbRecord());
        if(is_wp_error($res)){
            throw new Exception($res->get_error_message(), $res->get_error_code());
        }
        return is_wp_error($res)?null:$res->term_taxonomy_id;
    }

    public function update() {
        if(!$this->getId()){
            throw new Exception('TermModel: no term set', 1);
        }
        if(!$this->getTaxonomy()){
            throw new Exception('TermModel: no taxonomy set', 2);
        }
        $res = wp_insert_term($this->getId(), 
                $this->getTaxonomy(), 
                $this->packDbRecord(true));
        if(is_wp_error($res)){
            throw new Exception($res->get_error_message()/*, $res->get_error_code()*/);
        }
        return is_wp_error($res)?null:$res->term_taxonomy_id;
    }

    public function delete($args = array()) {
        if(!$this->getTaxonomy()){
            throw new Exception('TermModel: no taxonomy set', 2);
        }
        $res = wp_delete_term($this->getId(), $this->getTaxonomy(), $args);
        if(is_wp_error($res)){
            throw new Exception($res->get_error_message(), $res->get_error_code());
        }
        return is_wp_error($res)?null:$res;
    }

    public function packDbRecord($forUpdate = false) {
        $dbRecord = array(
            'description' => $this->getDescription(),
            'slug' => $this->getSlug(),
            'parent' => $this->getParentId(),
        );
        if($forUpdate){
            $dbRecord['name'] = $this->getName();
            $dbRecord['term_group'] = $this->getGroup();
        }
        
        return $dbRecord;
    }

    /**
     * 
     * @param type $dbRecord
     * @return TermModel
     */
    public static function unpackDbRecord($dbRecord) {
        $obj = new self();
        $obj->setTermId(Util::getItem($dbRecord, 'term_id', 0));
        $obj->setName(Util::getItem($dbRecord, 'name'));
        $obj->setSlug(Util::getItem($dbRecord, 'slug'));
        $obj->setGroup(Util::getItem($dbRecord, 'term_group', 0));
        $obj->setRelationId(Util::getItem($dbRecord, 'term_taxonomy_id', 0));
        $obj->setTaxonomy(Util::getItem($dbRecord, 'taxonomy'));
        $obj->setDescription(Util::getItem($dbRecord, 'description'));
        $obj->setParentId(Util::getItem($dbRecord, 'parent', 0));
        $obj->setCountPerTaxonomy(Util::getItem($dbRecord, 'count', 0));
        
        return $obj;
    }

    /**
     * 
     * @global type $wpdb
     * @param int $id
     * @param boolean $useCache
     * @return TermModel
     */
    public static function selectById($id, $useCache = true) {
        global $wpdb;
        $t1 = $wpdb->term_taxonomy;
        $t2 = $wpdb->terms;
        $sql = $wpdb->prepare("
            SELECT *
            FROM $t1 LEFT JOIN $t2 USING(term_id)
            WHERE term_taxonomy_id = %d
            ", $id);
        $dbRecord = $wpdb->get_row($sql);
        return self::unpackDbRecord($dbRecord);
    }
    
    /**
     * 
     * @param string $field
     * @param string $value
     * @param string $taxonomy
     * @param const $output
     * @param string $filter
     * @return TermModel
     */
    public static function selectBy($field, $value, $taxonomy, $output = OBJECT, $filter = 'raw'){
        $dbRecord = get_term_by($field, $value, $taxonomy, $output, $filter);
        return $dbRecord?self::unpackDbRecord($dbRecord):null;
    }

    /**
     * 
     * @param int $value
     * @param string $taxonomy
     * @param const $output
     * @param string $filter
     * @return TermModel
     */
    public static function selectByTermId($value, $taxonomy, $output = OBJECT, $filter = 'raw'){
        return self::selectBy('id', $value, $taxonomy, $output, $filter);
    }

    /**
     * 
     * @param string $value
     * @param string $taxonomy
     * @param const $output
     * @param string $filter
     * @return TermModel
     */
    public static function selectBySlug($value, $taxonomy, $output = OBJECT, $filter = 'raw'){
        return self::selectBy('slug', $value, $taxonomy, $output, $filter);
    }
    
    /**
     * 
     * @param string $value
     * @param string $taxonomy
     * @param const $output
     * @param string $filter
     * @return TermModel
     */
    public static function selectByName($value, $taxonomy, $output = OBJECT, $filter = 'raw'){
        return self::selectBy('name', $value, $taxonomy, $output, $filter);
    }
    
    /**
     * 
     * @param type $taxonomies
     * @param type $args
     * @return array(TermModel)
     */
    public static function selectTerms($taxonomies, $args){
        $dbRecords = get_terms($taxonomies, $args);
        $terms = array();
        foreach($dbRecords as $dbRecord){
            $term = self::unpackDbRecord($dbRecord);
            if($term){
                $terms[]=$term;
            }
        }
        return $terms;
    }
    
    public static function query($taxonomies = null){
        return new TermQueryModel($taxonomies);
    }
    
    public static function queryPostTerms($post, $taxonomies){
        return new PostTermQueryModel($post, $taxonomies);
    }

    public function getValidationErrors() {
        
    }

    public function unpackInput($input = array()) {
        if(empty($input)){
            $input = InputHelper::getParams();
        }
        $input = array_merge($this->packJsonItem(), $input);

        $this->setId(Util::getItem($input, 'id', 0));
        $this->setTermId(Util::getItem($input, 'term_id'));
        $this->setName(Util::getItem($input, 'name'));
        $this->setSlug(Util::getItem($input, 'slug'));
        $this->setGroup(Util::getItem($input, 'term_group', 0));
        $this->setTaxonomy(Util::getItem($input, 'taxonomy'));
        $this->setDescription(Util::getItem($input, 'daescription'));
        $this->setParentId(Util::getItem($input, 'parent', 0));
//        $this->setCountPerTaxonomy(Util::getItem($input, 'count', 0));
        return $this;
    }

    public function validateInput($input = array(), $action = 'create') {
        
    }

    public function packJsonItem() {
        return array(
            'id' => $this->getRelationId(),
            'term_id'=>$this->getTermId(),
            'name'=>$this->getName(),
            'slug'=>$this->getSlug(),
            'term_group'=>$this->getGroup(),
            'taxonomy'=>$this->getTaxonomy(),
            'description'=>$this->getDescription(),
            'parent'=>$this->getParentId(),
            'count'=>$this->getCountPerTaxonomy(),
        );
    }

}

