<?php
/**
 * @var array $mailConfig
 * @var array $sites
 * @var HostDownNotifier $hdn
 */

$version = $hdn->getVersion();
$mailEnabled = $mailConfig['Enable'];
$mailReceiver = $mailConfig['To'];
$threshold = $hdn->getDefaultThreshold();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>HostDownNotifier</title>
    <style>
        :root{
            --color-primary: #023535;
            --color-secondary: #015958;
            --color-tertiary: #D8FFDB;
            --color-danger: #ca0505;
            --color-warning: #ffc107;
        }
        body{
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            line-height: 1.4;
        }
        h1,h2{
            margin-top: 0;
            line-height: 1.2;
        }
        h1{
            font-size: 1.6rem;
        }
        h2{
            font-size: 1.4rem;
        }
        h1 small{
            font-weight: normal;
        }
        .wrapper{
            width: 98%;
            margin: 0 auto;
            max-width: 1200px;
            padding: 4rem 0;
        }
        textarea{
            width: 100%;
            min-height: 60vh;
        }
        .config{
            margin-bottom: 2rem;
        }
        .config__items{
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
        }
        .config__value{
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            background-color: var(--color-primary);
            color: white;
        }
        .config__value--off{
            background-color: var(--color-danger);
        }
        .pagesection{
            margin: 1rem 0;
            padding: 1.5rem;
            background-color: var(--color-tertiary);
            border-radius: 0.5rem;
        }
        .logfile{
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <header>
            <h1>HostDownNotifier <small>v<?=$version?></small></h1>
        </header>
        <main>
            <div class="info pagesection">
                <p>
                    HostDownNotifier is a simple tool that notifies if a configured host isn't available. This view (aka frontend) is purely for having
                    a quick glimpse at the configuration and the error log. There is no admin screen or such, hosts to check and email settings are configured in the <code>config.php</code> file.
                    After having this configured, you can run the script via a cronjob or manually. You can even completely omit this page output by removing <code>require 'app/view.php';</code>
                    in <code>index.php</code> if you want.
                </p>
            </div>
            <div class="config pagesection">
                <h2>Config</h2>
                <div class="config__items">
                    <div class="config__item config__item--mailenabled">
                        <strong>Mail enabled:</strong>
                        <span class="config__value config__value--<?=$mailEnabled ? 'on' : 'off'?>"><?=$mailEnabled ? 'Enabled' : 'Disabled'?></span>
                    </div>
                    <div class="config__item config__item--mailreceiver">
                        <strong>Mail receiver:</strong>
                        <span class="config__value"><?=$mailReceiver?></span>
                    </div>
                    <div class="config__item config__item--mailreceiver">
                        <strong>Default threshold:</strong>
                        <span class="config__value"><?=$threshold?>s</span>
                    </div>
                </div>
            </div>
            <div class="logfile">
                <h2>Logfile</h2>
                <textarea id="logview" id="" cols="30" rows="10" readonly><?=$hdn->getLog()?></textarea>
            </div>
        </main>
        
    </div>
    <script>
        var textarea = document.getElementById('logview');
        textarea.scrollTop = textarea.scrollHeight;
    </script>
</body>
</html>