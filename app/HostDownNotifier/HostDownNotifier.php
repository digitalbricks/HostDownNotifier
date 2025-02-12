<?php
// PHPMailer library
// @link: https://github.com/PHPMailer/PHPMailer
// @license: GNU Lesser General Public License
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ .'/../PHPMailer/src/Exception.php';
require __DIR__ .'/../PHPMailer/src/PHPMailer.php';
require __DIR__ .'/../PHPMailer/src/SMTP.php';


class HostDownNotifier{
    private $version = '0.2.0';
    
    private $defaultSiteConfig = array(
        'siteName' => '',   // e.g. 'My Website'
        'siteAddress' => '', // e.g. 'https://www.example.com' or just an IP address / hostame
        'notifyThreshold' => 3600,
        'acceptedStatusCodes' => [200, 301, 302],
    );

    private $defaultMailConfig = array(
        'SMTPDebug' => 0,
        'isSMTP' => true,
        'Host' => '',
        'SMTPAuth' => true,
        'Username' => '',
        'Password' => '',
        'SMTPSecure' => 'tls',
        'Port' => 465,
        'From' => '',
        'FromName' => 'Host Down Notifier',
        'Subject' => 'Host Down Notification',
        'To' => '',
    );

    private $mailConfig = array();
    private $sites = array();

    private $logFileDir = __DIR__ .'/../../log';

    public $logfileMaxSize = 1; // in MB


    public function __construct(array $mailConfig)
    {
        // make sure the ../log directory exists, if not create it
        if (!file_exists($this->logFileDir)) {
            mkdir($this->logFileDir, 0777, true);
        }

        // Merge the default mail config with the provided mail config
        $this->mailConfig = array_merge($this->defaultMailConfig, $mailConfig);

        // handling SMTPSecure settings
        if($this->mailConfig['SMTPSecure'] == 'tls'){
            $this->mailConfig['SMTPSecure'] = PHPMailer::ENCRYPTION_STARTTLS;
        }else if($this->mailConfig['SMTPSecure'] == 'ssl'){
            $this->mailConfig['SMTPSecure'] = PHPMailer::ENCRYPTION_SMTPS;
        }

    }

    /**
     * @return string
     */
    public function getVersion():string
    {
        return $this->version;
    }

    /**
     * @return int
     */
    public function getDefaultThreshold():int
    {
        return $this->defaultSiteConfig['notifyThreshold'];
    }

    /**
     * @param int $seconds
     * @return void
     */
    public function setDefaultThreshold(int $seconds)
    {
        $this->defaultSiteConfig['notifyThreshold'] = $seconds;
    }

    /**
     * @return bool
     */
    public function sendTestMail():bool
    {
        // Save the current SMTPDebug setting
        $SMTPDebug = $this->mailConfig['SMTPDebug'];

        // Set the SMTPDebug to 2 for verbose output
        $this->mailConfig['SMTPDebug'] = 2;

        // add notification to log
        $this->saveLog(
            'Sending test mail to '.$this->mailConfig['To']
        );

        $mailSend = $this->sendMail(
            $this->mailConfig['Subject'].': Test Mail',
            'This is a test mail from Host Down Notifier'
        );

        // Restore the SMTPDebug setting
        $this->mailConfig['SMTPDebug'] = $SMTPDebug;

        return $mailSend;

    }

    /**
     * @param string $subject
     * @param string $body
     * @return bool
     */
    private function sendMail(string $subject, string $body)
    {
        // return if 'Enable' is set to false in mailConfig
        if(!$this->mailConfig['Enable']){
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = $this->mailConfig['SMTPDebug'];                      // Enable verbose debug output
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = $this->mailConfig['Host'];                    // Set the SMTP server to send through
            $mail->SMTPAuth   = $this->mailConfig['SMTPAuth'];                                   // Enable SMTP authentication
            $mail->Username   = $this->mailConfig['Username'];                     // SMTP username
            $mail->Password   = $this->mailConfig['Password'];                               // SMTP password
            $mail->SMTPSecure = $this->mailConfig['SMTPSecure'];         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
            $mail->Port       = $this->mailConfig['Port'];                                    // TCP port to connect to
            $mail->CharSet    = 'UTF-8';

            //Recipients
            $mail->setFrom($this->mailConfig['From'], $this->mailConfig['FromName']);
            $mail->addAddress($this->mailConfig['To']);     // Add a recipient

            // Content
            $mail->isHTML(false);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            $this->saveLog('Notification sent', 'INFO');
            return true;
        } catch (Exception $e) {
            $this->saveLog('Message could not be sent. Mailer Error: '.$mail->ErrorInfo, 'ERROR');
            return false;
        }
    }

