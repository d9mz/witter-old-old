<?php
namespace Witter\Views;

use Witter\Models\Level;
use Witter\Models\Type;

class Feed extends View {
    public function View() {
        $feed = new \Witter\Models\Feed();
        $feed = $feed->GetFeed("everyone", 20);

        // UGLY.... Why do ?
        echo $this->Twig->render('feed.twig', array(
            "PageSettings" => $this->PageSettings("Feed", "This is your feed."),
            "Feed" => @$feed,
            "ActiveTab" => "all",
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

    public function ViewWeet(string $user, string $weet_id) {
        $weet  = new \Witter\Models\Feed();
        $userModel  = new \Witter\Models\User();
        $alert = new \Witter\Models\Alert();
        $feed = new \Witter\Models\Feed();
        if(!filter_var($weet_id, FILTER_VALIDATE_INT)) $alert->CreateAlert(Level::Error, "Invalid Weet ID");
        if(!$weet->WeetExists((int)$weet_id)) $alert->CreateAlert(Level::Error, "This weet does not exist.");

        $weet = $weet->GetWeet((int)$weet_id);

        if(!$userModel->UserExists($weet['feed_owner'], Type::ID)) $alert->CreateAlert(Level::Error, "This weet does not exist.");

        $user = $userModel->GetUser($user);

        // no url tampering!
        if(@$user['id'] != $weet['feed_owner']) $alert->CreateAlert(Level::Error, "This weet does not exist.");

        // does the owner of that weet actually exist?

        $weets = $feed->GetReplies((int)$weet_id);

        echo $this->Twig->render('thread.twig', array(
            "PageSettings" => $this->PageSettings($user['nickname'] . " (@" . $user['username'] . ")", $user['description']),
            "Weet" => @$weet,
            "Thread" => @$weets,
        ));
    }

    public function ViewReply(string $user, string $weet_id) {
        $weetModel  = new \Witter\Models\Feed();
        $userModel  = new \Witter\Models\User();
        $alert = new \Witter\Models\Alert();
        $feed = new \Witter\Models\Feed();
        if(!filter_var($weet_id, FILTER_VALIDATE_INT)) $alert->CreateAlert(Level::Error, "Invalid Weet ID");
        if(!$weetModel->WeetExists((int)$weet_id)) $alert->CreateAlert(Level::Error, "This weet does not exist.");

        $weet = $weetModel->GetReply((int)$weet_id);
        $weet = $weetModel->mapWeetToReply($weet, false);

        if(!$userModel->UserExists($weet['feed_owner'], Type::ID)) $alert->CreateAlert(Level::Error, "This weet does not exist.");

        $user = $userModel->GetUser($user);

        // no url tampering!
        if(@$user['id'] != $weet['feed_owner']) $alert->CreateAlert(Level::Error, "This weet does not exist.");

        // does the owner of that weet actually exist?

        $weets = $feed->GetReplies((int)$weet_id);

        echo $this->Twig->render('thread.twig', array(
            "PageSettings" => $this->PageSettings($user['nickname'] . " (@" . $user['username'] . ")", $user['description']),
            "Weet" => @$weet,
            "Thread" => @$weets,
        ));
    }
}