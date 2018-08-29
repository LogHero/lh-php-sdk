<?php


require 'vendor/predis/predis/autoload.php';


spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'loghero\\client\\apiaccess' => '/src/http/APIAccess.php',
                'loghero\\client\\apiaccessbase' => '/src/http/APIAccessBase.php',
                'loghero\\client\\apisettingsinterface' => '/src/http/APISettingsInterface.php',
                'loghero\\client\\apisettingsdefault' => '/src/http/APISettingsDefault.php',
                'loghero\\client\\apisettings' => '/src/http/APISettings.php',
                'loghero\\client\\apiaccessexception' => '/src/http/APIAccessException.php',
                'loghero\\client\\apiaccessinterface' => '/src/http/APIAccessInterface.php',
                'loghero\\client\\apikeyundefinedexception' => '/src/http/APIKeyUndefinedException.php',
                'loghero\\client\\asynclogtransport' => '/src/transport/AsyncLogTransport.php',
                'loghero\\client\\disabledlogtransport' => '/src/transport/DisabledLogTransport.php',
                'loghero\\client\\curlclient' => '/src/http/CurlClient.php',
                'loghero\\client\\debuglogevent' => '/src/event/DebugLogEvent.php',
                'loghero\\client\\filelogbuffer' => '/src/buffer/FileLogBuffer.php',
                'loghero\\client\\redislogbuffer' => '/src/buffer/RedisLogBuffer.php',
                'loghero\\client\\redisoptions' => '/src/buffer/RedisOptions.php',
                'loghero\\client\\invalidlogeventexception' => '/src/event/InvalidLogEventException.php',
                'loghero\\client\\logbufferinterface' => '/src/buffer/LogBufferInterface.php',
                'loghero\\client\\buffersizeexceededexception' => '/src/buffer/BufferSizeExceededException.php',
                'loghero\\client\\logevent' => '/src/event/LogEvent.php',
                'loghero\\client\\logeventfactory' => '/src/event/LogEventFactory.php',
                'loghero\\client\\logtransport' => '/src/transport/LogTransport.php',
                'loghero\\client\\logtransportinterface' => '/src/transport/LogTransportInterface.php',
                'loghero\\client\\logtransporttype' => '/src/transport/LogTransportType.php',
                'loghero\\client\\asyncflushfailedexception' => '/src/transport/AsyncFlushFailedException.php',
                'loghero\\client\\memlogbuffer' => '/src/buffer/MemLogBuffer.php',
                'loghero\\client\\logheroerrors' => '/src/error/LogHeroErrors.php',
                'loghero\\client\\permissiondeniedexception' => '/src/error/PermissionDeniedException.php',
                'loghero\\client\\storageinterface' => '/src/util/StorageInterface.php',
                'loghero\\client\\filestorage' => '/src/util/FileStorage.php',
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    }
);