    /**
     * @param string $message
     * @param string $level
     * @return void
     */
    private function saveLog(string $message, string $level = 'INFO')
    {
        $level = strtoupper($level);
        switch ($level) {
            case 'INFO':
                $icon = 'ℹ️';
                break;
            case 'ERROR':
                $icon = '⚠️';
                break;
            case 'DOWN':
                $icon = '🔴';
                break;
            case 'UP':
                $icon = '🟢';
                break;
            default:
                $icon = 'ℹ️';
        }


        // create logfile 'log/hostdownnotifier.log' if not exists
        // check logfile size and create a new one if it exceeds 1MB
        $logFile = $this->logFileDir.'/hostdownnotifier.log';
        if (!file_exists($logFile)) {
            file_put_contents($logFile, '');
        } elseif (filesize($logFile) > $this->logfileMaxSize*1048576) {
            unlink($logFile);
            file_put_contents($logFile, '');
        }

        // create log message
        $logMessage = '['.date('Y-m-d H:i:s').'] '.$icon.' ['.$level.'] '.$message.PHP_EOL;

        // append log message to logfile
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }


    /**
     * @param array $siteConfig
     * @return bool
     * @throws Exception
     */
    public function addSite(array $siteConfig):bool
    {
        // Merge the default site config with the provided site config
        $siteConfig = array_merge($this->defaultSiteConfig, $siteConfig);

        // Check if the site name is empty
        if (empty($siteConfig['siteName'])) {
            throw new Exception('siteName cannot be empty');
        }

        // Check if the site address is a valid URL or hostname
        if (empty($siteConfig['siteAddress'])) {
            throw new Exception('siteAddress cannot be empty');
        }

        // Merge the default site config with the provided site config
        $siteConfig = array_merge($this->defaultSiteConfig, $siteConfig);

        
        // Add the site to the sites array
        $this->sites[] = $siteConfig;

        return true;
    }
    
    // a function that checks if a given string is a valid HTTP/S URL or just an IP address or hostname

    /**
     * @param string $url
     * @return bool
     */
    private function isValidUrl(string $url):bool
    {
        // Check if the URL is a valid HTTP/S URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }
        return false;
    }


    /**
     * @param $string
     * @return string|bool
     */
    private function generateSafeFilename($string):string|bool
    {
        // Replace umlauts
        $umlautMap = [
            'ä' => 'ae', 'Ä' => 'Ae',
            'ö' => 'oe', 'Ö' => 'Oe',
            'ü' => 'ue', 'Ü' => 'Ue',
            'ß' => 'ss'
        ];

        // Decode URL to handle encoded characters
        $decodedUrl = urldecode($string);

        // Replace umlauts in the string
        $safeFilename = strtr($decodedUrl, $umlautMap);

        // Replace any non-allowed characters with an underscore
        $safeFilename = preg_replace('/[^a-zA-Z0-9-_\.]/u', '_', $safeFilename);

        // Trim excessive underscores and dots from beginning and end
        $safeFilename = trim($safeFilename, '_\.');

        // Ensure the filename is not empty after sanitization
        if (empty($safeFilename)) {
            return false;
        }

        return $safeFilename;
    }

    /**
     * @param string $siteName
     * @return string|bool
     */
    private function getDownFilePath(string $siteName):string|bool
    {
        // Generate a safe filename for the site name
        $safeFilename = $this->generateSafeFilename($siteName);

        // If the safe filename is empty, return false
        if (!$safeFilename) {
            return false;
        }

        // Generate the full path to the down file
        $downFilePath = $this->logFileDir.'/'.$safeFilename.'.down.log';

        return $downFilePath;
    }

    /**
     * @return void
     */
    public function run()
    {
        $this->saveLog('Script RUN started ----------------------');
        foreach ($this->sites as $site){
            $this->probe($site);
        }
        $this->saveLog('Script RUN finished ---------------------');
    }

