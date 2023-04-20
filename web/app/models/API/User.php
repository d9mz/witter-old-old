<?php
namespace Witter\Models;

class User extends ModelBase
{
    public function GetUser($user, $handle = true) {
        if($handle) {
            $stmt = $this->Connection->prepare("SELECT * FROM users WHERE username = :find");
            $stmt->bindParam(":find", $user);
            $stmt->execute();
        } else {
            $stmt = $this->Connection->prepare("SELECT * FROM users WHERE nickname = :find");
            $stmt->bindParam(":find", $user);
            $stmt->execute();
        }

        return ($stmt->rowCount() === 0 ? 0 : $stmt->fetch(\PDO::FETCH_ASSOC));
    }

    public function UserExists($user) : bool {
        $stmt = $this->Connection->prepare("SELECT username FROM users WHERE username = :username");
        $stmt->bindParam(":username", $user);
        $stmt->execute();

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

    public function CreateUser($user, $password_hash, $email): bool {
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