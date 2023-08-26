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
            $notificationModel = new \Witter\Models\Notifications();

            $css          = $userModel->showUnmoderatedCSS($_SESSION['Handle']);
            $isAdmin      = $userModel->isAdmin($_SESSION['Handle']);
            $dispCSS      = $userModel->isCSSUnapproved($_SESSION['Handle']);
            $waitingCSS   = $userModel->isCSSWaiting($_SESSION['Handle']);
            $unreadNotifs = $notificationModel->getUnreadNotifCount($_SESSION['Handle']);
            
            $this->Twig->addGlobal('showUnmoderatedCSS', $css);
            $this->Twig->addGlobal('isAdmin', $isAdmin);
            $this->Twig->addGlobal('hasDisprovenCSS', $dispCSS);
            $this->Twig->addGlobal('waitingApprovalCSS', $waitingCSS);
            $this->Twig->addGlobal('unreadNotifs', $unreadNotifs);
            $this->Twig->addGlobal('discordURL', getenv("DISCORD_URL"));
            $this->Twig->addGlobal('lastfmAPI', getenv("LASTFM_API_KEY"));
            $this->Twig->addGlobal('currentPath', $_SERVER["REQUEST_URI"]);

            if($userModel->isBanned()) {
                // redirect ... 
                if($_SERVER["REQUEST_URI"] != "/user_banned" && $_SERVER["REQUEST_URI"] != "/sign_out") {
                    header("Location: /user_banned");
                }
            }
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