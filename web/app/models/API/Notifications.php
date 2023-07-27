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
    case UserFollowed = 1;

    case WeetRepliedTo = 0;
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
    
    public function CreateNotification(
        NotificationTypes $type, 
        array $targets, 
        int $recipient, 
        int $initiator, 
        string $icon
    ) : void {
        // $icon is fontawesome icon, will already be prefixed with `fa-` 
        // anyways so no need to include that

        // this function ASSUMES that $targets is of all int type

        // for simple one recipient, one initiator $targets can be just []

        if($type == NotificationTypes::UserFollowed) {

        }
    }
}