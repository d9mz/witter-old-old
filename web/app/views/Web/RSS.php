<?php
namespace Witter\Views;

use Witter\Models\Level;
use Witter\Models\Type;

class RSS extends View {
    public function View() {
        if(!isset($_GET['page'])) { $page = 1; } else { $page = $_GET['page']; }
        $feedModel = new \Witter\Models\Feed();
        $feed = $feedModel->GetFeedScrolling($page, 20, true);
        
        // UGLY.... Why do ?
        echo $this->Twig->render('dynamic/rss.twig', array(
            "Feed" => @$feed,
            "Page" => $page,
        ));
    }
}