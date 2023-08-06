<?php
namespace Witter\Models;

/*
    notifications table:
    - initiator: id of the user who originally started notfi
    - recipient: id of user
    - targets: could contain multiple users, comma delimited & user ids
    - type: enum, defined below
*/

enum NotificationTypes : int {
    case UserFollowed = 0;

    case WeetRepliedTo = 1;
    case WeetLiked = 2;
    case WeetReweeted = 3;
}

class Notifications extends Model {
    // create database instance -- this shouldn't really be done here but it's
    // a temporary solution to the infinite connection issue
    public function __construct() {
        $connection = new \Witter\Models\Connection();
        $this->Connection = $connection->MakeConnection();
    }

    public function getUnreadNotifCount(string $user) : int {
        $userModel = new \Witter\Models\User();
        $user = $userModel->GetUser($user, Type::Username, true);

        $query = "SELECT id FROM notifications WHERE recipient = :find AND read_notif = 'n'";
        $stmt = $this->Connection->prepare($query);
        $stmt->bindParam(":find", $user['id']);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function SetReadAllNotifications() : void {
        // this will apply to the current logged in user
        $userModel = new \Witter\Models\User();
        $userID = $userModel->GetUID($_SESSION['Handle']);

        $stmt = $this->Connection->prepare(
            "UPDATE notifications
            SET read_notif = 'y'
            WHERE recipient = ? AND read_notif = 'n'"
        );

        $stmt->execute([$userID]);
    }

    // incomplete
    public function getUnreadNotifications() : array {
        // take into account: last_modified as well
        // createNotification will UPDATE an already existing targets [weet] & set last_modified
        // to now()

        $userModel = new \Witter\Models\User;
        $currentUser = $userModel->GetUser($_SESSION['Handle']);

        $Notifs = $this->Connection->prepare("SELECT * FROM notifications WHERE recipient = :id");
        $Notifs->bindParam(":id", $currentUser['id']);
        $Notifs->execute();

        $notifs = [];

        // Relation: get user info while fetching forum
        while ($notif = $Notifs->fetch(\PDO::FETCH_ASSOC)) {
            $user = $userModel->GetUser($notif['initiator'], Type::ID);
            $notif = $this->NotificationTypeToString($notif);
            $notif["user"] = $user;
            $notifs[] = $notif;
        }

        return @$notifs;
    }

    public function NotificationTypeToString(array $notification) : array {
        $userModel = new \Witter\Models\User();
        $weetModel = new \Witter\Models\Feed();

        // transform the ['type'] in a notification array to a string to be outputted by twig

        if(!isset($notification['type'])) return $notification;

        if($notification['type'] == 2) { 
            $targets = explode(",", $notification['targets']);
            $targets = array_filter($targets);

            // user followed
            $recipient = $userModel->GetUser($notification['recipient'], Type::ID, true);
            $initiator = $userModel->GetUser($notification['initiator'], Type::ID, true);
            $weet      = $weetModel->GetWeet($targets[0], true);

            // weet referenced got deleted, bye bye!
            if(!isset($weet['id'])) return [];

            // this is pretty long...
            $notification['type'] = sprintf("<b>%s</b> (<a href='/user/%s'>@%s</a>) liked your weet: .", $initiator['nickname'], $initiator['username'], $initiator['username']);
            $notification['weet'] = $weet;
        }

        if($notification['type'] == 1) { 
            $targets = explode(",", $notification['targets']);
            $targets = array_filter($targets);

            // user followed
            $recipient = $userModel->GetUser($notification['recipient'], Type::ID, true);
            $initiator = $userModel->GetUser($notification['initiator'], Type::ID, true);
            $weet      = $weetModel->GetWeet($targets[0], false);

            // weet referenced got deleted, bye bye!
            if(!isset($weet['id'])) return [];

            // this is pretty long...
            $notification['type'] = sprintf("<b>%s</b> (<a href='/user/%s'>@%s</a>) replied to your weet: .", $initiator['nickname'], $initiator['username'], $initiator['username']);
            $notification['weet'] = $weet;
        }

        if($notification['type'] == 3) { 
            $targets = explode(",", $notification['targets']);
            $targets = array_filter($targets);

            // user followed
            $recipient = $userModel->GetUser($notification['recipient'], Type::ID, true);
            $initiator = $userModel->GetUser($notification['initiator'], Type::ID, true);
            $weet      = $weetModel->GetWeet($targets[0], false);

            // weet referenced got deleted, bye bye!
            if(!isset($weet['id'])) return [];

            // this is pretty long...
            $notification['type'] = sprintf("<b>%s</b> (<a href='/user/%s'>@%s</a>) reposted your weet: .", $initiator['nickname'], $initiator['username'], $initiator['username']);
            $notification['weet'] = $weet;
        }

        if($notification['type'] == 0) { 
            // user followed
            $recipient = $userModel->GetUser($notification['recipient'], Type::ID, true);
            $initiator = $userModel->GetUser($notification['initiator'], Type::ID, true);

            // this is pretty long...
            $notification['type'] = sprintf("<b>%s</b> (<a href='/user/%s'>@%s</a>) has started to follow you.", $initiator['nickname'], $initiator['username'], $initiator['username']);
        }

        return $notification;
    }
    
    public function CreateNotification(
        NotificationTypes $type, 
        array $targets, 
        int $recipient, 
        int $initiator, 
        string $icon
    ) : void {
        // prevent self notification giving
        if($recipient == $initiator) return;

        // prevents end user from just spamming notifications
        $cooldownModel = new \Witter\Models\Cooldown();

        if(!$cooldownModel->GetCooldown("notif_cooldown", $_SESSION['Handle'], 2)) {
            return;
        } else {
            $cooldownModel->SetCooldown("notif_cooldown", $_SESSION['Handle']);
        }

        // $icon is fontawesome icon, will already be prefixed with `fa-` 
        // anyways so no need to include that

        // this function ASSUMES that $targets is of all int type

        // for simple one recipient, one initiator $targets can be just []
        $targets = array_filter($targets);
        $targets = implode(",", $targets);

        if($type == NotificationTypes::UserFollowed) {
            $stmt = $this->Connection->prepare(
                "INSERT INTO notifications
                    (icon, recipient, initiator, type, targets) 
                VALUES 
                    (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $icon,
                $recipient,
                $initiator,
                0, // shouldn't be hardcoded but enums don't have __toString magic method
                $targets,
            ]);
        }

        if($type == NotificationTypes::WeetRepliedTo) {
            $stmt = $this->Connection->prepare(
                "INSERT INTO notifications
                    (icon, recipient, initiator, type, targets) 
                VALUES 
                    (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $icon,
                $recipient,
                $initiator,
                1, // shouldn't be hardcoded but enums don't have __toString magic method
                $targets,
            ]);
        }

        if($type == NotificationTypes::WeetReweeted) {
            $stmt = $this->Connection->prepare(
                "INSERT INTO notifications
                    (icon, recipient, initiator, type, targets) 
                VALUES 
                    (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $icon,
                $recipient,
                $initiator,
                3, // shouldn't be hardcoded but enums don't have __toString magic method
                $targets,
            ]);
        }


        if($type == NotificationTypes::WeetLiked) {
            // Check for existing notification with the same initiator, type, recipient, and targets
            $stmt = $this->Connection->prepare(
                "SELECT * FROM notifications WHERE initiator = ? AND type = ? AND recipient = ? AND targets = ?"
            );
    
            $stmt->execute([$initiator, 0, $recipient, $targets]);
            $existing_notification = $stmt->fetch();
    
            if($existing_notification) {
                // Update the targets with the new liker
                $targets = $existing_notification['targets'] . ',' . $targets;
    
                // Update the icon to reflect the new like count
    
                $stmt = $this->Connection->prepare(
                    "UPDATE notifications
                    SET targets = ?
                    WHERE initiator = ? AND type = ? AND recipient = ? AND targets = ?"
                );
    
                $stmt->execute([$targets, $initiator, 0, $recipient, $targets]);
            } else {
                // No existing notification, create a new one
                $stmt = $this->Connection->prepare(
                    "INSERT INTO notifications
                    (icon, recipient, initiator, type, targets)
                    VALUES (?, ?, ?, ?, ?)"
                );
    
                $stmt->execute([$icon, $recipient, $initiator, 2, $targets]);
            }
        }
    }
}