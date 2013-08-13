<?php

class TermQueryModel{
    protected $vars = array();
    protected $taxonomies = null;


    public function __construct($taxonomies = null) {
        $this->taxonomies = $taxonomies;
    }
    
    public function getVars(){
        return $this->vars;
    }
    
    /**
     * 
     * @param string $key
     * @param mixed $value
     * @return \TermQueryModel
     */
    public function setVar($key, $value){
        $this->vars[$key] = $value;
        return $this;
    }
    
    /**
     * 
     * @param string|array(string) $taxonomies
     * @return \TermQueryModel
     */
    public static function query($taxonomies = null){
        return new self($taxonomies);
    }
    
    /**
     * Select all matching terms
     * 
     * @param string|array(string) $taxonomies
     * @return array(TermModel)
     */
    public function select($taxonomies = null){
        if(!$taxonomies){
            $taxonomies = $this->taxonomies;
        }
        return TermModel::selectTerms($taxonomies, $this->getVars());
    }
    
    /**
     * Select first matching term
     * 
     * @param string|array(string) $taxonomies
     * @return TermModel
     */
    public function selectOne($taxonomies = null){
        $terms = $this->select($taxonomies);
        return count($terms)?reset($terms):null;
    }
    
    /**
     * Designates the ascending or descending order of the 'orderby' parameter. 
     * Defaults to 'ASC'
     * @param string $order
     * @return \TermQueryModel
     */
    public function order($order){
        return $this->setVar('order', $order);
    }
    
    public function order_ASC(){
        return $this->order('ASC');
    }
   
    public function order_DESC(){
        return $this->order('DESC');
    }
    
    /**
     * Sort retrieved users by parameter. Defaults to 'login'.
     * 
     * @param string $orderBy
     * @return \TermQueryModel
     */
    public function orderBy($orderBy){
        return $this->setVar('orderby', $orderBy);
    }
    
    public function orderBy_ID(){
        return $this->orderBy('id');
    }
    
    public function orderBy_Count(){
        return $this->orderBy('count');
    }

    public function orderBy_Name(){
        return $this->orderBy('name');
    }
    
    public function orderBy_Slug(){
        return $this->orderBy('slug');
    }
    
    public function orderBy_TermGroup(){
        return $this->orderBy('term_group');
    }
    
    public function orderBy_None(){
        return $this->orderBy('none');
    }
    
    /**
     * Whether to return empty $terms
     * @param boolean $hide
     * @return \TermQueryModel
     */
    public function hideEmpty($hide = true){
        return $this->setVar('hide_empty', $hide);
    }
    
    /**
     * Whether to return empty $terms
     * @param boolean $show
     * @return \TermQueryModel
     */
    public function showEmpty($show = true){
        return $this->hideEmpty(!$show);
    }
    
    /**
     * An array of term ids to exclude. Also accepts a string of comma-separated ids.
     * 
     * @param integer|string|array $termIds
     * @return \TermQueryModel
     */
    public function excludeIds($termIds){
        return $this->setVar('exclude', $termIds);
    }
    
    /**
     * An array of parent term ids to exclude
     * 
     * @param integer|string|array $parentTermIds
     * @return \TermQueryModel
     */
    public function excludeTreeIds($parentTermIds){
        return $this->setVar('exclude_tree', $parentTermIds);
    }
    
    /**
     * An array of term ids to include. Empty returns all.
     * 
     * @param integer|string|array $termIds
     * @return \TermQueryModel
     */
    public function includeIds($termIds){
        return $this->setVar('include', $termIds);
    }
    
    /**
     * The maximum number of terms to return. Default is to return them all.
     * 
     * @param int $number
     * @return \TermQueryModel
     */
    public function number($number){
        return $this->setVar('number', $number);
    }
    
    /**
     * Set return values.
     * 
     * @param string|array(string) $fields
     * @return \TermQueryModel
     */
    public function fields($fields){
        return $this->setVar('fields', $fields);
    }
    
    /**
     * all - returns an array of term objects - Default
     * 
     * @return \TermQueryModel
     */
    public function fields_All(){
        return $this->fields('all');
    }
    
    /**
     * ids - returns an array of integers
     * 
     * @return \TermQueryModel
     */
    public function fields_Ids(){
        return $this->fields('ids');
    }
    
    /**
     * names - returns an array of strings
     * 
     * @return \TermQueryModel
     */
    public function fields_Names(){
        return $this->fields('names');
    }
    
    /**
     * count - (3.2+) returns the number of terms found
     * 
     * @return \TermQueryModel
     */
    public function fields_Count(){
        return $this->fields('count');
    }
    
    /**
     * id=>parent - returns an associative array where 
     * the key is the term id and 
     * the value is the parent term id if present or 0
     * 
     * @return \TermQueryModel
     */
    public function fields_ID_ParentId(){
        return $this->fields('id=>parent');
    }
    
    /**
     * Returns terms whose "slug" matches this value. Default is empty string.
     * 
     * @param string $slug
     * @return \TermQueryModel
     */
    public function slug($slug){
        return $this->setVar('slug', $slug);
    }
    
    /**
     * Get direct children of this term (only terms whose explicit parent is this value). 
     * If 0 is passed, only top-level terms are returned. Default is an empty string.
     * 
     * @param int $parentTermId
     * @return \TermQueryModel
     */
    public function parentId($parentTermId){
        return $this->setVar('parent', $parentTermId);
    }
    
    /**
     * Whether to include terms that have non-empty descendants 
     * (even if 'hide_empty' is set to true).
     * 
     * @param type $hierarchical
     * @return \TermQueryModel
     */
    public function hierarchical($hierarchical){
        return $this->setVar('hierarchical', $hierarchical);
    }
    
    public function hierarchical_Yes(){
        return $this->hierarchical(true);
    }
    
