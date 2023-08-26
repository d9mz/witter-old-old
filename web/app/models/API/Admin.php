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

    public function UnbanUser() {
        $alert  = new \Witter\Models\Alert();
        $weetModel = new \Witter\Models\Feed();
        $userModel = new \Witter\Models\User();
        $loggedInUser = $userModel->GetUID($_SESSION['Handle']);
        $user   = $userModel->GetUser($_POST['username']);

        if(isset($_SESSION['Handle']) || $userModel->isAdmin($_SESSION['Handle'])) {
            if(!$userModel->isBannedTarget($user['id'])) {
                $alert->CreateAlert(Level::Error, "This user is currently not banned.");
            }

            $stmt = $this->Connection->prepare("DELETE FROM bans WHERE target = ?");
            $stmt->execute(
                [
                    $user['id'],
                ]
            );

            $alert->InternalLog(Level::Info, "unbanned @" . $user['username']);
            $alert->CreateAlert(Level::Success, "Successfully unbanned " . $_POST['username'] . "'s profile");
        }
    }

    public function BanUser() {
        $alert  = new \Witter\Models\Alert();
        $weetModel = new \Witter\Models\Feed();
        $userModel = new \Witter\Models\User();
        $loggedInUser = $userModel->GetUID($_SESSION['Handle']);
        $user   = $userModel->GetUser($_POST['username']);

        if(isset($_SESSION['Handle']) || $userModel->isAdmin($_SESSION['Handle'])) {
            if($user['admin'] != "f") {
                $alert->CreateAlert(Level::Error, "Are you trying to ban an admin?");
            }

            if($userModel->isBannedTarget($user['id'])) {
                $alert->CreateAlert(Level::Error, "This user is already banned! Unban them first.");
            }

            $stmt = $this->Connection->prepare("INSERT INTO bans (reason, target, until, moderator) VALUES (:reason, :target, :until, :moderator)");

            $stmt->bindParam(":reason", $_POST['reason']);
            $stmt->bindParam(":target", $user['id']);
            $stmt->bindParam(":until", $_POST['until']);
            $stmt->bindParam(":moderator", $loggedInUser);
    
            $stmt->execute();

            if(isset($_POST['content']) && !empty(trim($_POST['content']))) {
                $content = explode(" ", $_POST['content']);
                $contents = [];

                foreach($content as $link) {
                    $link = $weetModel->GetWitterLinksInWeet($link);
                    if(isset($link[0])) {
                        array_push($contents, $link[1]);
                    }
                }

                $content = implode(" ", $contents);
        
                $stmt = $this->Connection->prepare("UPDATE bans SET offending_content = ? WHERE target = ?");
                $stmt->execute([
                    $content,
                    $user['id'],
                ]);
                $stmt = null;
            }

            $alert->InternalLog(Level::Info, "banned @" . $user['username'] . " until " . $_POST['until']. "\nreason: " . $_POST['reason']);
            $alert->CreateAlert(Level::Success, "Successfully banned " . $_POST['username'] . "'s profile");
        }
    }

    public function DeletePost() {
        $alert  = new \Witter\Models\Alert();
        $feedModel = new \Witter\Models\Feed();
        $userModel = new \Witter\Models\User();
        $feed   = $feedModel->GetWeet($_POST['id'], false);

        if(!isset($feed['id'])) {
            $alert->CreateAlert(Level::Error, "This post does not exist!");
        }

        if(isset($_SESSION['Handle']) || $userModel->isAdmin($_SESSION['Handle'])) {
            $stmt = $this->Connection->prepare("DELETE FROM feed WHERE feed_id = ?");
            $stmt->execute([
                $_POST['id'],
            ]);
            $stmt = null;

            $alert->CreateAlert(Level::Success, "Successfully deleted weet id #" . $_POST['id']);
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