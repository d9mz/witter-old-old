<?php
namespace Witter\Models;

use \Godruoyi\Snowflake\Snowflake;


// TODO: REFACTOR ALL OF THIS
// SPLIT INTO DIFFERENT FEED classes
// But still under a \Witter\Models\Feed\* namespace;

class Feed extends Model
{
    // create database instance -- this shouldn't really be done here but it's
    // a temporary solution to the infinite connection issue
    public function __construct() {
        $connection = new \Witter\Models\Connection();
        $this->Connection = $connection->MakeConnection();
    }
    
    public function RemoveWitterLinkInWeet(string $weet, string $link) : string {
        $pos = strpos($weet, $link);
        if ($pos !== false) {
            return substr_replace($weet, "", $pos, strlen($link));
        }

        // Edge case, should never happen regardless
        return "";
    }

    public function GetWitterLinksInWeet(string $weet) : array {
        $pattern = '/https?:\/\/genericlink\.com\/user\/([a-zA-Z0-9-]+)\/(\d+)/';
        preg_match($pattern, $weet, $matches);

        // This looks really weird
        if(isset($matches[1])) return [$matches[1], $matches[2], $matches[0]];
        else                   return [];
    }

    public function GenerateID() : string {
        // Intellisense doesn't like me doing this
        $snowflake = new Snowflake;
        return $snowflake->id();;
    }

