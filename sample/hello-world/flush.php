<?php

// This file is only necessary for asynchronous flushing (AsyncLogTransport).
// It collects the log events from the buffer and sends them as one batch to the LogHero API.


// Make sure that we quickly respond to client and close the session before the actual flushing is started
ignore_user_abort( true );
set_time_limit(0);

ob_start();
header('Connection: close');
header('Content-Length: '.ob_get_length());
header('Content-Encoding: none');
ob_end_flush();
ob_flush();
flush();

if(session_id()) {
    session_write_close();
}


// Start flushing the buffered log events
require_once __DIR__ . '/../../autoload.php';

use LogHero\Client\APISettings;
use LogHero\Client\APIAccess;
use LogHero\Client\FileLogBuffer;
use LogHero\Client\AsyncLogTransport;


$apiKey = 'YOUR_API_KEY';
$clientId = 'Hello-World-Sample';
$apiSettings = new APISettings($apiKey);

// Flush token is used to ensure that this flush action is authorized
$flushToken = $_SERVER['HTTP_TOKEN'];
if ($flushToken !== $apiSettings->getKey()) {
    throw new Exception('Token is invalid');
}

// Initialize log buffer to read buffered log events
$logBuffer = new FileLogBuffer(__DIR__ . '/buffer.loghero.io');

// Initialize API client to transform, compress and submit log events to LogHero API;
$apiAccess = new APIAccess($clientId, $apiSettings);

// Initialize log transport and dump buffered log events to the LogHero API
$logTransport = new AsyncLogTransport($logBuffer, $apiAccess, $clientId, $apiKey, null);
$logTransport->dumpLogEvents();
