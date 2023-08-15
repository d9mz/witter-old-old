<?php
namespace Witter\Views;

use Witter\Models\Level;
use Witter\Models\Type;

class Search extends View {
    public function Search() {
        $alertClass = new \Witter\Models\Alert();
        $feedModel = new \Witter\Models\Feed();
        $feed = $feedModel->GetFeed("everyone", 10);

        if(!isset($_GET['q']) || empty(trim($_GET['q']))) $alertClass->CreateAlert(Level::Error, "You did not provide a search query.");
        
        // UGLY.... Why do ?
        echo $this->Twig->render('search.twig', array(
            "PageSettings" => $this->PageSettings("Search", "Searching..."),
            "Feed" => @$feed,
            "ActiveTab" => "all",
        ));
    }
}