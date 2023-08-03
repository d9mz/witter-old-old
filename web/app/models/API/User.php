<?php
namespace Witter\Models;

use Witter\Models\NotificationTypes;

enum Type: int {
    case ID = 0;
    case Username = 1;
    case Nickname = 2;
}

class User extends Model
{
    // create database instance -- this shouldn't really be done here but it's
    // a temporary solution to the infinite connection issue
    public function __construct() {
        $connection = new \Witter\Models\Connection();
        $this->Connection = $connection->MakeConnection();
    }
    
    public function showUnmoderatedCSS(string $user) : bool {
        $query = $this->Connection->prepare("SELECT hide_css FROM users WHERE username = :user");
        $query->bindParam(":user", $user);
        $query->execute();
        $css = $query->fetch();

        // this switch thing is really confusing the shit out of me
        if(trim($css['hide_css']) == "t") {
            return false;
        } else {
            return true;
        }
    }

    // using strings for bools suck dookie
    public function isAdmin(int | string $user) : bool {
        $query = $this->Connection->prepare("SELECT admin FROM users WHERE username = :user");
        $query->bindParam(":user", $user);
        $query->execute();
        $admin = $query->fetch();

        if(trim($admin['admin']) == "t") {
            return true;
        } else {
            return false;
        }
    }

    // this shit STINKS
    public function isCSSUnapproved(int | string $user) : bool {
        $query = $this->Connection->prepare("SELECT moderated_css FROM users WHERE username = :user");
        $query->bindParam(":user", $user);
        $query->execute();
        $admin = $query->fetch();

        if(trim($admin['moderated_css']) == "d") {
            return true;
        } else {
            return false;
        }
    }

    public function isCSSWaiting(int | string $user) : bool {
        $query = $this->Connection->prepare("SELECT moderated_css FROM users WHERE username = :user");
        $query->bindParam(":user", $user);
        $query->execute();
        $admin = $query->fetch();

        if(trim($admin['moderated_css']) == "f") {
            return true;
        } else {
            return false;
        }
    }

    public function IsMutuals(int $userAId, int $userBId) : bool {
        // Checking if User A is following User B
        $stmtA = $this->Connection->prepare("SELECT * FROM followers WHERE user = :idA AND target = :idB");
        $stmtA->bindParam(":idA", $userAId);
        $stmtA->bindParam(":idB", $userBId);
        $stmtA->execute();
    
        // Checking if User B is following User A
        $stmtB = $this->Connection->prepare("SELECT * FROM followers WHERE user = :idB AND target = :idA");
        $stmtB->bindParam(":idA", $userAId);
        $stmtB->bindParam(":idB", $userBId);
        $stmtB->execute();
    
        // If both queries return a result, then they are mutuals
        return $stmtA->rowCount() > 0 && $stmtB->rowCount() > 0;
    }

    public function isOomf(int $userID) : bool {
        // assuming we're checking if oomf of current logged in user
        $currentUser = $this->GetUID($_SESSION['Handle']);

        // echo "(" . $currentUser . ")-(" . $userID . ")<br>";

        $stmt = $this->Connection->prepare("SELECT id FROM followers WHERE user = :idA AND target = :idB");
        $stmt->bindParam(":idA", $userID);
        $stmt->bindParam(":idB", $currentUser);
        $stmt->execute();

        return $stmt->rowCount() === 1;
    }
    
    public function GetFollowerFollowingCount(int $uid) : array {
        $followerStmt = $this->Connection->prepare("SELECT * FROM followers WHERE target = :id");
        $followerStmt->bindParam(":id", $uid);
        $followerStmt->execute();
    
        $followingStmt = $this->Connection->prepare("SELECT * FROM followers WHERE user = :id");
        $followingStmt->bindParam(":id", $uid);
        $followingStmt->execute();
    
        return [
            "follower_count" => $followerStmt->rowCount(),
            "following_count" => $followingStmt->rowCount(),
        ];
    }

