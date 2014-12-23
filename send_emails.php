<?php
require './vendor/autoload.php';

use \Stampie\Adapter\Guzzle;
use \Stampie\Mailer\Postmark;
use \Guzzle\Service\Client;
use \Stampie\Message;
use \Stampie\Identity;

$config = require "./config.php";

$dry_run = false;
$override_email = false;
if (count($argv) > 1) {
    if (filter_var($argv[1], FILTER_VALIDATE_EMAIL) !== false) {
        $override_email = $argv[1];
    } else {
        $dry_run = true;
    }
}

if ($dry_run) {
    print "!! ASSUMING DRY RUN!\n";
} else if ($override_email) {
    print "!! OVERRIDING TO-EMAIL TO {$override_email}\n";
}


$adapter = new Guzzle(new Client());
$mailer = new Postmark($adapter, $config['postmark']['token']);

abstract class LSPMessage extends Message {
    protected $config;
    
    public function setConfig($config) {
        $this->config = $config;
    }
    
    public function getConfig() {
        return $this->config;
    }
}

class AcceptanceEmail extends LSPMessage {
    public $vars = [
        '[[name]]' => '',
        '[[sessions]]' => '',
    ];
    
    public function getFrom() { return $this->getConfig()['from']; }
    public function getBcc() { return $this->getConfig()['bcc']; }
    public function getSubject() { return $this->getConfig()['acceptance']['subject']; }
    public function getText() { 
        return str_replace(
            array_keys($this->vars), 
            array_values($this->vars), 
            $this->getConfig()['acceptance']['template']
        ); 
    }
}

class DenialEmail extends LSPMessage {
    public $vars = [
        '[[name]]' => '',
    ];
    
    public function getFrom() { return $this->getConfig()['from']; }
    public function getBcc() { return $this->getConfig()['bcc']; }
    public function getSubject() { return $this->getConfig()['rejection']['subject']; }
    public function getText() { 
        return str_replace(
            array_keys($this->vars), 
            array_values($this->vars), 
            $this->getConfig()['rejection']['template']
        ); 
    }
    
    
}

function sendEmails($csvfile, $messageClass, $mailer, $config) {
    global $dry_run;
    global $override_email;
    
	$f = fopen($csvfile, "r");

	while($row = fgetcsv($f)) {
		$name = implode(' ', array_map('trim', [$row[0], $row[1]]));
		$email = trim($row[2]);
		$sessions = "";
		if (isset($row[3])) {
			foreach (explode("|", $row[3]) as $session) {
				$sessions .= " - $session\n";
			}
		}

		$identity = new Identity($override_email ?: $email);
		$identity->setName($name);

		$message = new $messageClass($identity);
        $message->setConfig($config);
		$message->vars['[[name]]'] = $name;
		$message->vars['[[sessions]]'] = $sessions;

		echo " * Sending mail to $name <$email>" . ($override_email ? " (overridden)\n" : "\n");
        if (!$dry_run) $mailer->send($message);
	}
	fclose($f);

}

echo "SENDING ACCEPTANCE EMAILS\n";
sendEmails("./acceptance.csv", "AcceptanceEmail", $mailer, $config);

echo "SENDING DENAIL EMAILS\n";
sendEmails("./rejection.csv", "DenialEmail", $mailer, $config);
