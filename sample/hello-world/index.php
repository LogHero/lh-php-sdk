<html>
  <head>
    <title>LogHero Example Page</title>
  </head>

  <body>

    <h1>Hello LogHero - Example Page</h1>

  </body>

  <?php
  require_once __DIR__ . '/../../autoload.php';

  use LogHero\Client\APISettings;
  use LogHero\Client\APIAccess;
  use LogHero\Client\FileLogBuffer;
  use LogHero\Client\LogTransport;
  use LogHero\Client\AsyncLogTransport;
  use LogHero\Client\LogEventFactory;

  $apiKey = 'YOUR_API_KEY';
  $clientId = 'Hello-World-Sample';
  $flushTriggerEndpoint = 'http://localhost/sdk/sample/hello-world/flush.php';
  $apiSettings = new APISettings($apiKey);

  // To avoid heavy load on the LogHero API and your server, log events are supposed to be sent in batches.
  // The simplest way of buffering is creating a text file with the serialized log events.
  // If you server load does not allow the usage of a buffer file you can switch to using the RedisLogBuffer instead.
  echo '<p>Initialize log buffer to collect hits for batch processing</p>';
  $logBuffer = new FileLogBuffer(__DIR__ . '/buffer.loghero.io');

  echo '<p>Initialize API client to transform, compress and submit log events to LogHero API</p>';
  $apiAccess = new APIAccess($clientId, $apiSettings);

  // The SDK provides two log transports:
  // - LogTransport: The simplest log transport that sends the log events immediately whenever the log buffer needs dumping.
  // - AsyncLogTransport: This log transport dumps the log events asynchronically.
  //                      You need to provide a flush endpoint that does the actual flushing (see './flush.php').
  //                      Whenever the buffer needs dumping, the log transport hits this endpoint to trigger the flush.
  echo '<p>Initialize log transport to define when log events are sent to the LogHero API</p>';
  #$logTransport = new LogTransport($logBuffer, $apiAccess);
  $logTransport = new AsyncLogTransport($logBuffer, $apiAccess, $clientId, $apiKey, $flushTriggerEndpoint);

  echo '<p>Create log event from the request and server information available</p>';
  $logEventFactory = new LogEventFactory();
  $logEvent = $logEventFactory->create();

  // When the maximum buffer size is reached or the age of the oldest log event in the buffer exceeds a time limit,
  // all log events from the buffer are sent automatically as one batch to the LogHero API.
  echo '<p>Submit log event to buffer.</p>';
  $logTransport->submit($logEvent);

  echo '<p>Page loaded successfully</p>';
  ?>
</html>
