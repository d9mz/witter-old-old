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
            $user = $userclass->GetUser($handle);

            echo $this->Twig->render('user.twig', array(
                "PageSettings" => $this->PageSettings($user['nickname'] . " (@" . $user['username'] . ")", $user['description']),
                "User" => $user,
                "Feed" => $feed,
                "ActiveTab" => "all",
            ));
        } else {
            $alert = new \Witter\Models\Alert();
            $alert->CreateAlert(Level::Error, "This user does not exist.");
        }
    }

    public function Likes($user) {
        $handle = $user;
        $userclass = new \Witter\Models\User();
        $user = $userclass->GetUser($user);

        if($userclass->UserExists($handle)) {
            $feed = new \Witter\Models\Feed();
            $feed = $feed->GetLikedPostsByUser($handle, 20);
            $user = $userclass->GetUser($handle);

            echo $this->Twig->render('user.twig', array(
                "PageSettings" => $this->PageSettings($user['nickname'] . " (@" . $user['username'] . ")", $user['description']),
                "User" => $user,
                "Feed" => $feed,
                "ActiveTab" => "likes",
            ));
        } else {
            $alert = new \Witter\Models\Alert();
            $alert->CreateAlert(Level::Error, "This user does not exist.");
        }
    }
}