<?php
    /**
     * Created by PhpStorm.
     * User: abelghazinyan
     * Date: 8/3/17
     * Time: 7:37 PM
     */

    namespace Database;

    class Connection
    {
        private static $instance;
        private $connection;

        private function __construct()
        {
            $config = parse_ini_file('../config/config.ini');

            $this->connection = new \PDO("mysql:host=localhost;dbname={$config['dbname']}",$config['username'],$config['password']);
        }

        public static function getInstance()
        {
            if (self::$instance === null) {
                self::$instance = new Connection();
            }
            return self::$instance;
        }

        public function getConnection()
        {
            return $this->connection;
        }

    }