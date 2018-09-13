<?php

namespace LogHero\Client;


abstract class LogTransportType {
    const ASYNC = 'Async';
    const SYNC = 'Sync';
    const DISABLED = 'Disabled';
}
