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
    // create database instance -- this shouldn't really be done here but it's
    // a temporary solution to the infinite connection issue
    public function __construct() {
        $connection = new \Witter\Models\Connection();
        $this->Connection = $connection->MakeConnection();
    }

    public function checkIfAdminIfNotError() : void {
        $userModel = new \Witter\Models\User();
        if(!isset($_SESSION['Handle']) || !$userModel->isAdmin($_SESSION['Handle'])) {
            header("HTTP/1.0 404 Not Found");

            $page = new \Witter\Views\Error;
            $page->View();

            die();
        }
    }

    public function BanUser() {
        $alert  = new \Witter\Models\Alert();
        $userModel = new \Witter\Models\User();
        $loggedInUser = $userModel->GetUID($_SESSION['Handle']);
        $user   = $userModel->GetUser($_POST['username']);

        if(isset($_SESSION['Handle']) || $userModel->isAdmin($_SESSION['Handle'])) {
            if($user['admin'] != "f") {
                $alert->CreateAlert(Level::Error, "Are you trying to ban an admin?");
            }

            $stmt = $this->Connection->prepare("INSERT INTO bans (reason, feed_owner, feed_text, feed_embed) VALUES (:reason, :id, :comment, :embed)");

            $stmt->bindParam(":reason", $_POST['reason']);
            $stmt->bindParam(":target", $user['id']);
            $stmt->bindParam(":until", $_POST['until']);
            $stmt->bindParam(":moderator", $loggedInUser);
    
            $stmt->execute();     

            $alert->CreateAlert(Level::Success, "Successfully banned " . $_POST['username'] . "'s profile");
        }
    }

    public function ResetUser() {
        $alert  = new \Witter\Models\Alert();
        $userModel = new \Witter\Models\User();
        $cdnModel = new \Witter\Models\CDN();
        $user   = $userModel->GetUser($_POST['username']);

        if(!isset($user['id'])) {
            $alert->CreateAlert(Level::Error, "This user does not exist!");
        }

        if(isset($_SESSION['Handle']) || $userModel->isAdmin($_SESSION['Handle'])) {
            $stmt = $this->Connection->prepare("UPDATE users SET css = '', moderated_css = 'f' WHERE username = ?");
            $stmt->execute([
                $_POST['username'],
            ]);
            $stmt = null;

            $stmt = $this->Connection->prepare("UPDATE users SET description = '' WHERE username = ?");
            $stmt->execute([
                $_POST['username'],
            ]);
            $stmt = null;

            $stmt = $this->Connection->prepare("UPDATE users SET nickname = ? WHERE username = ?");
            $stmt->execute([
                $_POST['username'],
                $_POST['username'],
            ]);
            $stmt = null;
            
            // delete pfp
            $cachePfp = $cdnModel->GetCacheByOwner($user['id']);
            if(isset($cachePfp['id'])) {
                $cachePfp['data'] = json_decode($cachePfp['data']);

                $stmt = $this->Connection->prepare("DELETE FROM cache WHERE id = ?");
                $stmt->execute([
                    $cachePfp['id'],
                ]);
                $stmt = null;

                unlink("/var/www/volumes/profile_picture/" . $cachePfp['data']->file_name);
            }

            // delete banner
            $cachePfp = $cdnModel->GetCacheByOwner($user['id'], ContentType::Banner);
            if(isset($cachePfp['id'])) {
                $cachePfp['data'] = json_decode($cachePfp['data']);

                $stmt = $this->Connection->prepare("DELETE FROM cache WHERE id = ?");
                $stmt->execute([
                    $cachePfp['id'],
                ]);
                $stmt = null;

                unlink("/var/www/volumes/banner/" . $cachePfp['data']->file_name);
            }

            $alert->CreateAlert(Level::Success, "Successfully reset " . $_POST['username'] . "'s profile");
        }
    }

    public function ApproveCSS() {
        // Get the JSON payload from the POST request
        $json = file_get_contents('php://input');
        $userModel = new \Witter\Models\User();

        if(isset($_SESSION['Handle']) || $userModel->isAdmin($_SESSION['Handle'])) {
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
    }

    public function DisapproveCSS() {
        // Get the JSON payload from the POST request
        $json = file_get_contents('php://input');
        $userModel = new \Witter\Models\User();
        if(isset($_SESSION['Handle']) || $userModel->isAdmin($_SESSION['Handle'])) {
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