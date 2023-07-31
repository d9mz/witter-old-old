<?php
namespace Witter\Views;

class Notification extends View {
    public function View() {
        $notificationModel = new \Witter\Models\Notifications;
        $notifications = $notificationModel->getUnreadNotifications($_SESSION['Handle']);

        echo $this->Twig->render('user_related/notifications.twig', array(
            "PageSettings" => $this->PageSettings("Notifications", "Notifications page"),
            "Notifications" => $notifications
        ));
    }
}