    public function GetUserMetricFollow(int | string $user, bool $follower = true) : array {
        if(is_int($user)) $userData = $this->GetUser($user, Type::ID);
        if(!is_int($user)) $userData = $this->GetUser($user, Type::Username);
        if(!isset($userData['id'])) return []; // THIS should not happen.
        
        if($follower) $query = "SELECT * FROM followers WHERE target = :id";
        if(!$follower) $query = "SELECT * FROM followers WHERE user = :id";
        
        $followerStmt = $this->Connection->prepare($query);
        $followerStmt->bindParam(":id", $userData['id']);
        $followerStmt->execute();

        $followers = [];
        while ($follower = $followerStmt->fetch(\PDO::FETCH_ASSOC)) {
            if($follower) { 
                $user_follower = $this->GetUser($follower['target'], Type::ID);
                $user_follower['following'] = isset($_SESSION['Handle']) ? $this->FollowingUser((int)$follower['user'], $_SESSION['Handle']) : false;
                if($user_follower['id'] == $userData['id']) {
                    // what the fuck
                    $user_follower = $this->GetUser($follower['user'], Type::ID);
                    $user_follower['following'] = isset($_SESSION['Handle']) ? $this->FollowingUser((int)$follower['target'], $_SESSION['Handle']) : false;
                }
            } elseif (!$follower) {
                $user_follower = $this->GetUser($follower['user'], Type::ID);
                $user_follower['following'] = isset($_SESSION['Handle']) ? $this->FollowingUser((int)$follower['user'], $_SESSION['Handle']) : false;
            } 
            
            $followers[] = $user_follower;
        } 

        return $followers;
    }
    
    public function FollowingUser(string|int $target, string|int $user) : bool {
        $userModel = new \Witter\Models\User();

        // TODO: UGLY!! Ugly as shit
        if(is_int($target)) $userTarget = $userModel->GetUID($target, Type::ID);
        if(!is_int($target)) $userTarget = $userModel->GetUID($target, Type::Username);
        if(!isset($userTarget)) return true; // THIS should not happen. Do not proceed at all
        
        if(is_int($user)) $userData = $userModel->GetUID($user, Type::ID);
        if(!is_int($user)) $userData = $userModel->GetUID($user, Type::Username);
        if(!isset($userData)) return true; // THIS should not happen. Do not proceed at all

        if($userData == $userTarget) return true; // No following yourself...

        $query = $this->Connection->prepare("SELECT * FROM followers WHERE user = :user AND target = :target");
        $query->bindParam(":target", $userTarget);
        $query->bindParam(":user", $userData);
        $query->execute();
        $follow = $query->fetch();

        if (isset($follow['id'])) {
            return true;
        } else {
            return false;
        }
    }

    public function FollowingUser(string|int $target, string|int $user) : bool {
        $userModel = new \Witter\Models\User();

        // TODO: UGLY!! Ugly as shit
        if(is_int($target)) $userTarget = $userModel->GetUID($target, Type::ID);
        if(!is_int($target)) $userTarget = $userModel->GetUID($target, Type::Username);
        if(!isset($userTarget)) return true; // THIS should not happen. Do not proceed at all
        
        if(is_int($user)) $userData = $userModel->GetUID($user, Type::ID);
        if(!is_int($user)) $userData = $userModel->GetUID($user, Type::Username);
        if(!isset($userData)) return true; // THIS should not happen. Do not proceed at all

        if($userData == $userTarget) return true; // No following yourself...

        $query = $this->Connection->prepare("SELECT * FROM followers WHERE user = :user AND target = :target");
        $query->bindParam(":target", $userTarget);
        $query->bindParam(":user", $userData);
        $query->execute();
        $follow = $query->fetch();

        if (isset($follow['id'])) {
            return true;
        } else {
            return false;
        }
    }

    // accidental-recursion safe
    // REALLY shouldn't have to be doing this
    public function SafeFollowingUser(string|int $target, string|int $user) : bool {
        $userModel = new \Witter\Models\User();

        // TODO: UGLY!! Ugly as shit
        if(is_int($target)) $userTarget = $userModel->GetUID($target, Type::ID);
        if(!is_int($target)) $userTarget = $userModel->GetUID($target, Type::Username);
        if($userTarget == -1) return true; // THIS should not happen. Do not proceed at all


        if(is_int($user)) $userData = $userModel->GetUID($user, Type::ID);
        if(!is_int($user)) $userData = $userModel->GetUID($user, Type::Username);
        if($userData == -1) return true; // THIS should not happen. Do not proceed at all

        if($userData == $userTarget) return true; // No following yourself...

        $query = $this->Connection->prepare("SELECT * FROM followers WHERE user = :user AND target = :target");
        $query->bindParam(":target", $userTarget);
        $query->bindParam(":user", $userData);
        $query->execute();
        $follow = $query->fetch();

        if (isset($follow['id'])) {
            return true;
        } else {
            return false;
        }
    }


