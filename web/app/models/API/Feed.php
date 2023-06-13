<?php
namespace Witter\Models;

class Feed extends Model
{
    public function PostLiked(int $id, string|int $user) : bool {
        $userModel = new \Witter\Models\User();

        if(is_int($user)) $userData = $userModel->GetUser($user, Type::ID);
        if(!is_int($user)) $userData = $userModel->GetUser($user, Type::Username);
        if(!isset($userData['id'])) return true; // THIS should not happen. Do not proceed at all
        
        $LikeSearch = $this->Connection->prepare("SELECT * FROM likes WHERE target = :target AND user = :user LIMIT 1");
        $LikeSearch->bindParam(":target", $id);
        $LikeSearch->bindParam(":user", $userData['id']);
        $LikeSearch->execute();
        $Like = $LikeSearch->fetch();

        if (isset($Like['id'])) {
            return true;
        } else {
            return false;
        }
    }

    public function GetFollowingFeed(string|int $user) : array {
        $userModel = new \Witter\Models\User();
    
        // Get user data
        if(is_int($user)) $userData = $userModel->GetUser($user, Type::ID);
        if(!is_int($user)) $userData = $userModel->GetUser($user, Type::Username);
        if(!isset($userData['id'])) return []; // If user not found, return empty array
    
        // Prepare the query
        $query = $this->Connection->prepare(
            "SELECT f.id, f.feed_owner, f.feed_text, f.feed_created 
            FROM followers AS flw
            INNER JOIN feed AS f ON flw.target = f.feed_owner
            WHERE flw.user = :user
            ORDER BY f.feed_created DESC" 
        );
    
        // Bind parameters
        $query->bindParam(":user", $userData['id']);
    
        // Execute the query
        $query->execute();
    
        // Fetch the results
        $feed = $query->fetchAll(\PDO::FETCH_ASSOC);
    
        // Check if feed is empty
        if(empty($feed)) {
            return []; // No posts from followed users found, return empty array
        }
    
        foreach($feed as &$post) {
            // Get post owner data
            if($userModel->UserExists($post['feed_owner'], Type::ID)) {
                $postOwner = $userModel->GetUser($post['feed_owner'], Type::ID);
            }
            
            // Get likes count for the post
            $likesQuery = $this->Connection->prepare("SELECT * FROM likes WHERE target = :target");
            $likesQuery->bindParam(":target", $post['id']);
            $likesQuery->execute();
            
            // Check if current user liked the post
            if(isset($_SESSION['Handle'])) {
                $post["liked"] = $this->PostLiked($post['id'], $_SESSION['Handle']);
            } else {
                $post["liked"] = false;
            }
    
            // Assign likes count and post owner data to the post
            $post["likes"] = $likesQuery->rowCount();
            $post["user"] = @$postOwner;
        }
    
        return $feed;
    }
       
    public function LikePost(string $id) { 
        $id = (int)$id; // cast $id to integer

        $userModel = new \Witter\Models\User();
        $user = $userModel->GetUser($_SESSION['Handle'], Type::Username);

        // Get the JSON payload from the POST request
        $json = file_get_contents('php://input');

        // Decode the JSON into a PHP object
        $data = json_decode($json);
        $comment_id = $data->weet_id;

        if($this->PostLiked($comment_id, $_SESSION['Handle'])) {
            $stmt = $this->Connection->prepare("DELETE FROM likes WHERE target = ? AND user = ?");
            $stmt->execute(
                [
                    $id,
                    $user['id'],
                ]
            );

            $response = array('status' => 'success', 'action' => 'unliked');
        } else {
            $stmt = $this->Connection->prepare(
                "INSERT INTO likes
                    (target, user) 
                VALUES 
                    (?, ?)"
            );
            $stmt->execute([
                $id,
                $user['id'],
            ]);

            $response = array('status' => 'success', 'action' => 'liked');
        }

        echo json_encode($response);
    }

