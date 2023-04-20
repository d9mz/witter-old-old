<?php
namespace Witter\Views;

class Homepage extends ViewBase {
    public function View() {
        echo $this->Twig->render('homepage.twig', array(
            "PageSettings" => $this->PageSettings(),
        ));
    }

    public function Redirect() {
        header("Location: /feed");
        die();
    }
}