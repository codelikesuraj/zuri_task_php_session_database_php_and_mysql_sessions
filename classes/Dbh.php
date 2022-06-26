<?php

class Dbh
{
    private $hostname = 'localhost';
    private $username = 'root';
    private $password = '';
    private $dbname = 'zuriphp';

    protected function connect()
    {
        try {
            return new PDO("mysql:host=$this->hostname;dbname=$this->dbname", $this->username, $this->password);
        } catch (PDOException $exception) {
            die('Error connecting to database: '.$exception->getMessage());
        }
    }
}