    // returns pdo loopabble thingy, can use while
    public function GetFeed(string $type = "following", int $limit = 20) : array {
        if($type == "everyone") {
            $Feed = $this->Connection->prepare("SELECT * FROM feed ORDER BY id DESC LIMIT " . $limit);
            $Feed->execute();

            // Relation: get user info while fetching forum
            $user_fetch = new \Witter\Models\User();
            while ($weet = $Feed->fetch(\PDO::FETCH_ASSOC)) {
                if ($user_fetch->UserExists($weet['feed_owner'], Type::ID)) {
                    $user = $user_fetch->GetUser($weet['feed_owner'], Type::ID);
                }

                // Relation: For getting # of likes on a specific weet
                $LikesSearch = $this->Connection->prepare("SELECT * FROM likes WHERE target = :target");
                $LikesSearch->bindParam(":target", $weet['id']);
                $LikesSearch->execute();

                // Relation: Did you like this post?
                if(isset($_SESSION['Handle'])) {
                    $weet["liked"] = $this->PostLiked($weet['id'], $_SESSION['Handle']);
                } else {
                    $weet["liked"] = false;
                }
    
                // assign user (accessible by weet.user.property in twig)
                // assign likes property (weet.likes)
                $weet["likes"] = $LikesSearch->rowCount();
                $weet["user"] = @$user;
                $weets[] = $weet;
            }
        } elseif($type == "following") {
            return [];
        } else {
            $user_fetch = new \Witter\Models\User();

            // Just get the user here, no need to requery the 
            // database to get the user again when the same 
            // person will always appear.

            $user = $user_fetch->GetUser($type, Type::Username);

            $Feed = $this->Connection->prepare("SELECT * FROM feed WHERE feed_owner = :id ORDER BY id DESC LIMIT " . $limit);
            $Feed->bindParam(":id", $user['id']);
            $Feed->execute();

            // Relation: get user info while fetching forum
            while ($weet = $Feed->fetch(\PDO::FETCH_ASSOC)) {
                // Relation: For getting # of likes on a specific weet
                $LikesSearch = $this->Connection->prepare("SELECT * FROM likes WHERE target = :target");
                $LikesSearch->bindParam(":target", $weet['id']);
                $LikesSearch->execute();

                // Relation: Did you like this post?
                if(isset($_SESSION['Handle'])) {
                    $weet["liked"] = $this->PostLiked($weet['id'], $_SESSION['Handle']);
                } else {
                    $weet["liked"] = false;
                }
    
                // assign user (accessible by weet.user.property in twig)
                // assign likes property (weet.likes)
                $weet["likes"] = $LikesSearch->rowCount();
                $weet["user"] = @$user;
                $weets[] = $weet;
            }
        }

        return @$weets;
    }

    public function GetLikedPostsByUser(string|int $user, int $limit = 20) {
        $userModel = new \Witter\Models\User();
    
        if(is_int($user)) $userData = $userModel->GetUser($user, Type::ID);
        if(!is_int($user)) $userData = $userModel->GetUser($user, Type::Username);
        if(!isset($userData['id'])) return []; // No such user
        
        $query = $this->Connection->prepare("SELECT * FROM likes WHERE user = :user ORDER BY id DESC LIMIT " . $limit);
        $query->bindParam(":user", $userData['id']);
        $query->execute();
    
        $user_fetch = new \Witter\Models\User();
    
        $weets = [];
        while ($like = $query->fetch(\PDO::FETCH_ASSOC)) {
            // Get the feed info for each liked weet
            $Feed = $this->Connection->prepare("SELECT * FROM feed WHERE id = :id");
            $Feed->bindParam(":id", $like['target']);
            $Feed->execute();
    
            while ($weet = $Feed->fetch(\PDO::FETCH_ASSOC)) {
                // Get user info
                if ($user_fetch->UserExists($weet['feed_owner'], Type::ID)) {
                    $user = $user_fetch->GetUser($weet['feed_owner'], Type::ID);
                }
    
                // Get likes info for this weet
                $LikesSearch = $this->Connection->prepare("SELECT * FROM likes WHERE target = :target");
                $LikesSearch->bindParam(":target", $weet['id']);
                $LikesSearch->execute();
    
                // Check if the specified user liked this post
                $weet["liked"] = $this->PostLiked($weet['id'], $userData['id']);
                
                // Assign user (accessible by weet.user.property in twig)
                // Assign likes property (weet.likes)
                $weet["likes"] = $LikesSearch->rowCount();
                $weet["user"] = @$user;
                $weets[] = $weet;
            }
        }
    
        return $weets;
    }    

    public function NewPost() {
        $alert    = new Alert();
        $user     = new \Witter\Models\User();
        $cooldown = new \Witter\Models\Cooldown();

        // comment validation
        if (!isset($_POST['comment']) && !empty(trim($_POST['comment']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a post.");
        }

        if (strlen($_POST['comment']) < 4 || strlen($_POST['comment']) > 200) {
            $alert->CreateAlert(Level::Error, "Your post must be longer than 3 characters and not longer than 20.");
        }

        if(!$cooldown->GetCooldown("weet_cooldown", $_SESSION['Handle'], 10)) {
            $alert->CreateAlert(Level::Error, "Please wait 10 seconds before posting a Weet.");
        } else {
            $cooldown->SetCooldown("weet_cooldown", $_SESSION['Handle']);
        }

        $user = $user->GetUser($_SESSION['Handle']);

        $stmt = $this->Connection->prepare("INSERT INTO feed (feed_owner, feed_text) VALUES (:id, :comment)");

        $stmt->bindParam(":id", $user['id']);
        $stmt->bindParam(":comment", $_POST['comment']);

        $stmt->execute();

        $alert->CreateAlert(Level::Success, "Successfully weeted!");
    }
}