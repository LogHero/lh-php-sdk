<?php


spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'loghero\\client\\apiaccess' => '/src/http/APIAccess.php',
                'loghero\\client\\apiaccessbase' => '/src/http/APIAccessBase.php',
                'loghero\\client\\apiaccessexception' => '/src/http/APIAccessException.php',
                'loghero\\client\\apiaccessinterface' => '/src/http/APIAccessInterface.php',
                'loghero\\client\\asynclogtransport' => '/src/transport/AsyncLogTransport.php',
                'loghero\\client\\curlclient' => '/src/http/CurlClient.php',
                'loghero\\client\\debuglogevent' => '/src/event/DebugLogEvent.php',
                'loghero\\client\\filelogbuffer' => '/src/buffer/FileLogBuffer.php',
                'loghero\\client\\invalidlogeventexception' => '/src/event/InvalidLogEventException.php',
                'loghero\\client\\logbufferinterface' => '/src/buffer/LogBufferInterface.php',
                'loghero\\client\\logevent' => '/src/event/LogEvent.php',
                'loghero\\client\\logeventfactory' => '/src/event/LogEventFactory.php',
                'loghero\\client\\logtransport' => '/src/transport/LogTransport.php',
                'loghero\\client\\logtransportinterface' => '/src/transport/LogTransportInterface.php',
                'loghero\\client\\memlogbuffer' => '/src/buffer/MemLogBuffer.php',
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    }
);