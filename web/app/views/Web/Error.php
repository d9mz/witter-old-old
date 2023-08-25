<?php
namespace Witter\Views;

class Error extends View {
    public function View() {
        echo $this->Twig->render('misc/404.twig', array(
            "PageSettings" => $this->PageSettings(),
        ));
    }

    public function JacksDen() {
        echo "<h1>Not Found</h1>";
        /*
        echo $this->Twig->render('misc/404.twig', array(
            "PageSettings" => $this->PageSettings(),
        ));
        */
    }
}