<?php

// Mail configuration for PHPMailer
$mailConfig = [
    'Host' => 'smtp.example.com',               // SMTP server
    'SMTPAuth' => true,                         // Enable SMTP authentication
    'Username' => 'username',
    'Password' => 'password',
    'SMTPSecure' => 'tls',                      // 'ssl' or 'tls'
    'Port' => 587,                              // Usually 587 for TLS, 465 for SSL
    'From' => 'report@example.com',             // Sender email address
    'FromName' => 'Host Down Notifier',         // Sender name
    'Subject' => 'Host Down Notification',
    'To' => 'receiver@example.com',             // Receiver email address
    'Enable' => true,                           // Enable or disable mail notifications
];

$sites = [
    [
        'siteName' => 'Proxmox VE',
        'siteAddress' => 'https://10.44.7.10:8006',
    ],
    [
        'siteName' => 'Home Assistant',
        'siteAddress' => 'http://10.44.7.20:8123',
    ],
    [
        'siteName' => 'Another service',
        'siteAddress' => 'https://10.44.7.30/',
    ],
    [
        'siteName' => 'Example with all available options',
        'siteAddress' => 'http://10.44.7.30',
        'notifyThreshold' => 3600,                  // Number of seconds until next downtime notification (if already reported)
        'acceptedStatusCodes' => [200, 301],        // Array of accepted HTTP status codes (default: [200, 301, 302])
    ],
];