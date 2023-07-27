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

    // incomplete
    public function getUnreadNotifications(string $user) : int {
        // take into account: last_modified as well
        // createNotification will UPDATE an already existing targets [weet] & set last_modified
        // to now()

        return 0;
    }

    public function NotificationTypeToString(array $notification) : array {
        $userModel = new \Witter\Models\User();

        // transform the ['type'] in a notification array to a string to be outputted by twig

        if($notification['type'] == 0) { 
            // user followed
            $recipient = $userModel->GetUser($notification['recipient'], Type::ID, true);
            $initiator = $userModel->GetUser($notification['initiator'], Type::ID, true);

            // this is pretty long...
            $notification['type'] = sprintf("%s (<a href='/user/%s'>@%s</a>) has started to follow you.", $initiator['nickname'], $initiator['username'], $initiator['username']);
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
        // prevents end user from just spamming notifications
        $cooldownModel = new \Witter\Models\Cooldown();

        if(!$cooldownModel->GetCooldown("notif_cooldown", $_SESSION['Handle'], 10)) {
            return;
        } else {
            $cooldownModel->SetCooldown("notif_cooldown", $_SESSION['Handle']);
        }

        // $icon is fontawesome icon, will already be prefixed with `fa-` 
        // anyways so no need to include that

        // this function ASSUMES that $targets is of all int type

        // for simple one recipient, one initiator $targets can be just []

        if($type == NotificationTypes::UserFollowed) {
            $targets = implode(",", $targets);

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
    }
}