    public function Follow(string $uid) {
        // Get the JSON payload from the POST request
        $json = file_get_contents('php://input');
        $notificationsModel = new \Witter\Models\Notifications;

        // Decode the JSON into a PHP object
        $data = json_decode($json);

        $user = $this->GetUser($_SESSION['Handle'], Type::Username);
        if($this->UserExists($uid, Type::ID)) {
            $target = $this->GetUser($uid, Type::ID);
        } else {
            $response = array('status' => 'fail', 'action' => 'user_nonexistant');
        }

        if($this->FollowingUser((int)$uid, $_SESSION['Handle'])) {
            $stmt = $this->Connection->prepare("DELETE FROM followers WHERE target = ? AND user = ?");
            $stmt->execute(
                [
                    $uid,
                    $user['id'],
                ]
            );

            $response = array('status' => 'success', 'action' => 'follow');
        } else {
            $notificationsModel->CreateNotification(NotificationTypes::UserFollowed, [], $target['id'], $user['id'], "user-plus");

            // wtf? todo: unfinished code?
            if($target['private'] == "t") {
                $stmt = $this->Connection->prepare(
                    "INSERT INTO followers
                        (target, user) 
                    VALUES 
                        (?, ?)"
                );
                $stmt->execute([
                    $uid,
                    $user['id'],
                ]);
    
                $response = array('status' => 'requested', 'action' => 'unfollow');
            } else {
                $stmt = $this->Connection->prepare(
                    "INSERT INTO followers
                        (target, user) 
                    VALUES 
                        (?, ?)"
                );
                $stmt->execute([
                    $uid,
                    $user['id'],
                ]);
    
                $response = array('status' => 'requested', 'action' => 'unfollow');
            }
        }

        echo json_encode($response);
    }

    public function Block(string $uid) {
        // Get the JSON payload from the POST request
        $json = file_get_contents('php://input');
        $notificationsModel = new \Witter\Models\Notifications;

        // Decode the JSON into a PHP object
        $data = json_decode($json);

        $user = $this->GetUser($_SESSION['Handle'], Type::Username);
        if($this->UserExists($uid, Type::ID)) {
            $target = $this->GetUser($uid, Type::ID);
        } else {
            $response = array('status' => 'fail', 'action' => 'user_nonexistant');
        }

        if($this->FollowingUser((int)$uid, $_SESSION['Handle'])) {
            $stmt = $this->Connection->prepare("DELETE FROM followers WHERE target = ? AND user = ?");
            $stmt->execute(
                [
                    $uid,
                    $user['id'],
                ]
            );

            $response = array('status' => 'success', 'action' => 'blocked');
        } else {
            $notificationsModel->CreateNotification(NotificationTypes::UserFollowed, [], $target['id'], $user['id'], "user-plus");

            // wtf? todo: unfinished code?
            if($target['private'] == "t") {
                $stmt = $this->Connection->prepare(
                    "INSERT INTO followers
                        (target, user) 
                    VALUES 
                        (?, ?)"
                );
                $stmt->execute([
                    $uid,
                    $user['id'],
                ]);
    
                $response = array('status' => 'requested', 'action' => 'unblocked');
            } else {
                $stmt = $this->Connection->prepare(
                    "INSERT INTO followers
                        (target, user) 
                    VALUES 
                        (?, ?)"
                );
                $stmt->execute([
                    $uid,
                    $user['id'],
                ]);
    
                $response = array('status' => 'requested', 'action' => 'unblocked');
            }
        }

        echo json_encode($response);
    }

