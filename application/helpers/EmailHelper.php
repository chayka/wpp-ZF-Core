<?php

class EmailHelper {

    protected static $scriptPaths = array();
    
    public static function addScriptPath($path){
        if(!in_array($path, self::$scriptPaths)){
            self::$scriptPaths[]=$path;
        }
    }
    
    public static function send($subject, $html, $to, $from = '', $cc = '', $bcc = '', $linkToWebVersion = ''){
        $mailFrom = get_option('EmailHelper.mail_from', 'postmaser@'.$_SERVER['SERVER_NAME']);
        $mailFromName = get_option('EmailHelper.mail_from', $_SERVER['SERVER_NAME']);
        $mailer = get_option('EmailHelper.mailer', 'php');
        $smtpHost = get_option('EmailHelper.smtp_host', 'localhost');
        $smtpPort = get_option('EmailHelper.smtp_port', '25');
        $smtpSsl  = get_option('EmailHelper.smtp_ssl', 'none');
        $smtpAuth  = get_option('EmailHelper.smtp_auth', false);
        $smtpUser  = get_option('EmailHelper.smtp_user', '');
        $smtpPass  = get_option('EmailHelper.smtp_pass', '');
        
        $mail = new Zend_Mail('utf-8');

        // configure base stuff
        $mail->setSubject($subject);
        $fn = get_template_directory().'/application/views/scripts/email/template.phtml';

        if(file_exists($fn)){
            $view = new Zend_View();
            $view->setBasePath(get_template_directory().'/application/views');
            try{
                $html = str_replace('<!--content-->', $html, $view->render('email/template.phtml'));
            }catch(Exception $e){
                JsonHelper::respond($e->getMessage());
            }
        }
        if($linkToWebVersion){
            $html = str_replace('[WEBVERSION]', $linkToWebVersion, $html);
        }else{
            $html = preg_replace('%<(p)[^>]*>[^<]*<a [^>]*href="\[WEBVERSION\]"[^>]*>[^<]*</a>[^<]*</p>%imUs', '', $html);
//            $html = preg_replace('%<a [^>]*href="\[WEBVERSION\]"[^>]*>[^<]*</a>[^<]*</p>"%imUs', '', $html);
//            die($html);
        }
        $mail->setBodyHtml($html);
        $mail->setFrom($from?$from:$mailFrom, $from?null:$mailFromName);
        $mail->addTo($to);
        if($cc){
            $mail->addCc($cc);
        }
        if($bcc){
            $mail->addBcc($bcc);
        }

        $config = $smtpAuth?array(
            'host' => $smtpHost,
            'auth' => 'login',
            'username' => $smtpUser,
            'password' => $smtpPass,
            'port' => $smtpPort
            ):array(
            'port' => $smtpPort                
            );
        if($smtpSsl!='none'){
            $config['ssl'] = 'tls';
        }
            
        try{
            $transport = null;
            switch($mailer){
                case 'smtp':
                    $transport = new Zend_Mail_Transport_Smtp($smtpHost, $config);
                    break;
                case 'dkim':
                    $transport = new Mail_Transport_Dkim($config);
                    break;
                default:
                    $transport = null;
            }
//            $transport = 'smtp' == $mailer?
//                new Zend_Mail_Transport_Smtp($smtpHost, $config):
//                null;
            return $mail->send($transport);
        }catch(Exception $e){
            JsonHelper::respondError($e->getMessage());
            return false;
        }
        return false;
    }
    

    public static function sendTemplate($subject, $template, $params, $to, $from = '', $cc = '', $bcc = '', $scriptPath = '/views/scripts/email/'){
        try{
            $html = new Zend_View();
            $html->setScriptPath($scriptPath);
            foreach(self::$scriptPaths as $path){
                $html->addScriptPath($path);
            }
            foreach($params as $key => $value){
                $html->assign($key, $value);
            }

            $content = $html->render($template);
            
        }catch(Exception $e){
            JsonHelper::respondError($e->getMessage());
        }
        return self::send($subject, $content, $to, $from, $cc, $bcc);
    }
    
//    public static function userRegistered($user, $password){
//        self::sendTemplate(sprintf("Учетная запись на %s", $_SERVER['SERVER_NAME']), 
//                'user-registered.phtml', array('user' => $user, 'password' => $password), 
//                $user->getEmail());
//    }
    
}

