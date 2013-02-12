<?php

require_once 'Zend/Db/Adapter/Mysqli.php';

interface DbRecordInterface{
    public static function unpackDbRecord($dbRecord);
    public function packDbRecord();
    public function insert();
    public function update();
    public function delete();
}

class Zend_Db_Adapter_Wpdb extends Zend_Db_Adapter_Mysqli{

    public function __construct($config) {
        global $wpdb;
        parent::__construct($config);
        $this->_connection = $wpdb->dbh;
    }
}

class WpDbHelper {

    const TABLE_DOMAINS = "domains";
    const TABLE_CLIENTS = "clients";
    const TABLE_REQUESTS =  "requests";
    const TABLE_TOC = "toc";
    
    const TABLE_USERS = "users";
    const TABLE_STATS = "stats";
    const TABLE_PAYMENTS = "payments";
    const TABLE_TICKETS = "tickets";
    const TABLE_SUBS = "subs";
    const TABLE_SMS_CMC24 = "sms_cmc24";
    const TABLE_SMS_A1PAY = "sms_a1pay";
    const TABLE_MOVIES = 'movies';
    const TABLE_MOVIE_CATEGORIES = 'categories';
    const TABLE_DOWNLOADS = 'downloads';
    
    /**
     *
     * @return Zend_Db_Adapter_Mysqli 
     */
    public static function getAdapter() {
        global $wpdb;
        if (!Zend_Registry::isRegistered('db')) {
//            $dbConfig = new Zend_Config_Xml(PathHelper::getDatabaseConfigFilename(), 'database', true);
//            $db = Zend_Db::factory($dbConfig);
            $db = new Zend_Db_Adapter_Wpdb(array(
                'host' => 'localhost',
                'dbname' => 'wp',
                'username' => 'wp',
                'password' => 'wp'
            ));
//            print_r()
//            die('(@)');
            $db->query('set names utf8');
            $db->query('set collation_connection=utf8_general_ci');
            $db->setFetchMode(Zend_Db::FETCH_ASSOC);
            Zend_Db_Table_Abstract::setDefaultAdapter($db);
            Zend_Registry::set('db', $db);
        }
        return Zend_Registry::get('db');
    }

    public static function close() {
        if (Zend_Registry::isRegistered('db')) {
            Zend_Registry::get('db')->closeConnection();
            Zend_Registry::set('db', null);
        }
    }
    
    /**
     * Returns Zend_Db_Select of domains table
     * @return Zend_Db_Select
     */
    public static function selectDomains($withInfo = false){
        $select = self::getAdapter()->select()
                ->from(array('d' => self::TABLE_DOMAINS));
        if($withInfo){
            $sources = array(
                CrawledAlexaDataModel::SOURCE_PREFIX => CrawledAlexaDataModel::DB_TABLE,
                CrawledCompeteDataModel::SOURCE_PREFIX => CrawledCompeteDataModel::DB_TABLE,
                CrawledDmozDataModel::SOURCE_PREFIX => CrawledDmozDataModel::DB_TABLE,
                CrawledDomainsAvailbilityDataModel::SOURCE_PREFIX => CrawledDomainsAvailbilityDataModel::DB_TABLE,
                CrawledGoogleDataModel::SOURCE_PREFIX => CrawledGoogleDataModel::DB_TABLE,
                CrawledMyWebsitePriceModel::SOURCE_PREFIX => CrawledMyWebsitePriceModel::DB_TABLE,
                CrawledSemrushDataModel::SOURCE_PREFIX => CrawledSemrushDataModel::DB_TABLE,
                CrawledSiteDataModel::SOURCE_PREFIX => CrawledSiteDataModel::DB_TABLE,
                CrawledWhoisDataModel::SOURCE_PREFIX => CrawledWhoisDataModel::DB_TABLE,
                CrawledYahooDataModel::SOURCE_PREFIX => CrawledYahooDataModel::DB_TABLE,

            );
            foreach ($sources as $prefix => $table){
                $select->joinLeft(array($prefix => $table), "d.domainId = $prefix.domainId", 
                        array($prefix.'Data' => $prefix.'.data', $prefix.'Date' => $prefix.'.date', $prefix.'Status' => $prefix.'.status'));
            }
        }
        return $select;
    }

