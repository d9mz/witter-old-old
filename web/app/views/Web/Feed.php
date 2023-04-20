<?php
namespace Witter\Views;

use Witter\Models\Level;

class Feed extends ViewBase {
    public function View() {
        $feed = new \Witter\Models\Feed();
        $feed = $feed->GetFeed("anybody", 20);

        // UGLY.... Why do ?
        while($weet = $feed) {
            $weets[] = $weet;
        }

        echo $this->Twig->render('user.twig', array(
            "PageSettings" => $this->PageSettings(),
            "Feed" => $weets,
        ));
    }
}