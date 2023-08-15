<?php
namespace Witter\Views;

use Witter\Models\Level;
use Witter\Models\Type;

class Search extends View {
    public function Search() {
        $alertClass = new \Witter\Models\Alert();
        $searchModel = new \Witter\Models\Search();
        $searchFlags = new \Witter\Models\SearchFlags();

        if(!isset($_GET['q']) || empty(trim($_GET['q']))) $alertClass->CreateAlert(Level::Error, "You did not provide a search query.");
        
        // bitwise flags for search,
        $searchFlags->setSearchingForHashtags(isset($_GET['hashtags']));
        $searchFlags->setSearchingForUsers(isset($_GET['users']));
        $searchFlags->setSearchingForWeets(isset($_GET['weets']));
        
        $results = $searchModel->getSearchQuery($_GET['q'], $searchFlags->getFlagsAsObject());
        
        // UGLY.... Why do ?
        echo $this->Twig->render('search.twig', array(
            "PageSettings" => $this->PageSettings("Search", "Searching..."),
            "Results" => @$results,
        ));
    }
}