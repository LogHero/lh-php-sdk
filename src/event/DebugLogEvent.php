<?php
namespace LogHero\Client;


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