    /**
     * Returns Zend_Db_Select of crawled data
     * @return Zend_Db_Select
     */
    public static function selectCrawledData($dbTable){
        return self::getAdapter()->select()
                ->from(array('cd' => $dbTable));
    }

    /**
     * Returns Zend_Db_Select of clients table
     * @return Zend_Db_Select
     */
    public static function selectClients(){
        return self::getAdapter()->select()
                ->from(array('c' => self::TABLE_CLIENTS));
    }

    /**
     * Returns Zend_Db_Select of movies table
     * @return Zend_Db_Select
     */
    public static function selectRequests(){
        return self::getAdapter()->select()
                ->from(array('r' => self::TABLE_REQUESTS));
    }
    
    public static function fetchToc(){
        $db = DbHelper::getAdapter();
        $vars = ConfigHelper::getVars();
        $tocSize = $db->fetchOne("SELECT COUNT(*) FROM ".self::TABLE_TOC);
        $lastTocGenerated = !empty($vars->dates->lastTocGenerated)?
                DateHelper::dbStrToDatetime($vars->dates->lastTocGenerated):null;
        $lastUpdate = !empty($vars->dates->lastCrawled)?
                DateHelper::dbStrToDatetime($vars->dates->lastTocGenerated):null;
        $table = array();
        if(!$tocSize || !($lastTocGenerated && $lastUpdate) || $lastUpdate->compare($lastTocGenerated)>0){
            $x = 100;
            $select = $db->select()
                    ->from(DbHelper::TABLE_DOMAINS)
                    ->reset('columns')
                    ->columns(array('abb' => 'LEFT(`url`, 1)', 'count' =>'count(*)', 'pages' => "CEIL(COUNT(*) / $x)"))
                    ->where("`url` < 'a'")
                    ->where("`status` = ?", DomainModel::STATUS_CRAWLED)
                    ->group('abb');
            try{
            $table = $select->query()->fetchAll();
            }catch(Exception $e){
                Log::exception($e);
            }
            $select = $db->select()
                    ->from(DbHelper::TABLE_DOMAINS)
                    ->reset('columns')
                    ->columns(array('abb' => 'LEFT(`url`, 2)', 'count' =>'count(*)', 'pages' => "CEIL(COUNT(*) / $x)"))
                    ->where("`url` > 'a'")
                    ->where("`status` = ?", DomainModel::STATUS_CRAWLED)
                    ->group('abb');
            try{
            $table += $select->query()->fetchAll();
            }catch(Exception $e){
                Log::exception($e);
            }

            $db->query("TRUNCATE TABLE `".DbHelper::TABLE_TOC."`");
            foreach ($table as $row){
                $db->insert(DbHelper::TABLE_TOC, $row);
            }
            if(!$lastUpdate){
                $vars->dates->lastCrawled = DateHelper::dateToDbStr(new Zend_Date());
            }
            $vars->dates->lastTocGenerated = DateHelper::dateToDbStr(new Zend_Date());
            ConfigHelper::updateVars();
        }else{
            $table = $db->select()->from(array('toc'=>self::TABLE_TOC))->query()->fetchAll();
        }
        return $table;        
    }

    /**
     * Returns Zend_Db_Select of categories table
     * @return Zend_Db_Select
     */
    public static function selectMovieCategories(){
        return self::getAdapter()->select()->from(array('mc' => self::TABLE_MOVIE_CATEGORIES));
    }

    /**
     * Returns Zend_Db_Select of movies table
     * @return Zend_Db_Select
     */
    public static function selectCategoryMovies(){
        return self::getAdapter()->select()
                ->from(array('mc' => self::TABLE_MOVIE_CATEGORIES))
                ->joinLeft(array('m' => self::TABLE_MOVIES), 'mc.categoryId = m.categoryId')
                ->where("m.categoryId IS NOT NULL")
                ->group("mc.categoryId");
    }
    
