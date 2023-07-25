<?php
namespace Witter\Models;

class Example extends Model {
    // create database instance -- this shouldn't really be done here but it's
    // a temporary solution to the infinite connection issue
    public function __construct() {
        $connection = new \Witter\Models\Connection();
        $this->Connection = $connection->MakeConnection();
    }
    
    public function DoSomething() {

    }
}