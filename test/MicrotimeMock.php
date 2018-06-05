<?php
namespace LogHero\Client;
use phpmock\MockBuilder;


function createMicrotimeMock($namespace) {
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
