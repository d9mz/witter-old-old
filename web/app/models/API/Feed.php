<?php
namespace Witter\Models;

class Feed extends Model
{
    // returns pdo loopabble thingy, can use while
    public function GetFeed(string $type = "following", int $limit = 20) {
        if($type == "everyone") {
            $Feed = $this->Connection->prepare("SELECT * FROM feed ORDER BY id DESC LIMIT " . $limit);
            $Feed->execute();

            // Relation: get user info while fetching forum
            $user_fetch = new \Witter\Models\User();
            while ($weet = $Feed->fetch(\PDO::FETCH_ASSOC)) {
                if ($user_fetch->UserExists($weet['feed_owner'], Type::ID)) {
                    $user = $user_fetch->GetUser($weet['feed_owner'], Type::ID);
                }

                $weet["user"] = @$user;
                $weets[] = $weet;
            }
        } elseif($type == "following") {
            return "";
        } else {
            $user_fetch = new \Witter\Models\User();
            $user = $user_fetch->GetUser($type, Type::Username);

            $Feed = $this->Connection->prepare("SELECT * FROM feed WHERE feed_owner = :id ORDER BY id DESC LIMIT " . $limit);
            $Feed->bindParam(":id", $user['id']);
            $Feed->execute();

            // Relation: get user info while fetching forum
            while ($weet = $Feed->fetch(\PDO::FETCH_ASSOC)) {
                $weet["user"] = @$user;
                $weets[] = $weet;
            }
        }

        return @$weets;
    }

    public function NewPost() {
        $alert = new Alert();
        $user  = new \Witter\Models\User();

        // comment validation
        if (!isset($_POST['comment']) && !empty(trim($_POST['comment']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a post.");
        }

        if (strlen($_POST['comment']) < 4 || strlen($_POST['comment']) > 200) {
            $alert->CreateAlert(Level::Error, "Your post must be longer than 3 characters and not longer than 20.");
        }

        $user = $user->GetUser($_SESSION['Handle']);

        $stmt = $this->Connection->prepare("INSERT INTO feed (feed_owner, feed_text) VALUES (:id, :comment)");

        $stmt->bindParam(":id", $user['id']);
        $stmt->bindParam(":comment", $_POST['comment']);

        $stmt->execute();

        $alert->CreateAlert(Level::Success, "Successfully weeted!");
    }
}