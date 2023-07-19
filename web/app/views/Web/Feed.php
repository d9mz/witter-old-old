<?php
namespace Witter\Views;

use Witter\Models\Level;
use Witter\Models\Type;

class Feed extends View {
    public function View() {
        $feedModel = new \Witter\Models\Feed();
        $feed = $feedModel->GetFeed("everyone", 20);
        
        // UGLY.... Why do ?
        echo $this->Twig->render('feed.twig', array(
            "PageSettings" => $this->PageSettings("Feed", "This is your feed."),
            "Feed" => @$feed,
            "ActiveTab" => "all",
        ));
    }

    public function Trending() {
        $feed = new \Witter\Models\Feed();
        $feed = $feed->GetTrendingFeed();

        // UGLY.... Why do ?
        echo $this->Twig->render('feed.twig', array(
            "PageSettings" => $this->PageSettings("Feed", "This is your feed."),
            "Feed" => @$feed,
            "ActiveTab" => "trending",
        ));
    }

    public function Following() {
        $feed = new \Witter\Models\Feed();
        $feed = $feed->GetFollowingFeed($_SESSION['Handle']);

        // UGLY.... Why do ?
        echo $this->Twig->render('feed.twig', array(
            "PageSettings" => $this->PageSettings("Feed", "This is your feed."),
            "Feed" => @$feed,
            "ActiveTab" => "following",
        ));
    }

    public function ViewWeet(string $user, string $weet_id) {
        $weetModel  = new \Witter\Models\Feed();
        $userModel  = new \Witter\Models\User();
        $alert = new \Witter\Models\Alert();
        $feed = new \Witter\Models\Feed();
        if(!filter_var($weet_id, FILTER_VALIDATE_INT)) $alert->CreateAlert(Level::Error, "Invalid Weet ID");
        if(!$weetModel->WeetExists((int)$weet_id)) $alert->CreateAlert(Level::Error, "This weet does not exist.");

        $weet = $weetModel->GetWeet((int)$weet_id, false);

        if($weet['feed_target'] != -1) {
            if(!filter_var($weet_id, FILTER_VALIDATE_INT)) $alert->CreateAlert(Level::Error, "Invalid Weet ID");
            if(!$weetModel->WeetExists((int)$weet_id, false)) $alert->CreateAlert(Level::Error, "This weet does not exist.");
    
            $weet = $weetModel->GetWeet((int)$weet_id, false);
            if(!$userModel->UserExists($weet['feed_owner'], Type::ID)) $alert->CreateAlert(Level::Error, "This weet does not exist.");
    
            // FROM THIS POINT ON BELOW VVVV
            $weet_id_original = $weet_id;
            $thread = $weetModel->getReplyTree($weet_id_original, $weetModel, 8);
    
            // does the owner of that weet actually exist?
            $weets = $feed->GetReplies((int)$weet_id_original, 20, true);
            $user = $userModel->GetUser($weet['feed_owner'], Type::ID);
            $user['following'] = $userModel->FollowingUser($user['id'], $_SESSION['Handle']); // Why the hell do I have to do this?
    
            echo $this->Twig->render('user_related/thread.twig', array(
                "PageSettings" => $this->PageSettings($user['nickname'] . " (@" . $user['username'] . ")", $user['description']),
                "Weet" => @$weet,
                "Target" => @$weet_id_original,
                "Thread" => @$weets,
                "FullThread" => @$thread,
                "User" => @$user,
                "Reply" => true,
            ));
        } else {
            if(!$userModel->UserExists($weet['feed_owner'], Type::ID)) $alert->CreateAlert(Level::Error, "This weet does not exist.");

            $user = $userModel->GetUser($user);
            $user['following'] = $userModel->FollowingUser($user['id'], $_SESSION['Handle']); // Why the hell do I have to do this?

            // no url tampering!
            // does the owner of that weet actually exist?
            if(@$user['id'] != $weet['feed_owner']) $alert->CreateAlert(Level::Error, "This weet does not exist.");

            $weets = $feed->GetReplies((int)$weet_id);
            echo $this->Twig->render('user_related/thread.twig', array(
                "PageSettings" => $this->PageSettings($user['nickname'] . " (@" . $user['username'] . ")", $user['description']),
                "Weet" => @$weet,
                "Thread" => @$weets,
                "User" => @$user,
                "Reply" => false,
            )); 
        }
    }

    public function ViewReply(string $weet_id) {

    }
}