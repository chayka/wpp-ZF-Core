<?php

class PaginationModel{
    protected $totalPages;
    protected $currentPage;
    protected $packSize = 10;
    protected $pageLinkPattern = '/page/.page.';
    
    public function getTotalPages() {
        return $this->totalPages;
    }

    public function setTotalPages($totalPages) {
        $this->totalPages = $totalPages;
    }

    public function getCurrentPage() {
        return $this->currentPage;
    }

    public function setCurrentPage($currentPage) {
        $this->currentPage = $currentPage;
    }

    public function getPackSize() {
        return $this->packSize;
    }

    public function setPackSize($packSize) {
        $this->packSize = $packSize;
    }

    public function getPageLinkPattern() {
        return $this->pageLinkPattern;
    }

    public function setPageLinkPattern($pageLinkPattern) {
        $this->pageLinkPattern = $pageLinkPattern;
    }

    public function getPackFirstPage(){
        return ceil($this->getCurrentPage() / $this->getPackSize()-1) * $this->getPackSize() + 1;
    }
    
    public function getPackLastPage(){
        return ceil($this->getCurrentPage() / $this->getPackSize()) * $this->getPackSize();
    }
    
    public function pageExists($page){
        return $page > 0 && $page <= $this->getTotalPages();
    }

    public function getPageLink($page){
        return $this->pageExists($page)?
                str_replace('.page.', $page, $this->getPageLinkPattern()):null;
    }
    
    public function getPreviousPageLink(){
        $page = $this->getCurrentPage() - 1;
        return $this->getPageLink($page);
    }
    
    public function getNextPageLink(){
        $page = $this->getCurrentPage() + 1;
        return $this->getPageLink($page);
    }
    
    public function getPreviousPackLink(){
        $page = $this->getPackFirstPage() - 1;
        return $this->getPageLink($page);
    }
    
    public function getNextPackLink(){
        $page = $this->getPackLastPage() + 1;
        return $this->getPageLink($page);
    }
    
    public function render(){
//    public static function sendTemplate($subject, $template, $params, $to, $from = '', $cc = '', $bcc = ''){
        $html = new Zend_View();
        $html->setScriptPath(ZF_CORE_APPLICATION_PATH . '/views/scripts/posts/');

        $html->assign('model', $this);
        
        return $html->render('pagination.phtml');
    }
}   