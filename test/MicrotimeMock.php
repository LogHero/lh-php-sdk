<?php
namespace LogHero\Client\Test;

use phpmock\MockBuilder;


function createMicrotimeMock($namespace='\\LogHero\\Client') {
    $builder = new MockBuilder();
    $builder->setNamespace($namespace)
        ->setName('microtime')
        ->setFunction(
            function () {
                return $GLOBALS['currentTime'];
            }
        );
    return $builder->build();
}
