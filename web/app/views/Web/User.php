<?php
namespace Witter\Views;

use Witter\Models\Level;
use Witter\Models\Type;

class User extends View {
    public function Followers($user) {
        $handle = $user;
        $userclass = new \Witter\Models\User();
        $user = $userclass->GetUser($user);

        if($userclass->UserExists($handle)) {
            $followers = $userclass->GetUserMetricFollow($handle);

            echo $this->Twig->render('user_related/user_follower_following.twig', array(
                "PageSettings" => $this->PageSettings($user['nickname'] . " (@" . $user['username'] . ")", $user['description']),
                "User" => $user,
                "Followers" => @$followers,
                "ActiveTab" => "followers",
            ));
        } else {
            $alert = new \Witter\Models\Alert();
            $alert->CreateAlert(Level::Error, "This user does not exist.");
        }
    }

    public function Following($user) {
        $handle = $user;
        $userclass = new \Witter\Models\User();
        $user = $userclass->GetUser($user);

        if($userclass->UserExists($handle)) {
            $following = $userclass->GetUserMetricFollow($handle, false);

            echo $this->Twig->render('user_related/user_follower_following.twig', array(
                "PageSettings" => $this->PageSettings($user['nickname'] . " (@" . $user['username'] . ")", $user['description']),
                "User" => $user,
                "Following" => @$following,
                "ActiveTab" => "following",
            ));
        } else {
            $alert = new \Witter\Models\Alert();
            $alert->CreateAlert(Level::Error, "This user does not exist.");
        }
    }

    public function View($user) {
        $handle = $user;
        $userclass = new \Witter\Models\User();
        $user = $userclass->GetUser($user);

        if($userclass->UserExists($handle)) {
            $feed = new \Witter\Models\Feed();
            $feed = $feed->GetFeed($handle, 20);
            $user = $userclass->GetUser($handle);

            // is the logged in user following the current user?
            if(isset($_SESSION['Handle'])) {
                $following = new \Witter\Models\User();
                $user['following'] = $following->FollowingUser($user['id'], $_SESSION['Handle']);
            } else {
                $user['following'] = false;
            }

            echo $this->Twig->render('user_related/user.twig', array(
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
            
            // is the logged in user following the current user?
            if(isset($_SESSION['Handle'])) {
                $following = new \Witter\Models\User();
                $user['following'] = $following->FollowingUser($user['id'], $_SESSION['Handle']);
            } else {
                $user['following'] = false;
            }

            echo $this->Twig->render('user_related/user.twig', array(
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