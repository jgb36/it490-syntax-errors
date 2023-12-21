#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('login.php.inc');

function logProcessor($log)
{
  print_r($log);
  $logFilePath = __DIR__ . '/logfile.txt';

    $currentTime = date('Y-m-d H:i:s');
    if (is_array($log)) {
        $log = implode(PHP_EOL, $log);
    }
    $logEntry = "[$currentTime] $log" . PHP_EOL . str_repeat('-', 25) . PHP_EOL;
    file_put_contents($logFilePath, $logEntry, FILE_APPEND);
    return;
}

$server = new rabbitMQServer("RabbitMQ.ini","logger");

echo "logRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('logProcessor');
echo "logRabbitMQServer END".PHP_EOL;

?>

