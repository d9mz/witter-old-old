<?php
namespace Witter\Views;

class Scrolling extends View {
    // create database instance -- this shouldn't really be done here but it's
    // a temporary solution to the infinite connection issue
    public function GetWeets($page) {
        // 10 weets per scroll
        $feedModel = new \Witter\Models\Feed();
        $weets = $feedModel->GetFeedScrolling($page, 10);
        
        // UGLY.... Why do ?
        echo $this->Twig->render('dynamic/feed.twig', array(
            "Feed" => @$weets,
        ));
    }
}