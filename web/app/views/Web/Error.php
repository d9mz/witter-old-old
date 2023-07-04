<?php
namespace Witter\Views;

class Error extends View {
    public function View() {
        echo $this->Twig->render('404.twig', array(
            "PageSettings" => $this->PageSettings(),
        ));
    }
}