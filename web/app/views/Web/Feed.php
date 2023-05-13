<?php
namespace Witter\Views;

use Witter\Models\Level;

class Feed extends ViewBase {
    public function View() {
        $feed = new \Witter\Models\Feed();
        $feed = $feed->GetFeed("everyone", 20);

        // UGLY.... Why do ?
        echo $this->Twig->render('feed.twig', array(
            "PageSettings" => $this->PageSettings(),
            "Feed" => @$feed,
        ));
    }
}