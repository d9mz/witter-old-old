<?php
namespace Witter\Views;

class View extends \Witter\Configurator {
    public $Connection;
    public $Configuration;
    public $Twig;

    function __construct() {
        parent::__construct();
        $this->MakeConnection();

        if(isset($_SESSION['Handle'])) {
            $userModel = new \Witter\Models\User();

            $css        = $userModel->showUnmoderatedCSS($_SESSION['Handle']);
            $isAdmin    = $userModel->isAdmin($_SESSION['Handle']);
            $dispCSS    = $userModel->isCSSUnapproved($_SESSION['Handle']);
            $waitingCSS = $userModel->isCSSWaiting($_SESSION['Handle']);
            
            $this->Twig->addGlobal('showUnmoderatedCSS', $css);
            $this->Twig->addGlobal('isAdmin', $isAdmin);
            $this->Twig->addGlobal('hasDisprovenCSS', $dispCSS);
            $this->Twig->addGlobal('waitingApprovalCSS', $waitingCSS);
        }
    }

    function MakeConnection() {
        try
        {
            $this->Connection = new \PDO("mysql:host=" . $this->Configuration->Database->DatabaseHost . ";dbname=" . $this->Configuration->Database->DatabaseName . ";charset=utf8mb4",
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
    }

    public function PageSettings($Title = "Homepage", $Description = "Welcome to Witter") {
        // TODO: Ugly.
        // This gets rid of Alert every time a view is rendered, but there may be a specific
        // edge-case where this is not true

        unset($_SESSION['Alert']);

        return (object) [
            "PageTitle"       => $Title,
            "PageDescription" => $Description,
        ];
    }
}