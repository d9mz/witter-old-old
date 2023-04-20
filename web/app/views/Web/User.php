<?php
namespace Witter\Views;

use Witter\Models\Level;

class User extends ViewBase {
    public function View($user) {
        $handle = $user;
        $userclass = new \Witter\Models\User();

        if($userclass->UserExists($handle)) {
            echo $this->Twig->render('user.twig', array(
                "PageSettings" => $this->PageSettings(),
                "User" => $userclass->GetUser($handle),
            ));
        } else {
            $alert = new \Witter\Models\Alert();
            $alert->CreateAlert(Level::Error, "This user does not exist.");
        }
    }
}