<?php
namespace LogHero\Client;
require_once __DIR__ . '/LogEvent.php';


class DebugLogEvent extends LogEvent  {

    public function columns() {
        $columns = parent::columns();
        $columns[] = 'rawIp';
        return $columns;
    }

    public function row() {
        $rows = parent::row();
        $rows[] = $this->ipAddress;
        return $rows;
    }
}