    public function hierarchical_No(){
        return $this->hierarchical(false);
    }
    
    /**
     * Get all descendents of this term. Default is 0.
     * 
     * @param int $parentTermId
     * @return \TermQueryModel
     */
    public function childOf($parentTermId){
        return $this->setVar('child_of', $parentTermId);
    }
    
    /**
     * Default is nothing . Allow for overwriting 'hide_empty' and 'child_of', 
     * which can be done by setting the value to 'all'.
     * 
     * @param string $value
     * @return \TermQueryModel
     */
    public function get($value){
        return $this->setVar('get', $value);
    }
    
    /**
     * The term name you wish to match. It does a LIKE 'term_name%' query. 
     * This matches terms that begin with the 
     * 
     * @param string $name
     * @return \TermQueryModel
     */
    public function nameLike($name){
        return $this->setVar('name__like', $name);
    }
    
    /**
     * If true, count all of the children along with the $terms.
     * 
     * @param boolean $count
     * @return \TermQueryModel
     */
    public function padCounts($count = true){
        return $this->setVar('pad_counts', $count);
    }
    
    /**
     * The number by which to offset the terms query.
     *  
     * @param int $offset
     * @return \TermQueryModel
     */
    public function offset($offset){
        return $this->setVar('offset', $offset);
    }
    
    /**
     * The term name you wish to match. It does a LIKE '%term_name%' query. 
     * This matches terms that contain the 'search'  
     * 
     * @param string $name
     * @return \TermQueryModel
     */
    public function search($name){
        return $this->setVar('search', $name);
    }
    
    /**
     * Version 3.2 and above. The 'cache_domain' argument enables a unique cache key 
     * to be produced when the query produced by get_terms() is stored in object cache. 
     * For instance, if you are using one of this function's filters to modify the query 
     * (such as 'terms_clauses'), setting 'cache_domain' to a unique value will not 
     * overwrite the cache for similar queries. Default value is 'core'.
     * 
     * @param string $domain
     * @return \TermQueryModel
     */
    public function cacheDomain($domain = 'core'){
        return $this->setVar('cache_domain', $domain);
    }
    
    
}
    
class PostTermQueryModel {
    protected $vars = array();
    protected $taxonomies = null;
    protected $post = null;


    public function __construct($post = null, $taxonomies = null) {
        $this->taxonomies = $taxonomies;
        $this->post = $post;
    }
    
    public function getVars(){
        return $this->vars;
    }
    
    /**
     * 
     * @param string $key
     * @param mixed $value
     * @return \PostTermQueryModel
     */
    public function setVar($key, $value){
        $this->vars[$key] = $value;
        return $this;
    }
    
    /**
     * 
     * @param string|array(string) $taxonomies
     * @return \PostTermQueryModel
     */
    public static function query($post = null, $taxonomies = null){
        return new self($post, $taxonomies);
    }
    
    /**
     * 
     * @param string|array(string) $taxonomies
     * @return array(TermModel)
     */
    public function select($post = null, $taxonomies = null){
        if(!$post){
            $post = $this->post;
        }
        if(!$taxonomies){
            $taxonomies = $this->taxonomies;
        }
        return $post->loadTerms($taxonomies, $this->getVars());
    }
    
    /**
     * Select first matching term
     * 
     * @param string|array(string) $taxonomies
     * @return TermModel
     */
    public function selectOne($taxonomies = null){
        $terms = $this->select();
        return count($terms)?reset($terms):null;
    }
    
    /**
     * Designates the ascending or descending order of the 'orderby' parameter. 
     * Defaults to 'ASC'
     * @param string $order
     * @return \PostTermQueryModel
     */
    public function order($order){
        return $this->setVar('order', $order);
    }
    
    public function order_ASC(){
        return $this->order('ASC');
    }
   
    public function order_DESC(){
        return $this->order('DESC');
    }
    
    /**
     * Sort retrieved users by parameter. Defaults to 'login'.
     * 
     * @param string $orderBy
     * @return \PostTermQueryModel
     */
    public function orderBy($orderBy){
        return $this->setVar('orderby', $orderBy);
    }
    
    public function orderBy_ID(){
        return $this->orderBy('id');
    }
    
    public function orderBy_Count(){
        return $this->orderBy('count');
    }

    public function orderBy_Name(){
        return $this->orderBy('name');
    }
    
    public function orderBy_Slug(){
        return $this->orderBy('slug');
    }
    
    public function orderBy_TermOrder(){
        return $this->orderBy('term_order');
    }
    
    public function orderBy_TermGroup(){
        return $this->orderBy('term_group');
    }
    
    public function orderBy_None(){
        return $this->orderBy('none');
    }
    
    /**
     * Set return values.
     * 
     * @param string|array(string) $fields
     * @return \PostTermQueryModel
     */
    public function fields($fields){
        return $this->setVar('fields', $fields);
    }
    
    /**
     * all - returns an array of term objects - Default
     * 
     * @return \PostTermQueryModel
     */
    public function fields_All(){
        return $this->fields('all');
    }
    
    /**
     * all - returns an array of term objects - Default
     * 
     * @return \PostTermQueryModel
     */
    public function fields_AllWithObjectId(){
        return $this->fields('all_with_object_id');
    }
    
    /**
     * ids - returns an array of integers
     * 
     * @return \PostTermQueryModel
     */
    public function fields_Ids(){
        return $this->fields('ids');
    }
    
    /**
     * names - returns an array of strings
     * 
     * @return \PostTermQueryModel
     */
    public function fields_Names(){
        return $this->fields('names');
    }
    
    /**
     * slugs - returns an array of strings
     * 
     * @return \PostTermQueryModel
     */
    public function fields_Slugs(){
        return $this->fields('slugs');
    }
}
