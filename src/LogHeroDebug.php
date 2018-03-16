<?php
    include(dirname(__DIR__).'/LogHero.php');

    class LHDebugLogEvent extends LHLogEvent  {

        public function columns() {
            $columns = parent::columns();
            array_push($columns, 'rawIp');
            return $columns;
        }

        public function row() {
            $rows = parent::row();
            array_push($rows, $this->ipAddress);
            return $rows;
        }

    }

?>
