<?php
/**
 * @var array $mailConfig
 * @var array $sites
 */

// check for config.php
if (!file_exists('config.php')) {
    die('Please create a config.php file. You can use the config.sample.php as a template.');
}


require 'app/HostDownNotifier/HostDownNotifier.php';
require 'config.php';



$hdn = new HostDownNotifier($mailConfig);

// second until next downtime notification (if already reported host still down)
// NOTE: Modifying default values has to be done before adding sites!
$hdn->setDefaultThreshold(86400); // 1 day


foreach ($sites as $site) {
    $hdn->addSite($site);
}

// DEBUG: Uncomment to send a test mail
//$hdn->sendTestMail();



// run the checks
$hdn->run();

// load the view
require 'app/view.php';


