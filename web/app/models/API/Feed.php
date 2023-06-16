<?php
namespace Witter\Models;

class Feed extends Model
{
    public function mapWeetToReply(array $item, bool $isWeetToReply) : array {
        $mappedItem = array();
    
        if ($isWeetToReply) {
            // Mapping Weet to Reply
            $mappedItem['reply_text'] = $item['feed_text'];       
            $mappedItem['reply_author'] = $item['feed_owner'];    
            $mappedItem['reply_created'] = $item['feed_created']; 
            $mappedItem['reply_target'] = $item['id'];            
            // $mappedItem['replying_to_reply'] = 'f';   
        } else {
            // Mapping Reply to Weet
            $mappedItem['feed_text'] = $item['reply_text'];       
            $mappedItem['feed_owner'] = $item['reply_author'];    
            $mappedItem['feed_created'] = $item['reply_created']; 
            $mappedItem['id'] = $item['reply_target'];
        }
    
        return $mappedItem;
    }
    
    public function getWeetThread(int $weet_id) : array {
        $weet = $this->GetWeet($weet_id);
        if (!empty($weet)) {
            // Get all replies to the weet
            $replies = $this->GetReplies($weet_id);
            // Loop through each reply and get replies to it (i.e., reply to reply)
            if (!empty($replies)) {
                foreach ($replies as $key => $reply) {
                    $replies_to_reply = $this->GetReplies($reply['id'], 20, true);
                    $replies[$key]['replies'] = $replies_to_reply;
                }
            }
            // Add all replies (including replies to replies) to the weet
            $weet['replies'] = $replies;
        }
        return $weet;
    }
    
    
    public function PostLiked(int $id, string|int $user, bool $reply = false) : bool {
        $userModel = new \Witter\Models\User();

        if(is_int($user)) $userData = $userModel->GetUser($user, Type::ID);
        if(!is_int($user)) $userData = $userModel->GetUser($user, Type::Username);
        if(!isset($userData['id'])) return true; // THIS should not happen. Do not proceed at all
        if($reply)  $query = "SELECT * FROM likes WHERE target = :target AND user = :user AND reply = 't' LIMIT 1";
        if(!$reply) $query = "SELECT * FROM likes WHERE target = :target AND user = :user AND reply = 'f' LIMIT 1";

        $LikeSearch = $this->Connection->prepare($query);
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

    public function GetTrendingFeed() : array {
        $userModel = new \Witter\Models\User();
    
        // Prepare the query
        $query = $this->Connection->prepare(
            "SELECT f.id, f.feed_owner, f.feed_text, f.feed_created, COUNT(l.id) as likes_count 
            FROM feed AS f
            INNER JOIN likes AS l ON f.id = l.target
            WHERE l.created_at >= NOW() - INTERVAL 1 HOUR 
            GROUP BY f.id
            ORDER BY likes_count DESC"
        );
    
        // Execute the query
        $query->execute();
    
        // Fetch the results
        $feed = $query->fetchAll(\PDO::FETCH_ASSOC);
    
        // Check if feed is empty
        if(empty($feed)) {
            return []; // No posts found, return empty array
        }
    
        foreach($feed as &$post) {
            // Get post owner data
            if($userModel->UserExists($post['feed_owner'], Type::ID)) {
                $postOwner = $userModel->GetUser($post['feed_owner'], Type::ID);
            }
    
            // Check if current user liked the post
            if(isset($_SESSION['Handle'])) {
                $post["liked"] = $this->PostLiked($post['id'], $_SESSION['Handle']);
            } else {
                $post["liked"] = false;
            }
    
            // Assign post owner data and likes count to the post
            $post["replies"] = $this->GetReplyCount($post['id']);
            $post["user"] = @$postOwner;
            $post["likes"] = $this->GetLikeCount($post['id']);
        }
    
        // Reverse the order of the feed to have most liked posts at the top
        $feed = array_reverse($feed);
    
        return $feed;
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
       
    // ugly to have two functions for this
    public function LikeReply(string $id) { 
        $id = (int)$id; // cast $id to integer

        $userModel = new \Witter\Models\User();
        $user = $userModel->GetUser($_SESSION['Handle'], Type::Username);

        // Get the JSON payload from the POST request
        $json = file_get_contents('php://input');

        // Decode the JSON into a PHP object
        $data = json_decode($json);
        $comment_id = $data->weet_id;

        if($this->PostLiked($comment_id, $_SESSION['Handle'], true)) {
            $stmt = $this->Connection->prepare("DELETE FROM likes WHERE target = ? AND user = ? AND reply = 't'");
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
                    (target, user, reply) 
                VALUES 
                    (?, ?, 't')"
            );
            $stmt->execute([
                $id,
                $user['id'],
            ]);

            $response = array('status' => 'success', 'action' => 'liked');
        }

        echo json_encode($response);
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
            $stmt = $this->Connection->prepare("DELETE FROM likes WHERE target = ? AND user = ? AND reply = 'f'");
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

    public function WeetExists(int $weet_id, bool $reply = false) : bool {
        if(!$reply) {
            $stmt = $this->Connection->prepare("SELECT id FROM feed WHERE id = :id");
            $stmt->bindParam(":id", $weet_id);
            $stmt->execute();
        } else {
            $stmt = $this->Connection->prepare("SELECT id FROM reply WHERE id = :id");
            $stmt->bindParam(":id", $weet_id);
            $stmt->execute();
        }

        return $stmt->rowCount() === 1;
    }
    
    public function GetWeet(int $weet_id) : array {
        $query = "SELECT * FROM feed WHERE id = :find";
        $stmt = $this->Connection->prepare($query);
        $stmt->bindParam(":find", $weet_id);
        $stmt->execute();

        $weet = $stmt->rowCount() === 0 ? 0 : $stmt->fetch(\PDO::FETCH_ASSOC);

        if(isset($weet['id'])) {
            $user_fetch = new \Witter\Models\User();
            $user = $user_fetch->GetUser($weet['feed_owner'], Type::ID);

            // Relation: For getting # of likes on a specific weet
            $LikesSearch = $this->Connection->prepare("SELECT * FROM likes WHERE target = :target AND reply = 'f'");
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
            $weet["replies"] = $this->GetReplyCount($weet['id']);
            $weet["likes"] = $LikesSearch->rowCount();
            $weet["user"] = @$user;

            return $weet;
        } else {
            return [];
        }
    }

    public function GetReply(int $weet_id) : array {
        $query = "SELECT * FROM reply WHERE id = :find";
        $stmt = $this->Connection->prepare($query);
        $stmt->bindParam(":find", $weet_id);
        $stmt->execute();

        $weet = $stmt->rowCount() === 0 ? 0 : $stmt->fetch(\PDO::FETCH_ASSOC);

        if(isset($weet['id'])) {
            $user_fetch = new \Witter\Models\User();
            $user = $user_fetch->GetUser($weet['reply_author'], Type::ID);

            // Relation: For getting # of likes on a specific weet
            // TODO: Why the hell do I not put this in a function?
            $LikesSearch = $this->Connection->prepare("SELECT * FROM likes WHERE target = :target AND reply = 't'");
            $LikesSearch->bindParam(":target", $weet['id']);
            $LikesSearch->execute();

            // Relation: Did you like this post?
            if(isset($_SESSION['Handle'])) {
                $weet["liked"] = $this->PostLiked($weet['id'], $_SESSION['Handle'], true);
            } else {
                $weet["liked"] = false;
            }

            // assign user (accessible by weet.user.property in twig)
            // assign likes property (weet.likes)
            $weet["replies"] = $this->GetReplyCount($weet['id'], true);
            $weet["likes"] = $this->GetLikeCount($weet['id'], true);
            $weet["user"] = @$user;

            return $weet;
        } else {
            return [];
        }
    }


    public function GetReplies(int $weet_id, int $limit = 20, bool $reply_to_reply = false) {
        echo $weet_id . ";";
        echo (int)$reply_to_reply;
        if(!$reply_to_reply) $query = "SELECT * FROM reply WHERE reply_target = :id AND replying_to_reply = 'f' ORDER BY id DESC LIMIT " . $limit;
        if($reply_to_reply) $query = "SELECT * FROM reply WHERE reply_target = :id AND replying_to_reply = 't' ORDER BY id DESC LIMIT " . $limit;
        echo "fart";

        $Feed = $this->Connection->prepare($query);
        $Feed->bindParam(":id", $weet_id);
        $Feed->execute();
        echo "fart";

        // Relation: get user info while fetching forum
        $user_fetch = new \Witter\Models\User();
        while ($weet = $Feed->fetch(\PDO::FETCH_ASSOC)) {
            echo "fart";
            if ($user_fetch->UserExists($weet['reply_author'], Type::ID)) {
                $user = $user_fetch->GetUser($weet['reply_author'], Type::ID);
            }

            // Relation: For getting # of likes on a specific weet
            $LikesSearch = $this->Connection->prepare("SELECT * FROM likes WHERE target = :target AND reply = 't'");
            $LikesSearch->bindParam(":target", $weet['id']);
            $LikesSearch->execute();

            // Relation: Did you like this post?
            if(isset($_SESSION['Handle'])) {
                $weet["liked"] = $this->PostLiked($weet['id'], $_SESSION['Handle'], true);
            } else {
                $weet["liked"] = false;
            }

            // assign user (accessible by weet.user.property in twig)
            // assign likes property (weet.likes)

            echo $weet['id'];
            $weet["replies"] = $this->GetReplyCount($weet['id'], true);
            echo $weet["replies"];
            $weet["likes"] = $LikesSearch->rowCount();
            $weet["user"] = @$user;
            $weets[] = $weet;
        }

        return @$weets;
    }

    public function GetReplyCount(int $weet_id, bool $reply = false) : int {
        if(!$reply) {
            $Feed = $this->Connection->prepare("SELECT * FROM reply WHERE reply_target = :id AND replying_to_reply = 'f'");
            $Feed->bindParam(":id", $weet_id);
            $Feed->execute();
        } else {
            $Feed = $this->Connection->prepare("SELECT * FROM reply WHERE reply_target = :id AND replying_to_reply = 't'");
            $Feed->bindParam(":id", $weet_id);
            $Feed->execute();
        }

        return $Feed->rowCount();
    }

    public function GetLikeCount(int $weet_id, bool $reply = false) : int {
        if(!$reply) {
            $Feed = $this->Connection->prepare("SELECT * FROM likes WHERE target = :id AND reply = 'f'");
            $Feed->bindParam(":id", $weet_id);
            $Feed->execute();
        } else {
            $Feed = $this->Connection->prepare("SELECT * FROM likes WHERE target = :id AND reply = 't'");
            $Feed->bindParam(":id", $weet_id);
            $Feed->execute();
        }

        return $Feed->rowCount();
    }

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

                // Relation: Did you like this post?
                if(isset($_SESSION['Handle'])) {
                    $weet["liked"] = $this->PostLiked($weet['id'], $_SESSION['Handle']);
                } else {
                    $weet["liked"] = false;
                }
    
                // assign user (accessible by weet.user.property in twig)
                // assign likes property (weet.likes)
                $weet["replies"] = $this->GetReplyCount($weet['id']);
                $weet["likes"] = $this->GetLikeCount($weet['id']);
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
                // Relation: Did you like this post?
                if(isset($_SESSION['Handle'])) {
                    $weet["liked"] = $this->PostLiked($weet['id'], $_SESSION['Handle']);
                } else {
                    $weet["liked"] = false;
                }
    
                // assign user (accessible by weet.user.property in twig)
                // assign likes property (weet.likes)
                $weet["replies"] = $this->GetReplyCount($weet['id']);
                $weet["likes"] = $this->GetLikeCount($weet['id']);
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
                $weet["replies"] = $this->GetReplyCount($weet['id']);
                $weet["likes"] = $LikesSearch->rowCount();
                $weet["user"] = @$user;
                $weets[] = $weet;
            }
        }
    
