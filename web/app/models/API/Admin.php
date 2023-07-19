<?php
namespace Witter\Models;

enum ModeratableTypes : string {
    case CSS = "CSS";
    case ProfilePicture = "ProfilePicture";
    case Banner = "Banner";
    case Description = "Description";
    case Post = "Post";
}

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

    // Create a general all-purpose function for the enum
    // We're gonna have to make some pagination for this so better to do this now than later
    public function getUnmoderatedItems(ModeratableTypes $moderationType, int $page = 0) : array {
        if($moderationType == ModeratableTypes::CSS) {
            $userModel = new \Witter\Models\User();

            $items = $this->Connection->prepare("SELECT username FROM users WHERE moderated_css = 'f' ORDER BY id DESC");
            $items->execute();

            while ($item = $items->fetch(\PDO::FETCH_ASSOC)) {
                $users[] = $userModel->GetUser($item['username'], Type::Username, true);
            }
            
            return $users;
        }

        // shouldn't happen ... "default"
        return [];
    }
}