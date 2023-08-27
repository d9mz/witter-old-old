<?php
namespace Witter\Views;

use Witter\Models\Level;

class Settings extends View {
    public function View() {
        $userModel = new \Witter\Models\User();
        $settingsModel = new \Witter\Models\Settings();
        $fmModel = new \Witter\Models\LastFM();

        if(isset($_GET['token'])) {
            $settingsModel->UpdateLastFMToken($_GET['token']);
        }

        $user = $userModel->GetUser($_SESSION['Handle']);
        
        echo $this->Twig->render('settings.twig', array(
            "PageSettings" => $this->PageSettings("Settings", "Settings page"),
            "User" => $user,
            "ActiveTab" => "general",
        ));
    }
}