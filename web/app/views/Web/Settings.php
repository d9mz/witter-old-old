<?php
namespace Witter\Views;

use Witter\Models\Level;

class Settings extends View {
    public function View() {
        $userModel = new \Witter\Models\User();
        $settingsModel = new \Witter\Models\Settings();

        $user = $userModel->GetUser($_SESSION['Handle']);

        if(isset($_GET['token'])) {
            $settingsModel->UpdateLastFMToken($_GET['token']);
        }

        echo $this->Twig->render('settings.twig', array(
            "PageSettings" => $this->PageSettings("Settings", "Settings page"),
            "User" => $user,
            "ActiveTab" => "general",
        ));
    }
}