    public function GetUID($user, Type $type = Type::Username) : int {
        $type = match ($type) {
            Type::ID => "id",
            Type::Username => "handle",
            Type::Nickname => "nickname",
        };

        if($type == "id") {
            $query = "SELECT id FROM users WHERE id = :find";
        } elseif($type == "handle") {
            $query = "SELECT id FROM users WHERE username = :find";
        } elseif($type == "nickname") {
            $query = "SELECT id FROM users WHERE nickname = :find";
        }

        $stmt = $this->Connection->prepare($query);
        $stmt->bindParam(":find", $user);
        $stmt->execute();

        $user = $stmt->rowCount() === 0 ? 0 : $stmt->fetch(\PDO::FETCH_ASSOC);

        if(isset($user['id'])) {
            return $user['id'];
        } else {
            return -1;
        }
    }

    // worst function ever?
    // try to make a database class down the line -- this REALLY sucks
    // try implementing string : array so if array, implode(", ") etc etc for SELECT from

    public function getSingleColumnFromTable(string $column, string $table, string $where, string $where_equals) {
        $query = "SELECT " . $column . " FROM " . $table . " WHERE " . $where . " = :find";

        $stmt = $this->Connection->prepare($query);
        $stmt->bindParam(":find", $where_equals);
        $stmt->execute();

        $find = $stmt->rowCount() === 0 ? 0 : $stmt->fetch(\PDO::FETCH_ASSOC);

        if(isset($find[$column])) {
            return $find[$column];
        } else {
            return -1;
        }
    }

    // vvv Type type = Type ??? Looks weird but whatever
    public function GetUser($user, Type $type = Type::Username, bool $optimized = false) : array {
        $type = match ($type) {
            Type::ID => "id",
            Type::Username => "handle",
            Type::Nickname => "nickname",
        };

        if($type == "id") {
            $query = "SELECT * FROM users WHERE id = :find";
        } elseif($type == "handle") {
            $query = "SELECT * FROM users WHERE username = :find";
        } elseif($type == "nickname") {
            $query = "SELECT * FROM users WHERE nickname = :find";
        }

        $stmt = $this->Connection->prepare($query);
        $stmt->bindParam(":find", $user);
        $stmt->execute();

        $user = $stmt->rowCount() === 0 ? 0 : $stmt->fetch(\PDO::FETCH_ASSOC);

        if(isset($user['id'])) {
            // profile picture
            $cdn = new \Witter\Models\CDN();
            $cdn = $cdn->GetCacheByOwner($user['id'], ContentType::ProfilePicture);
            if (isset($cdn['data'])) {
                $cdn = json_decode($cdn['data']);
                $cdn = $cdn->file_name;
            } else {
                $cdn = "default";
            }

            $user['profile_picture'] = $cdn;

            // empty description?
            if(empty(trim($user['description']))) $user['description'] = "Hello!";

            // banner
            $cdn = new \Witter\Models\CDN();
            $cdn = $cdn->GetCacheByOwner($user['id'], ContentType::Banner);
            if (isset($cdn['data'])) {
                $cdn = json_decode($cdn['data']);
                $cdn = $cdn->file_name;
            } else {
                $cdn = "";
            }

            $user['banner'] = $cdn;

            // "viewable"? private user thing
            $user['visible'] = true;

            if(!$optimized) {
                $user['oomf'] = false;
                
                if(isset($_SESSION['Handle'])) {
                    if(!$this->SafeFollowingUser($_SESSION['Handle'], $user['id']) && $user['private'] == "t") $user['visible'] = false;
                    if($this->SafeFollowingUser($_SESSION['Handle'], $user['id']) && $user['private'] == "t") $user['visible'] = true;
                    if($_SESSION['Handle'] == $user['username']) $user['visible'] = true;

                    // oomf checking
                    if($this->isOomf($user['id'])) $user['oomf'] = true;
                }

                // is the logged in user following this user?
                // can't do this here because it causes a memory leak ????

                // get follower & follow count & make it properties of $user
                $user['metrics'] = $this->GetFollowerFollowingCount($user['id']);
                $user['username_md5'] = base64_encode($user['username']);
            }
            return $user;
        } else {
            return [];
        }
    }

