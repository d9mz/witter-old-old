<?php
namespace Witter\Models;

enum Type: int {
    case ID = 0;
    case Username = 1;
    case Nickname = 2;
}

class User extends Model
{
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
    
    public function FollowingUser(string|int $target, string|int $user) : bool {
        $userModel = new \Witter\Models\User();

        // TODO: UGLY!! Ugly as shit
        if(is_int($target)) $userTarget = $userModel->GetUser($target, Type::ID);
        if(!is_int($target)) $userTarget = $userModel->GetUser($target, Type::Username);
        if(!isset($userTarget['id'])) return true; // THIS should not happen. Do not proceed at all
        
        if(is_int($user)) $userData = $userModel->GetUser($user, Type::ID);
        if(!is_int($user)) $userData = $userModel->GetUser($user, Type::Username);
        if(!isset($userData['id'])) return true; // THIS should not happen. Do not proceed at all

        if($userData['id'] == $userTarget['id']) return true; // No following yourself...

        $query = $this->Connection->prepare("SELECT * FROM followers WHERE user = :user AND target = :target");
        $query->bindParam(":target", $userTarget['id']);
        $query->bindParam(":user", $userData['id']);
        $query->execute();
        $follow = $query->fetch();

        if (isset($follow['id'])) {
            return true;
        } else {
            return false;
        }
    }

    public function Follow(string $uid) {
        $userModel = new \Witter\Models\User();
        $user = $userModel->GetUser($_SESSION['Handle'], Type::Username);

        // Get the JSON payload from the POST request
        $json = file_get_contents('php://input');

        // Decode the JSON into a PHP object
        $data = json_decode($json);

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

            $response = array('status' => 'success', 'action' => 'unfollow');
        }

        echo json_encode($response);
    }

    // vvv Type type = Type ??? Looks weird but whatever
    public function GetUser($user, Type $type = Type::Username) {
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

            // is the logged in user following this user?
            // can't do this here because it causes a memory leak ????

            // get follower & follow count & make it properties of $user
            $user['metrics'] = $this->GetFollowerFollowingCount($user['id']);
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
        if (!isset($_POST['password']) && !empty(trim($_POST['password']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a password.");
        }

        if (strlen($_POST['password']) < 4) {
            $alert->CreateAlert(Level::Error, "Your password must be longer than 4 character.");
        }

        // username validation
        if (!isset($_POST['username']) && !empty(trim($_POST['username']))) {
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
        if (!isset($_POST['password']) && !empty(trim($_POST['password']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a password.");
        }

        if (strlen($_POST['password']) < 4) {
            $alert->CreateAlert(Level::Error, "Your password must be longer than 4 character.");
        }

        // username validation
        if (!isset($_POST['username']) && !empty(trim($_POST['username']))) {
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
        if (!isset($_POST['email']) && !empty(trim($_POST['email']))) {
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