    /**
     * Returns Zend_Db_Select of downloads table
     * @return Zend_Db_Select
     */
    public static function selectDownloads(){
        return self::getAdapter()->select()
                ->from(array('d' => self::TABLE_DOWNLOADS))
                ->joinLeft(array('m' => self::TABLE_MOVIES), 'd.movieId = m.movieId')
                ->joinLeft(array('mc' => self::TABLE_MOVIE_CATEGORIES), 'm.categoryId = mc.categoryId', array('category'));
    }
    
    /**
     * Returns Zend_Db_Select of tickets table
     * @return Zend_Db_Select
     */
    public static function selectTickets() {
        return self::getAdapter()->select()
                ->from(self::TABLE_TICKETS, array('ticketId', 'userId', 'password', 'level', 'duration', 'added'));
    /*    return "SELECT `ticketId`, `userId`, `password`, `level`, `duration`, `added` " .
                "FROM `" . self::TABLE_TICKETS . "` " .
                "WHERE $where";*/
    }
    
    /**
     *
     * @return Zend_Db_Select 
     */
    public static function selectStats(){
        return self::getAdapter()->select()
                ->from(array('s'=>self::TABLE_STATS))
                ->joinLeft(array('subs' => self::TABLE_SUBS), '`s`.`subId` = `subs`.`subId`', array('userId'));
    }
    
    /**
     *
     * @return Zend_Db_Select 
     */
    public static function selectStatsPeriod($since, $till=null, $orderBy = 'date'){
        if ($till == null) {
            $till = new Zend_Date($since);
        }
        return self::selectStats()
                ->where('`date` >= ?', DateHelper::dateToDbStr($since))
                ->where('`date` <= ?', DateHelper::dateToDbStr($till))
                ->order($orderBy);
    }
    
    /**
     *
     * @return Zend_Db_Select 
     */
    public static function selectPayments(){
        return DbHelper::getAdapter()->select()
                ->from(array('p' => self::TABLE_PAYMENTS), '*')
                ->columns(array(
                    'since'=>'MIN(`s`.`date`)',
                    'till'=>'MAX(`s`.`date`)'
                    ))
                ->joinRight(array('s' => self::TABLE_STATS), '`p`.`paymentId` = `s`.`paymentId`')
                ->joinLeft(array('u' => self::TABLE_USERS), '`p`.`userId` = `u`.`userId`')
                ->group('p.paymentId')
                ->order('p.date DESC');
    }
    
    /**
     *
     * @return Zend_Db_Select 
     */
    public static function selectPendingPayments(){
        return DbHelper::selectStats()
                ->reset('columns')
                ->columns('u.*')
                ->columns(array(
                    'amount'=>'ROUND(SUM(`earned` - `onhold` + `approved`), 2)',
                    'since'=>'MIN(`date`)',
                    'till'=>'MAX(`date`)'
                    ))
                ->joinLeft(array('u' => self::TABLE_USERS), '`subs`.`userId` = `u`.`userId`')
                ->where('`paymentId` = 0 ')
                ->where('`date` < ?', DateHelper::dateToDbStr(new Zend_Date()))
                ->group('subs.userId');
    }
    
    /**
     *
     * @return Zend_Db_Select 
     */
    public static function selectSubAccounts(){
        return DbHelper::getAdapter()
                ->select()
                ->from(array('subs' => self::TABLE_SUBS))
                ->where('`status` = 0');
    }
    
    /**
     *
     * @return Zend_Db_Select 
     */
    public static function selectSmsCmc24(){
        return DbHelper::getAdapter()
                ->select()
                ->from(array('sms' => self::TABLE_SMS_CMC24));
    }

    /**
     *
     * @return Zend_Db_Select 
     */
    public static function selectSmsA1Pay(){
        return DbHelper::getAdapter()
                ->select()
                ->from(array('sms' => self::TABLE_SMS_A1PAY));
    }
}

