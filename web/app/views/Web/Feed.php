<?php
namespace Witter\Views;

use Witter\Models\Level;

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
}