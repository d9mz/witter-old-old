<?php
namespace Witter\Views;

class Example extends View {
    public function View() {
        echo $this->Twig->render('NotFound.twig', array(
            "PageSettings" => $this->PageSettings(),
        ));
    }
}