class Mail_Transport_Dkim extends Zend_Mail_Transport_Smtp
{
    /**
     * Maximum resend attempts on connectivity problems and throttling
     * @see http://docs.amazonwebservices.com/ses/latest/DeveloperGuide/Troubleshooting.Throttling.html
     */
    const RESEND_ATTEMPTS = 3;
   
    /**
     * EOL character string used by DKIM in canonicalizations is "CRLF"
     * @var string
     */
    private $_dkimEol = "\r\n";
   
    /**
     * Flag to add DKIM signature
     * @var bool
     */
    private $_dkimEnabled = false;
   
    /**
     * The domain of the signing entity (required)
     * @var string
     */
    private $_dkimDomain;
   
    /**
     * The selector subdividing the namespace for the domain (required)
     * @var string
     */
    private $_dkimSelector;
   
    /**
     * The algorithm used to generate the signature
     * @var string
     */
    private $_dkimAlgorithm = "rsa-sha1";
   
    /**
     * A colon-separated list of query methods used to retrieve the public key
     * @var string
     */
    private $_dkimQueryMethods = "dns/txt";
   
    /**
     * Message canonicalization algorithm
     * @var type
     */
    private $_dkimCanonicalization = "relaxed/simple";
   
    /**
     * Zend_Crypt_Rsa instance for encryption operations
     * @var Zend_Crypt_Rsa
     */
    private $_cryptRsa;
   
