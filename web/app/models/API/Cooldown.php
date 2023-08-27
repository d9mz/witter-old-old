<?php
namespace Witter\Models;

// Should this be used in the Utility class?
// Not sure.
class Cooldown extends Model {
    // create database instance -- this shouldn't really be done here but it's
    // a temporary solution to the infinite connection issue
    public function __construct() {
        $connection = new \Witter\Models\Connection();
        $this->Connection = $connection->MakeConnection();
    }
    
    //TODO: POTTENTIALLY include __construct that passes the User array to reduce # of database queries?

    // TODO: Clean up this function... Looks pretty ugly
    public function GetCooldown(string $column, int|string $user, int $cooldown) : bool {
        $userModel = new \Witter\Models\User();
        
        if(filter_var($user, FILTER_VALIDATE_INT)) {
            if($userModel->UserExists($user, Type::ID)) {
                // the $column parameter should ALWAYS be hardcoded!!!
                $stmt = $this->Connection->prepare("SELECT id FROM users WHERE id = :id AND " . $column . " >= NOW() - INTERVAL " . $cooldown . " SECOND");
                $stmt->bindParam(":id", $user);
                $stmt->execute();
                if($stmt->rowCount() === 1) {
                    return false;
                }
                
                return true;
            } else {
                return false;
                // SHOULDN'T be happening!
                // TODO: Create "panic" condition where something should 
                //       never happen and delete all current sessions
            }
        } else {
            if($userModel->UserExists($user, Type::Username)) {
                // the $column parameter should ALWAYS be hardcoded!!!
                $stmt = $this->Connection->prepare("SELECT * FROM users WHERE username = :username AND " . $column . " >= NOW() - INTERVAL " . $cooldown . " SECOND");
                $stmt->bindParam(":username", $user);
                $stmt->execute();
                if($stmt->rowCount() === 1) {
                    return false;
                }

                return true;
            } else {
                return false;
            }
        }

        // true: user can post/do action,
        // false: user still has to wait
    }

    // TODO: Clean up this function too... Looks pretty ugly
    public function SetCooldown(string $column, int|string $user) : void {
        $userModel = new \Witter\Models\User();

        if(filter_var($user, FILTER_VALIDATE_INT)) {
            if($userModel->UserExists($user, Type::ID)) {
                // the $column parameter should ALWAYS be hardcoded!!!
                $stmt = $this->Connection->prepare("UPDATE users SET " . $column . " = NOW() WHERE id = ?");
                $res = $stmt->execute([$user]);
            }
        } else {
            if($userModel->UserExists($user, Type::Username)) {
                // the $column parameter should ALWAYS be hardcoded!!!
                $stmt = $this->Connection->prepare("UPDATE users SET " . $column . " = NOW() WHERE username = ?");
                $res = $stmt->execute([$user]);
            }
        }
    }
}