    public function UserExists($user, Type $type = Type::Username) : bool {
        $type = match ($type) {
            Type::ID => "id",
            Type::Username => "handle",
            Type::Nickname => "nickname",
        };

        if($type == "handle") {
            $stmt = $this->Connection->prepare("SELECT username FROM users WHERE username = :username");
            $stmt->bindParam(":username", $user);
            $stmt->execute();
        } elseif($type == "id") {
            $stmt = $this->Connection->prepare("SELECT username FROM users WHERE id = :username");
            $stmt->bindParam(":username", $user);
            $stmt->execute();
        } elseif($type == "nickname") {
            $stmt = $this->Connection->prepare("SELECT username FROM users WHERE nickname = :username");
            $stmt->bindParam(":username", $user);
            $stmt->execute();
        }

        return $stmt->rowCount() === 1;
    }

    public function SignIn() : void {
        $alert = new Alert();

        // password validation
        if (!isset($_POST['password']) || empty(trim($_POST['password']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a password.");
        }

        if (strlen($_POST['password']) < 4) {
            $alert->CreateAlert(Level::Error, "Your password must be longer than 4 character.");
        }

        // username validation
        if (!isset($_POST['username']) || empty(trim($_POST['username']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a handle.");
        }

        if (strlen($_POST['username']) < 4 || strlen($_POST['username']) > 20) {
            $alert->CreateAlert(Level::Error, "Your handle must be longer than 3 characters and not longer than 20.");
        }

        // more username validation
        if (preg_match('/[^a-zA-Z\d]/', $_POST['username'])) {
            $alert->CreateAlert(Level::Error, "Your handle cannot contain special characters.");
        }

        $_POST['username'] = strtolower($_POST['username']);

        if($this->UserExists($_POST['username'])) {
            $user = $this->GetUser($_POST['username']);
            // Get user & verify password
            if(!@password_verify($_POST['password'], $user['password'])) {
                $alert->CreateAlert(Level::Error, "You have entered the wrong password.");
            }

            $_SESSION['Handle'] = $_POST['username'];
            $alert->CreateAlert(Level::Success, "Successfully logged in.", false);
            header("Location: /");
        } else {
            $alert->CreateAlert(Level::Error, "This user does not exist.");
        }
    }

    public function CreateUser(string $user, string $password_hash, string $email): bool {
        $stmt = $this->Connection->prepare("INSERT INTO users (username, password, nickname, email) VALUES (:username, :password, :handle, :email)");
        $password_hash = password_hash($password_hash, PASSWORD_DEFAULT);

        $stmt->bindParam(":username", $user);
        $stmt->bindParam(":handle", $user);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":email", $email);

        $stmt->execute();

        // TODO: Catch on error
        return true;
    }

    public function Register(): void {
        $alert = new Alert();

        // Pretty ugly -- TODO: use match() ? New PHP8 feature...
        // TODO: Use new Validator class... :)

        // password validation
        if (!isset($_POST['password']) || empty(trim($_POST['password']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a password.");
        }

        if (strlen($_POST['password']) < 4) {
            $alert->CreateAlert(Level::Error, "Your password must be longer than 4 character.");
        }

        // username validation
        if (!isset($_POST['username']) || empty(trim($_POST['username']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a handle.");
        }

        if (strlen($_POST['username']) < 4 || strlen($_POST['username']) > 20) {
            $alert->CreateAlert(Level::Error, "Your handle must be longer than 3 characters and not longer than 20.");
        }

        // more username validation
        if (preg_match('/[^a-zA-Z\d]/', $_POST['username'])) {
            $alert->CreateAlert(Level::Error, "Your handle cannot contain special characters.");
        }

        // email validation
        if (!isset($_POST['email']) || empty(trim($_POST['email']))) {
            $alert->CreateAlert(Level::Error, "You did not enter an e-mail address.");
        }

        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $alert->CreateAlert(Level::Error, "Your e-mail address is not valid.");
        }

        $_POST['username'] = strtolower($_POST['username']);

        // username exists?
        if($this->UserExists($_POST['username'])) {
            $alert->CreateAlert(Level::Error, "There is already a user with the same username. Please choose another.");
        }

        if($this->CreateUser($_POST['username'], $_POST['password'], $_POST['email'])) {
            $alert->CreateAlert(Level::Success, "Successfully created a Witter account! <a href='/'>You may log in.</a>");
        } else {
            $alert->CreateAlert(Level::Error, "There was an unexpected error while we were creating your account! <a href='/'>Please try again.</a>");
        }
    }
}