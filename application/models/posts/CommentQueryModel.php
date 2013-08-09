<?php

class CommentQueryModel{
    
    protected $vars = array();
    
    public function __construct() {
        ;
    }
    
    public function getVars(){
        return $this->vars;
    }
    
    /**
     * 
     * @param type $key
     * @param type $value
     * @return \CommentQueryModel
     */
    public function setVar($key, $value){
        $this->vars[$key] = $value;
        return $this;
    }
    
    /**
     * @param int $postId postId
     * @return \CommentQueryModel
     */
    public static function query($postId = 0){
        $q  = new self();
        if($postId){
            $q->postId($postId);
        }
        return $q;
    }
    
    public function select(){
        return CommentModel::selectComments($this->getVars());
    }
    
    /**
     * Only return comments with this status.
     * 
     * @param string $status
     * @return \CommentQueryModel
     */
    public function status($status){
        return $this->setVar('status', $status);
    }
    
    public function status_Hold(){
        return $this->status('hold');
    }
    
    public function status_Approve(){
        return $this->status('approve');
    }
    
    public function status_Spam(){
        return $this->status('spam');
    }
    
    public function status_Trash(){
        return $this->status('trash');
    }
    
    /**
     * Number of comments to return. Leave blank to return all comments.
     * 
     * @param int $number
     * @return \CommentQueryModel
     */
    public function number($number){
        return $this->setVar('number', $number);
    }
    
    /**
     * Offset from latest comment. You must include $number along with this.
     * @param int $offset
     * @return \CommentQueryModel
     */
    public function offset($offset){
        return $this->setVar('offset', $offset);
    }
    
    /**
     * How to sort $orderby. Valid values: ASC, DESC
     * Default: DESC
     * @param string $order
     * @return \CommentQueryModel
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
     * Set the field used to sort comments.
     * Default: comment_date_gmt
     * 
     * @param string $orderBy
     * @return \CommentQueryModel
     */
    public function orderBy($orderBy){
        return $this->setVar('orderby', $orderBy);
    }
    
    /**
     * Only return comments for a particular post or page.
     * 
     * @param int $postId
     * @return \CommentQueryModel
     */
    public function postId($postId){
        return $this->setVar('post_id', $postId);
    }
    
    /**
     * Only return comments for a particular user.
     * 
     * @param int $userId
     * @return \CommentQueryModel
     */
    public function userId($userId){
        return $this->setVar('user_id', $userId);
    }
    
    /**
     * Only return the total count of comments.
     * 
     * @param boolean $countOnly
     * @return type
     */
    public function returnCountOnly($countOnly = 1){
        return $this->setVar('count', $countOnly);
    }
    
    /**
     * Custom field key.
     * 
     * @param string $key
     * @return \CommentQueryModel
     */
    public function metaKey($key){
        return $this->setVar('meta_key', $key);
    }
    
    /**
     * Custom field value
     * 
     * @param string $value
     * @return \CommentQueryModel
     */
    public function metaValue($value){
        return $this->setVar('meta_value', $value);
    }
    
    /**
     * Custom field numeric value
     * 
     * @param number $value
     * @return \CommentQueryModel
     */
    public function metaValueNum($value){
        return $this->setVar('meta_value_num', $value);
    }
    
    /**
     * Operator to test the 'meta_value'. 
     * Possible values are '!=', '>', '>=', '<', or '<='. Default value is '='.
     * 
     * @param string $compare
     * @return \CommentQueryModel
     */
    public function metaCompare($compare){
        return $this->setVar('meta_compare', $compare);
    }
    
    /**
     * Custom field parameters (available with Version 3.5).
     * 
     * @param string $key Custom field key
     * @param string|array $value Custom field value 
     * (Note: Array support is limited to a compare value of 
     * 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS' or 'NOT EXISTS')
     * @param string $compare Operator to test. Possible values are 
     * '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 
     * 'BETWEEN', 'NOT BETWEEN', 'EXISTS', and 'NOT EXISTS'. 
     * Default value is '='.
     * @param string $type Custom field type. Possible values are 
     * 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 
     * 'TIME', 'UNSIGNED'. Default value is 'CHAR'.
     * @return \CommentQueryModel
     */
    public function metaQuery($key, $value, $compare = '=', $type = 'CHAR'){
        $taxQuery = array(
            'key' => $key,
            'value' => $value,
            'compare' => $compare,
            'type' => $type,
        );
        
        $this->vars['meta_query'][]=$taxQuery;
        
        return $this;
    }

    /**
     * Set relation for multiple meta_query handling
     * Should come first before metaQuery() call
     *  
     * @param string $relation
     * @return \CommentQueryModel
     */
    public function metaQueryRelation($relation){
        $this->vars['meta_query']['relation']=$relation;
        
        return $this;
    }
    
    public function metaQueryRelation_AND(){
        return $this->metaQueryRelation('AND');
    }
    
    public function metaQueryRelation_OR(){
        return $this->metaQueryRelation('OR');
    }
    
}
