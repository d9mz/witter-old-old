<?php
namespace Witter\Models;

use Intervention\Image\ImageManager;

class Admin extends Model
{
    public function checkIfAdminIfNotError() : void {
        $userModel = new \Witter\Models\User();
        if(!isset($_SESSION['Handle']) || !$userModel->isAdmin($_SESSION['Handle'])) {
            header("HTTP/1.0 404 Not Found");

            $page = new \Witter\Views\Error;
            $page->View();
        }
    }

    public function ModerateCSS() {
        $alert  = new \Witter\Models\Alert();
        $user   = new \Witter\Models\User();
        $user   = $user->GetUser($_SESSION['Handle']);

        if (!isset($_POST['description']) && !empty(trim($_POST['description']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a description.");
        }

        if (strlen($_POST['description']) < 4 || strlen($_POST['description']) > 200) {
            $alert->CreateAlert(Level::Error, "Your description must be longer than 3 characters and not longer than 200.");
        }

        $stmt = $this->Connection->prepare("UPDATE users SET description = ? WHERE id = ?");
        $stmt->execute([
            $_POST['description'],
            $user['id'],
        ]);
        $stmt = null;

        $alert->CreateAlert(Level::Success, "Successfully set your description.");
    }
}