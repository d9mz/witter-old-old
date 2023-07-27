<?php
namespace Witter\Views;

class Notification extends View {
    public function View() {
        echo $this->Twig->render('user_related/notifications.twig', array(
            "PageSettings" => $this->PageSettings("Notifications", "Notifications page"),
        ));
    }
}