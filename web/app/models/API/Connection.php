<?php
namespace Witter\Models;

class Connection extends \Witter\Configurator {
    public $Configuration;

    public function MakeConnection() : \PDO {
        $Configuration = (object) [
            "Database" => (object) [
                "DatabaseHost"     => getenv("MYSQL_HOST"),
                "DatabaseName"     => getenv("MYSQL_DATABASE"),
                "DatabaseUsername" => getenv("MYSQL_ROOT_USER"),
                "DatabasePassword" => getenv("MYSQL_ROOT_PASSWORD"),
            ],
        ];

        $this->Configuration = $Configuration;

        try
        {
            $Connection = new \PDO("mysql:host=" . $this->Configuration->Database->DatabaseHost . ";dbname=" . $this->Configuration->Database->DatabaseName . ";charset=utf8mb4",
                $this->Configuration->Database->DatabaseUsername,
                $this->Configuration->Database->DatabasePassword,
                [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ]
            );
        }
        catch(\PDOException $e)
        {
            die("An error occured connecting to the database: " . $e->getMessage());
        }

        return $Connection;
    }
}