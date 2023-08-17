<?php
namespace Witter\Views;

use Witter\Models\Level;

class Settings extends View {
    public function View() {
        $user = new \Witter\Models\User();
        $user = $user->GetUser($_SESSION['Handle']);

        echo $this->Twig->render('settings.twig', array(
            "PageSettings" => $this->PageSettings("Settings", "Settings page"),
            "User" => $user,
            "ActiveTab" => "general",
        ));
    }
}