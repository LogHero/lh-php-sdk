<html>
  <head>
    <title>LogHero Hello World</title>
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
  use LogHero\Client\LogEventFactory;

  $apiKey = 'YOUR_API_KEY';
  $clientId = 'Hello-World-Sample';
  $apiSettings = new APISettings($apiKey);

  echo '<p>Initialize log buffer to collect hits for batch processing</p>';
  $logBuffer = new FileLogBuffer(__DIR__ . '/buffer.loghero.io');

  echo '<p>Initialize API client to submit log events to LogHero API</p>';
  $apiAccess = new APIAccess($clientId, $apiSettings);

  // TODO Provide sync and async sample
  echo '<p>Initialize log transport to define when log events are sent to the LogHero API</p>';
  $logTransport = new LogTransport($logBuffer, $apiAccess);

  echo '<p>Create log event from the request and server information available</p>';
  $logEventFactory = new LogEventFactory();
  $logEvent = $logEventFactory->create();

  echo '<p>
     Submit log event to buffer.
     When the maximum buffer size is reached or the age of the oldest log event in the buffer exceeds a time limit,
     all log events from the buffer are sent as one batch to the LogHero API.
  </p>';
  $logTransport->submit($logEvent);

  echo '<p>Page loaded successfully</p>';
  ?>
</html>
