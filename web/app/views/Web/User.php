<?php
namespace Witter\Views;

use Witter\Models\Level;

class User extends View {
    public function View($user) {
        $handle = $user;
        $userclass = new \Witter\Models\User();
        $user = $userclass->GetUser($user);

        if($userclass->UserExists($handle)) {
            $feed = new \Witter\Models\Feed();
            $feed = $feed->GetFeed($handle, 20);

            echo $this->Twig->render('user.twig', array(
                "PageSettings" => $this->PageSettings(),
                "User" => $userclass->GetUser($handle),
                "Feed" => $feed,
            ));
        } else {
            $alert = new \Witter\Models\Alert();
            $alert->CreateAlert(Level::Error, "This user does not exist.");
        }
    }
}