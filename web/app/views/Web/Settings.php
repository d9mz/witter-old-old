<?php
namespace Witter\Views;

use Witter\Models\Level;

class Settings extends View {
    public function View() {
        $userModel = new \Witter\Models\User();
        $settingsModel = new \Witter\Models\Settings();
        $fmModel = new \Witter\Models\LastFM();
        $util = new \Witter\Models\Utility();

        $countries = $util->getAllCountries();

        if(isset($_GET['token'])) {
            $settingsModel->UpdateLastFMToken($_GET['token']);
        }

        $user = $userModel->GetUser($_SESSION['Handle']);
        
        echo $this->Twig->render('settings/settings.twig', array(
            "PageSettings" => $this->PageSettings("Settings", "Settings page"),
            "User" => $user,
            "ActiveTab" => "general",
            "Countries" => $countries,
        ));
    }

    public function Privacy() {
        $userModel = new \Witter\Models\User();
        $settingsModel = new \Witter\Models\Settings();
        $fmModel = new \Witter\Models\LastFM();
        $util = new \Witter\Models\Utility();

        $privacy = $util->getAllPrivacyOptions();

        if(isset($_GET['token'])) {
            $settingsModel->UpdateLastFMToken($_GET['token']);
        }

        $user = $userModel->GetUser($_SESSION['Handle']);
        
        echo $this->Twig->render('settings/privacy.twig', array(
            "PageSettings" => $this->PageSettings("Settings", "Settings page"),
            "User" => $user,
            "ActiveTab" => "privacy",
            "Privacy" => $privacy,
        ));
    }

    public function Blocked() {
        $userModel = new \Witter\Models\User();
        $settingsModel = new \Witter\Models\Settings();
        $fmModel = new \Witter\Models\LastFM();
        $util = new \Witter\Models\Utility();

        if(isset($_GET['token'])) {
            $settingsModel->UpdateLastFMToken($_GET['token']);
        }

        $user = $userModel->GetUser($_SESSION['Handle']);
        $blocked = $userModel->getBlockedUsers($_SESSION['Handle']);
        
        echo $this->Twig->render('settings/blocked.twig', array(
            "PageSettings" => $this->PageSettings("Settings", "Settings page"),
            "User" => $user,
            "Blocked" => $blocked,
            "ActiveTab" => "blocked",
        ));
    }
}