    /**
     * @param array $site
     * @return void
     */
    private function probe(array $site)
    {

        if($this->isValidUrl($site['siteAddress'])){
            $url = $site['siteAddress'];
            $statuscode = $this->getHTTPstatusCode($url);
            if(!in_array($statuscode, $site['acceptedStatusCodes'])){
                $this->logNotifyDown($site, $statuscode);
            } else {
                $this->logNotifyUp($site, $statuscode);
            }
        }
    }

    /**
     * @param array $site
     * @param int|bool $statuscode
     * @return void
     */
    private function logNotifyDown(array $site, int|bool $statuscode = false)
    {
        if($statuscode==0){
            $statuscode = 'NONE';
        }


        $this->saveLog(
            'Site '.$site['siteName'].' ('.$site['siteAddress'].') returned status code '.$statuscode,
            'DOWN'
        );

        // create a down file
        $downFileIsNew = $this->createDownFile($site);
        $downFilePath = $this->getDownFilePath($site['siteName']);


        $notifyThreshold = $site['notifyThreshold'];
        if($downFileIsNew){
            $mailSubject = '🔴'. $this->mailConfig['Subject'].': '.$site['siteName'].' is down';
            $mailBody = 'The site '.$site['siteName'].' ('.$site['siteAddress'].') returned status code '.$statuscode;
            $mailBody .= PHP_EOL.'DownFile created / renewed: '.file_get_contents($downFilePath);
            $mailBody .= PHP_EOL.'Threshold until next mail notification: '.$site['notifyThreshold'].' seconds';
            $this->sendMail($mailSubject, $mailBody);
        } else {
            $this->saveLog('Notification skipped because threshold ('.$site['notifyThreshold'].'s) not reached', 'INFO');
        }
    }

    private function createDownFile(array $site)
    {
        $downFilePath = $this->getDownFilePath($site['siteName']);
        if (!file_exists($downFilePath)) {
            // create the down file
            file_put_contents($downFilePath, date('Y-m-d H:i:s'));
            return true;
        }

        // if DownFile already exists and is older than the notifyThreshold
        // we create a new one (in order to get an updated filemtime timestamp)
        $downFileMtime = filemtime($downFilePath);
        $notifyThreshold = $site['notifyThreshold'];
        if(time() - $downFileMtime > $notifyThreshold){
            // create the down file
            file_put_contents($downFilePath, date('Y-m-d H:i:s'));
            return true;
        }
        return false;
    }

    /**
     * @param array $site
     * @param int|bool $statuscode
     * @return void
     */
    private function logNotifyUp(array $site, int|bool $statuscode = false)
    {
        $this->saveLog(
            'Site '.$site['siteName'].' ('.$site['siteAddress'].') returned status code '.$statuscode,
            'UP'
        );

        // check if down file exists
        $downFilePath = $this->getDownFilePath($site['siteName']);
        if (file_exists($downFilePath)) {
            // send a notification that the site is up again
            $mailSubject = '🟢'. $this->mailConfig['Subject'].': '.$site['siteName'].' is up again';
            $mailBody = 'The site '.$site['siteName'].' ('.$site['siteAddress'].') returned status code '.$statuscode;
            $mailBody .= PHP_EOL.'Down state first observed: '.file_get_contents($downFilePath);
            $this->sendMail($mailSubject, $mailBody);

            // delete the down file
            unlink($downFilePath);
        }
    }


    /**
     * @param $host
     * @param $port
     * @param $timeout
     * @return bool
     */
    private function isServerReachable($host, $port = 80, $timeout = 2):bool
    {
        // Create a socket connection to the host on the specified port
        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if ($connection) {
            // Connection was successful; close it and return true
            fclose($connection);
            return true;
        } else {
            // Connection failed; return false
            return false;
        }
    }


    /**
     * @param string $url
     * @return int
     */
    private function getHTTPstatusCode(string $url):int
    {
        $agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';


        // Initialize cURL
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Execute the cURL request
        curl_exec($ch);

        // Get the HTTP status code
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Close the cURL connection
        curl_close($ch);

        return $statusCode;

    }

    /**
     * @return string
     */
    public function getLog():string
    {
        $logFile = $this->logFileDir.'/hostdownnotifier.log';
        if (file_exists($logFile)) {
            return file_get_contents($logFile);
        } else {
            return "Logfile not found";
        }

    }
}