    /**
     * Key path to DKIM private key
     * @var string
     */
    private $_keyPath;
    /**
     * Setting up values from application.ini
     * @param type $options
     */
    public function __construct($options = array())
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }
       
        // setting up dkim-related options
        if (isset($options["dkim"])) {
            foreach ($options["dkim"] as $key => $value) {
                if ("enabled" === $key && !empty($value)) {
                    $this->_dkimEnabled = true;
                } else if ("pemPath" === $key) {
                    $this->_keyPath = $options["dkim"]['pemPath'];
                } else {
                    $variable = "_dkim" . ucfirst($key);
                    $this->$variable = $value;
                }
            }
        }
       
        /**
         * Zend_Mail_Transport_Smtp is not like other Zend\Mail\Transport
         * classes. It expects hostname as first argument to the constructor.
         */
        if(empty($options['host'])) {
            throw new Exception('A host is necessary for this ' .
                    'transport, but none was given');
        }
        parent::__construct($options["host"], $options);
    }
   
    /**
     * Amazon SES doesn't allow content-disposition 'inline' header when content type is set to 'multipart/mixed'.
     * @param string $boundary
     * @return array
     */
    protected function _getHeaders($boundary)
    {
        parent::_getHeaders($boundary);
       
        // if it's mulitpart message we're removing "Content-Disposition" common header
        if (!empty($boundary)) {
            $type = $this->_mail->getType();
            if (Zend_Mime::MULTIPART_MIXED == $type || empty($type)) {
                unset($this->_headers["Content-Disposition"]);
            }
        }
       
        return $this->_headers;
    }
    /**
     * Lazy loader for the private key
     * @return Zend_Crypt_Rsa
     */
    private function _getCryptRsa()
    {
        if (empty($this->_cryptRsa)) {
            $this->_cryptRsa = new Zend_Crypt_Rsa(array(
                'pemPath' => $this->_keyPath));
        }
        return $this->_cryptRsa;
    }
   
    /**
     * Overriding _sendMail() to sign the message
     * @throws Zend_Mail_Protocol_Exception
     */
    public function _sendMail()
    {
        // signing the message if neccessary
        if ($this->_dkimEnabled) {
            $this->_addDkimSignature();
        }
       
        // attempting to send emails
        $attemptCount = 0;
        do {
            $retry = false;
            $attemptCount++;
           
            try {
                parent::_sendMail();
            } catch (Zend_Mail_Protocol_Exception $e) {
                switch (trim($e->getMessage())) {
                    // in non-fatal exception wait for 5-10 sec and retry
                    case 'Could not read from ' . $this->_host:
                        $this->_forceReconnect();
                    case 'Throttling failure: Maximum sending rate exceeded.':
                        if ($attemptCount >= self::RESEND_ATTEMPTS) {
                            // it was last attempt, throwing it outside
                            throw $e;
                        }
                        sleep(rand(5, 10));
                        $retry = true;
                        break;
                    // throwing an unexpected exception outside
                    default:
                        throw $e;
                }
            }
        } while ($retry && $attemptCount < self::RESEND_ATTEMPTS);
    }
   
    /**
     * Adds DKIM-Signature header to the message.
     */
    private function _addDkimSignature()
    {
        // getting canonnicalizated body
        $body = $this->_simpleBodyCanonicalization();
       
        // calculating packed binary SHA1 hash
        $bodyHash = base64_encode(pack("H*", sha1($body)));
       
        // generating tag set for the signature
        $dkimSignature = array(
            "v" => "1",
            "a" => $this->_dkimAlgorithm,
            "q" => $this->_dkimQueryMethods,
            "l" => strlen($body),
            "s" => $this->_dkimSelector,
            "t" => time(),
            "c" => $this->_dkimCanonicalization,
            "h" => "From:To:Subject",
            "d" => $this->_dkimDomain,
            "bh" => $bodyHash,
            "b" => "" // needs to be empty cuz this part should be signed
        );
       
        // assembling the header value together
        $values = array();
        foreach ($dkimSignature as $key => $value) {
            $values[] = $key . "=" . $value;
        }
        $dkim = implode(";" . $this->EOL . "\t", $values);
       
        // getting text to be signed according with "h" tag
        $toBeSigned = $this->_relaxedHeaderCanonicalization(
            "From: " . $this->_headers["From"][0] . $this->_dkimEol .
            "To: " . $this->_headers["To"][0] . $this->_dkimEol .
            "Subject: " . $this->_headers["Subject"][0] . $this->_dkimEol .
            "DKIM-Signature: " . $dkim);
       
        // signing the headers
        $signature = $this->_getCryptRsa()
                ->sign($toBeSigned, null, Zend_Crypt_Rsa::BASE64);
       
        // adding new header
        $this->header .= "DKIM-Signature: " . $dkim.$signature . $this->EOL;
    }
   
    /**
     * "relaxed" Header Canonicalization Algorithm implemented according to
     * RFC2822 section 3.4.2.
     * @param string $string
     * @return string
     */
    private function _relaxedHeaderCanonicalization($string)
    {
        // unfolding all header field continuation lines
        $string = preg_replace("/" . $this->_dkimEol . "\s+/", " ", $string);
       
        // converting all header fields to lowercase
        $lines = explode($this->_dkimEol, $string);
        foreach ($lines as $key => $line) {
            list($heading, $value) = explode(":", $line, 2);
            $heading = strtolower($heading);
            // converting all sequences of one or more WSP characters to a
            // single SP character
            $value = preg_replace("/\s+/", " ", $value);
            $lines[$key] = $heading . ":" . trim($value);
        }
       
        $string = implode($this->_dkimEol, $lines);
        return $string;
    }
    /**
     * The "simple" Body Canonicalization Algorithm implemented according to
     * RFC2822 section 3.4.3.
     * @return string
     */
    private function _simpleBodyCanonicalization()
    {
        $body = $this->body;
       
        if ($body == '')
            return $this->_dkimEol;
        // replace all \n -> \r\n
        $body = str_replace($this->_dkimEol, "\n", $body);
        $body = str_replace("\n", $this->_dkimEol, $body);
       
        // converting "0*CRLF" at the end of the body to a single "CRLF"
        $_EolSize = strlen($this->_dkimEol);
        while (substr($body, strlen($body) - $_EolSize*2, $_EolSize*2)
                == $this->_dkimEol . $this->_dkimEol) {
            $body = substr($body, 0, strlen($body) - $_EolSize);
        }
       
        return $body;
    }
    /**
     * Forces to disconnect. When next send attempt will be done transport
     * will reconnect again.
     */
    private function _forceReconnect()
    {
        if ($this->_connection instanceof Zend_Mail_Protocol_Smtp) {
            try {
                $this->_connection->quit();
            } catch (Zend_Mail_Protocol_Exception $e) {
                // ignore
            }
            $this->_connection->disconnect();
        }
        $this->_connection = null;
    }
}