        return $weets;
    }    

    public function ReplyToReply($weet_id) {
        $alert    = new Alert();
        $user     = new \Witter\Models\User();
        $cooldown = new \Witter\Models\Cooldown();
        $weet     = new \Witter\Models\Feed();
        $alert    = new \Witter\Models\Alert();

        if(!filter_var($weet_id, FILTER_VALIDATE_INT)) $alert->CreateAlert(Level::Error, "Invalid Weet ID");
        if(!$weet->WeetExists((int)$weet_id)) $alert->CreateAlert(Level::Error, "This weet does not exist.");

        // comment validation
        if (!isset($_POST['comment']) && !empty(trim($_POST['comment']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a reply.");
        }

        if (strlen($_POST['comment']) < 4 || strlen($_POST['comment']) > 200) {
            $alert->CreateAlert(Level::Error, "Your reply must be longer than 3 characters and not longer than 20.");
        }

        if(!$cooldown->GetCooldown("weet_cooldown", $_SESSION['Handle'], 10)) {
            $alert->CreateAlert(Level::Error, "Please wait 10 seconds before replying to a Weet.");
        } else {
            $cooldown->SetCooldown("weet_cooldown", $_SESSION['Handle']);
        }

        $user = $user->GetUser($_SESSION['Handle']);

        $stmt = $this->Connection->prepare("INSERT INTO reply (reply_author, reply_text, reply_target, replying_to_reply) VALUES (:id, :comment, :target, 't')");

        $stmt->bindParam(":id", $user['id']);
        $stmt->bindParam(":comment", $_POST['comment']);
        $stmt->bindParam(":target", $weet_id);

        $stmt->execute();

        $alert->CreateAlert(Level::Success, "Successfully replied!");
    }
    public function Reply($weet_id) {
        $alert    = new Alert();
        $user     = new \Witter\Models\User();
        $cooldown = new \Witter\Models\Cooldown();
        $weet     = new \Witter\Models\Feed();
        $alert    = new \Witter\Models\Alert();

        if(!filter_var($weet_id, FILTER_VALIDATE_INT)) $alert->CreateAlert(Level::Error, "Invalid Weet ID");
        if(!$weet->WeetExists((int)$weet_id)) $alert->CreateAlert(Level::Error, "This weet does not exist.");

        // comment validation
        if (!isset($_POST['comment']) && !empty(trim($_POST['comment']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a reply.");
        }

        if (strlen($_POST['comment']) < 4 || strlen($_POST['comment']) > 200) {
            $alert->CreateAlert(Level::Error, "Your reply must be longer than 3 characters and not longer than 20.");
        }

        if(!$cooldown->GetCooldown("weet_cooldown", $_SESSION['Handle'], 10)) {
            $alert->CreateAlert(Level::Error, "Please wait 10 seconds before replying to a Weet.");
        } else {
            $cooldown->SetCooldown("weet_cooldown", $_SESSION['Handle']);
        }

        $user = $user->GetUser($_SESSION['Handle']);

        $stmt = $this->Connection->prepare("INSERT INTO reply (reply_author, reply_text, reply_target) VALUES (:id, :comment, :target)");

        $stmt->bindParam(":id", $user['id']);
        $stmt->bindParam(":comment", $_POST['comment']);
        $stmt->bindParam(":target", $weet_id);

        $stmt->execute();

        $alert->CreateAlert(Level::Success, "Successfully replied!");
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