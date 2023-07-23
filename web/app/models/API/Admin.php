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

            die();
        }
    }

    public function ApproveCSS() {
        // Get the JSON payload from the POST request
        $json = file_get_contents('php://input');
        $userModel = new \Witter\Models\User();

        // Decode the JSON into a PHP object
        $data = json_decode($json);

        if(!is_numeric($data->target)) die();
        $user = $userModel->GetUser($data->target, Type::ID);
        
        if(isset($user['id'])) {
            $stmt = $this->Connection->prepare("UPDATE users SET moderated_css = 't' WHERE id = ?");
            $stmt->execute([
                $user['id'],
            ]);
            $stmt = null;
        }

        echo json_encode($data);
    }

    public function DisapproveCSS() {
        // Get the JSON payload from the POST request
        $json = file_get_contents('php://input');
        $userModel = new \Witter\Models\User();

        // Decode the JSON into a PHP object
        $data = json_decode($json);

        if(!is_numeric($data->target)) die();
        $user = $userModel->GetUser($data->target, Type::ID);
        
        if(isset($user['id'])) {
            $stmt = $this->Connection->prepare("UPDATE users SET moderated_css = 'd' WHERE id = ?");
            $stmt->execute([
                $user['id'],
            ]);
            $stmt = null;
        }

        echo json_encode($data);
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