    public function getWeetThread(int $weet_id) : array {
        $weet = $this->GetWeet($weet_id, true);
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
        
        $query = "SELECT * FROM likes WHERE target = :target AND user = :user LIMIT 1";

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

    public function GetTrendingFeed() : mixed {
        $userModel = new \Witter\Models\User();
    
        // Prepare the query
        $query = $this->Connection->prepare(
            "SELECT f.feed_id, COUNT(l.target) AS likes
            FROM feed AS f
            LEFT JOIN likes AS l ON f.feed_id = l.target
            WHERE f.feed_created > DATE_SUB(NOW(), INTERVAL 1 DAY)
            GROUP BY f.feed_id
            ORDER BY likes"
        );
    
        // Execute the query
        $query->execute();
    
        // Fetch the results
        $feed = $query->fetchAll(\PDO::FETCH_ASSOC);
    
        // Check if feed is empty
        if(empty($feed)) {
            return [[]]; // No posts found, return empty array
        }
    
        foreach($feed as &$post) {
            $post = $this->GetWeet($post['feed_id'], false);
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
            "SELECT f.feed_id 
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
            $post = $this->GetWeet($post['feed_id'], false);
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

        $weet = $this->GetWeet($comment_id, false);

        if($weet['user']['visible'] == true) {
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
        } else {
            $response = array('status' => 'fail', 'action' => 'urgay');
        }

        echo json_encode($response);
    }

    public function DeletePost(string $id) { 
        $id = (int)$id; // cast $id to integer

        $userModel = new \Witter\Models\User();
        $feedModel = new \Witter\Models\Feed();
        $user = $userModel->GetUser($_SESSION['Handle'], Type::Username);

        // Get the JSON payload from the POST request
        $json = file_get_contents('php://input');

        // Decode the JSON into a PHP object
        $data = json_decode($json);
        $comment_id = $data->weet_id;

        $weet = $this->GetWeet($comment_id, false);

        if($weet['user']['username'] == $_SESSION['Handle']) {
            // if reweet, take away one from original reweet count
            if(!empty(trim($weet['feed_embed']))) {
                $reweet = $feedModel->GetWeet($weet['feed_embed'], false, false, true);
                $reweets = $reweet['feed_reweets'] - 1;

                $stmt = $this->Connection->prepare("UPDATE feed SET feed_reweets = ? WHERE feed_id = ?");
                $stmt->execute([
                    $reweets,
                    $reweet['feed_id'],
                ]);
                $stmt = null;
            }

            $stmt = $this->Connection->prepare("DELETE FROM feed WHERE feed_id = ? AND feed_owner = ?");
            $stmt->execute(
                [
                    $weet['feed_id'],
                    $user['id']
                ]
            );

            $response = array('status' => 'success', 'action' => 'deleted');
        } else {
            header('HTTP/1.0 403 Forbidden');
            $response = array('status' => 'fail', 'action' => 'urgay');
        }

        echo json_encode($response);
    }

    public function WeetExists(int $weet_id, bool $actual_id = false) : bool {
        if(!$actual_id) {
            $stmt = $this->Connection->prepare("SELECT id FROM feed WHERE feed_id = :id");
            $stmt->bindParam(":id", $weet_id);
            $stmt->execute();
        } else {
            $stmt = $this->Connection->prepare("SELECT id FROM feed WHERE id = :id");
            $stmt->bindParam(":id", $weet_id);
            $stmt->execute();
        }

        return $stmt->rowCount() === 1;
    }
    
    // Optimize option set to true disables unneeded database queries (such as getting likes, replies, liked, etc.)
    public function GetWeet(int $weet_id, bool $actual_id, bool $remove_witter_links = true, bool $optimize = false) : array {
        $userModel = new User();
        
        if($actual_id) $query = "SELECT * FROM feed WHERE id = :find";
        if(!$actual_id) $query = "SELECT * FROM feed WHERE feed_id = :find";

        $stmt = $this->Connection->prepare($query);
        $stmt->bindParam(":find", $weet_id);
        $stmt->execute();

        $weet = $stmt->rowCount() === 0 ? 0 : $stmt->fetch(\PDO::FETCH_ASSOC);

        if(isset($weet['id'])) {
            $user_fetch = new \Witter\Models\User();
            $user = $user_fetch->GetUser($weet['feed_owner'], Type::ID);

            // Relation: For getting # of likes on a specific weet
            if(!$optimize) {
                $LikesSearch = $this->Connection->prepare("SELECT * FROM likes WHERE target = :target");
                $LikesSearch->bindParam(":target", $weet['feed_id']);
                $LikesSearch->execute();

                // Relation: Did you like this post?
                if(isset($_SESSION['Handle'])) {
                    $weet["liked"] = $this->PostLiked($weet['feed_id'], $_SESSION['Handle']);
                } else {
                    $weet["liked"] = false;
                }

                // assign user (accessible by weet.user.property in twig)
                // assign likes property (weet.likes)
                $weet["replies"] = $this->GetReplyCount($weet['feed_id']);
                $weet["likes"] = $LikesSearch->rowCount();
            }

            $weet["user"] = @$user;

            // Remove Witter links if 
            if(!empty(trim($weet['feed_embed']))) {
                // Get all that reweet metadata
                $retweet = $this->GetWitterLinksInWeet($weet['feed_text']);
                $user_exists = $userModel->UserExists($retweet[0]);
                $weet_exists = $this->GetWeet($retweet[1], false);

                if($user_exists && $weet_exists) {
                    $weet["reweet"] = $this->GetWeet($retweet[1], false, false, true);
                    $weet["feed_text"] = $this->RemoveWitterLinkInWeet($weet["feed_text"], $retweet[2]);
                } else if($user_exists) {
                    // Most likely Weet got deleted

                    $weet["reweet"]["user"]["username"] = "n/a";
                    $weet["reweet"]["user"]["nickname"] = "N/A";
                    $weet["reweet"]["feed_text"] = "This weet is currently unavailable.";
                    $weet["reweet"]["feed_created"] = "2021-09-10 01:23:45";
                    $weet["reweet"]["feed_target"] = -1;
                    $weet["reweet"]["feed_id"] = 0;
                }
            }

            if(!$weet['user']['visible']) {
                $weet["feed_text"] = "The post owner limits who can see their posts.";
            }

            return $weet;
        } else {
            return [];
        }
    }

    public function getReplyTree($weet_id, self $weetModel, $depth = 0) : array {
        $tree = [];

        // The base case: if a weet doesn't have a reply, return an empty array
        if (!$weetModel->WeetExists($weet_id, false)) {
            return $tree;
        }
    
        // Recursive case: get the weet, add it to the tree, and then add all replies to the weet
        $weet = $weetModel->GetWeet($weet_id, false);
        $weet['reply'] = true;
        if(isset($_SESSION['Handle'])) $weet['liked'] = $weetModel->PostLiked($weet_id, $_SESSION['Handle']);
    
        // Check for circular references to avoid infinite loop
        if ($weet['feed_target'] != $weet_id && $weet['feed_target'] != -1 && $depth < 10) {
            $tree = $this->getReplyTree($weet['feed_target'], $weetModel, $depth + 1);
        }
    
        // Add the weet to the tree after adding its replies
        $tree[] = $weet;
    
        return $tree;
    }    

    public function GetReply(int $weet_id) : array {
        $query = "SELECT * FROM feed WHERE id = :find LIMIT 1";
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
            $LikesSearch->bindParam(":target", $weet['feed_id']);
            $LikesSearch->execute();

            // Relation: Did you like this post?
            if(isset($_SESSION['Handle'])) {
                $weet["liked"] = $this->PostLiked($weet['feed_id'], $_SESSION['Handle'], true);
            } else {
                $weet["liked"] = false;
            }

            // assign user (accessible by weet.user.property in twig)
            // assign likes property (weet.likes)
            $weet["replies"] = $this->GetReplyCount($weet['feed_id'], true);
            $weet["likes"] = $this->GetLikeCount($weet['feed_id'], true);
            $weet["user"] = @$user;

            return $weet;
        } else {
            return [];
        }
    }


    public function GetReplies(int $weet_id, int $limit = 20, bool $reply_to_reply = false) {
        $query = "SELECT * FROM feed WHERE feed_target = :id ORDER BY id DESC LIMIT " . $limit;

        $Feed = $this->Connection->prepare($query);
        $Feed->bindParam(":id", $weet_id);
        $Feed->execute();

        // Relation: get user info while fetching forum
        $user_fetch = new \Witter\Models\User();
        while ($weet = $Feed->fetch(\PDO::FETCH_ASSOC)) {
            if ($user_fetch->UserExists($weet['feed_owner'], Type::ID)) {
                $user = $user_fetch->GetUser($weet['feed_owner'], Type::ID);
            }

            // Relation: Did you like this post?
            if(isset($_SESSION['Handle'])) {
                $weet["liked"] = $this->PostLiked($weet['feed_id'], $_SESSION['Handle'], true);
            } else {
                $weet["liked"] = false;
            }

            // assign user (accessible by weet.user.property in twig)
            // assign likes property (weet.likes)

            $weet["replies"] = $this->GetReplyCount($weet['feed_id'], true);
            $weet["likes"] = $this->GetLikeCount($weet['feed_id']);
            $weet["user"] = @$user;
            $weets[] = $weet;
        }

        return @$weets;
    }

    public function GetReplyCount(int $weet_id, bool $reply = false) : int {
        $Feed = $this->Connection->prepare("SELECT * FROM feed WHERE feed_target = :id");
        $Feed->bindParam(":id", $weet_id);
        $Feed->execute();

        return $Feed->rowCount();
    }

    public function GetLikeCount(int $weet_id, bool $reply = false) : int {
        $Feed = $this->Connection->prepare("SELECT * FROM likes WHERE target = :id");
        $Feed->bindParam(":id", $weet_id);
        $Feed->execute();

        return $Feed->rowCount();
    }

    public function GetFeedScrolling(int $page, int $weetsToLoad) : array {
        $weetsToSkip = $page * $weetsToLoad;

        $feedModel = new \Witter\Models\Feed();

        $Feed = $this->Connection->prepare("SELECT feed_id FROM feed WHERE feed_target = -1 ORDER BY id DESC LIMIT " . $weetsToLoad . " OFFSET " . $weetsToSkip);
        $Feed->execute();

        // Relation: get user info while fetching forum
        while ($weet = $Feed->fetch(\PDO::FETCH_ASSOC)) {
            $weet = $feedModel->GetWeet($weet['feed_id'], false);
            $weets[] = $weet;
        }
        
        return $weets;
    }

    // returns pdo loopabble thingy, can use while
    public function GetFeed(string $type = "following", int $limit = 20) {
        if($type == "everyone") {
            $Feed = $this->Connection->prepare("SELECT feed_id FROM feed WHERE feed_target = -1 ORDER BY id DESC LIMIT " . $limit);
            $Feed->execute();

            // Relation: get user info while fetching forum
            $user_fetch = new \Witter\Models\User();
            while ($weet = $Feed->fetch(\PDO::FETCH_ASSOC)) {
                $weet = $this->GetWeet($weet['feed_id'], false);
                $weets[] = $weet;
            }
        } elseif($type == "following") {
            return [];
        } else {
            $user_fetch = new \Witter\Models\User();
            $user = $user_fetch->GetUser($type, Type::Username);

            $Feed = $this->Connection->prepare("SELECT feed_id FROM feed WHERE feed_owner = :id AND feed_target = -1 ORDER BY id DESC LIMIT " . $limit);
            $Feed->bindParam(":id", $user['id']);
            $Feed->execute();

            // Relation: get user info while fetching forum
            while ($weet = $Feed->fetch(\PDO::FETCH_ASSOC)) {
                $weet = $this->GetWeet($weet['feed_id'], false);
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
            $Feed = $this->Connection->prepare("SELECT * FROM feed WHERE feed_id = :id");
            $Feed->bindParam(":id", $like['target']);
            $Feed->execute();
    
            while ($weet = $Feed->fetch(\PDO::FETCH_ASSOC)) {
                // Get user info
                if ($user_fetch->UserExists($weet['feed_owner'], Type::ID)) {
                    $user = $user_fetch->GetUser($weet['feed_owner'], Type::ID);
                }
    
                // Get likes info for this weet
                $LikesSearch = $this->Connection->prepare("SELECT * FROM likes WHERE target = :target");
                $LikesSearch->bindParam(":target", $weet['feed_id']);
                $LikesSearch->execute();
    
                // Check if the specified user liked this post
                $weet["liked"] = $this->PostLiked($weet['feed_id'], $userData['id']);
                
                // Assign user (accessible by weet.user.property in twig)
                // Assign likes property (weet.likes)
                $weet["replies"] = $this->GetReplyCount($weet['feed_id']);
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
        if (!isset($_POST['comment']) || empty(trim($_POST['comment']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a reply.");
        }

        if (strlen($_POST['comment']) < 4 || strlen($_POST['comment']) > 200) {
            $alert->CreateAlert(Level::Error, "Your reply must be longer than 3 characters and not longer than 20.");
        }

        /*
        if(!$cooldown->GetCooldown("weet_cooldown", $_SESSION['Handle'], 10)) {
            $alert->CreateAlert(Level::Error, "Please wait 10 seconds before replying to a Weet.");
        } else {
            $cooldown->SetCooldown("weet_cooldown", $_SESSION['Handle']);
        }
        */

        $user = $user->GetUser($_SESSION['Handle']);

        $stmt = $this->Connection->prepare("INSERT INTO feed (feed_id, feed_owner, feed_text, feed_target) VALUES (:snowflake, :id, :comment, :target)");

        $id = $this->GenerateID();
        $stmt->bindParam(":snowflake", $id);
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
        if (!isset($_POST['comment']) || empty(trim($_POST['comment']))) {
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

        $stmt = $this->Connection->prepare("INSERT INTO feed (feed_id, feed_owner, feed_text, feed_target) VALUES (:snowflake, :id, :comment, :target)");

        $id = $this->GenerateID();
        $stmt->bindParam(":snowflake", $id);
        $stmt->bindParam(":id", $user['id']);
        $stmt->bindParam(":comment", $_POST['comment']);
        $stmt->bindParam(":target", $weet_id);

        $stmt->execute();

        $alert->CreateAlert(Level::Success, "Successfully replied!");
    }

    public function NewPost() {
        $alert     = new Alert();
        $userModel = new \Witter\Models\User();
        $weetModel = new \Witter\Models\Feed();
        $cooldown  = new \Witter\Models\Cooldown();

        // comment validation
        if (!isset($_POST['comment']) || empty(trim($_POST['comment']))) {
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

        // hacky shitty fix nbecasue my dumbass forgot to accoutn for this
        $_POST['comment'] = trim($_POST['comment']);

        $user   = $userModel->GetUser($_SESSION['Handle']);
        $id     = $this->GenerateID();
        $reweet = $this->GetWitterLinksInWeet($_POST['comment']);

        // TODO: This is pretty ugly...
        if(count($reweet) == 3) {
            // weet link detected ...
            if($userModel->UserExists($reweet[0]) && $weetModel->WeetExists($reweet[1])) {
                // a bit ugly
                // TODO: i really shouldn't be returning the username for this? what if username change
                $reweetUser = $userModel->GetUser($reweet[0], Type::Username);

                // if the priv user is following the logged in user
                if($reweetUser['visible']) {
                    // add +1 to reweets of target weet
                    $weet = $weetModel->GetWeet($reweet[1], false, false, true);
                    $reweets = $weet['feed_reweets'] + 1;

                    $stmt = $this->Connection->prepare("UPDATE feed SET feed_reweets = ? WHERE feed_id = ?");
                    $stmt->execute([
                        $reweets,
                        $weet['feed_id'],
                    ]);
                    $stmt = null;
                    
                    // insert into feed w/ metadata
                    $stmt = $this->Connection->prepare("INSERT INTO feed (feed_id, feed_owner, feed_text, feed_embed) VALUES (:snowflake, :id, :comment, :embed)");

                    $stmt->bindParam(":snowflake", $id);
                    $stmt->bindParam(":id", $user['id']);
                    $stmt->bindParam(":comment", $_POST['comment']);
                    $stmt->bindParam(":embed", $reweet[1]);
            
                    $stmt->execute();        
                } else {
                    // REALLY ugly.
                    
                    $stmt = $this->Connection->prepare("INSERT INTO feed (feed_id, feed_owner, feed_text) VALUES (:snowflake, :id, :comment)");

                    $stmt->bindParam(":snowflake", $id);
                    $stmt->bindParam(":id", $user['id']);
                    $stmt->bindParam(":comment", $_POST['comment']);
            
                    $stmt->execute();
                }
            } else {
                $stmt = $this->Connection->prepare("INSERT INTO feed (feed_id, feed_owner, feed_text) VALUES (:snowflake, :id, :comment)");

                $stmt->bindParam(":snowflake", $id);
                $stmt->bindParam(":id", $user['id']);
                $stmt->bindParam(":comment", $_POST['comment']);
        
                $stmt->execute();        
            }
        } else {
            $stmt = $this->Connection->prepare("INSERT INTO feed (feed_id, feed_owner, feed_text) VALUES (:snowflake, :id, :comment)");

            $stmt->bindParam(":snowflake", $id);
            $stmt->bindParam(":id", $user['id']);
            $stmt->bindParam(":comment", $_POST['comment']);
    
            $stmt->execute();
        }


        $alert->CreateAlert(Level::Success, "Successfully weeted!");
    }
}