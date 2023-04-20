<?php
namespace Witter\Models;

class Feed extends ModelBase
{
    // returns pdo loopabble thingy, can use while
    public function GetFeed($type = "following", $limit = 20) : array {
        $Feed = $this->Connection->prepare("SELECT * FROM feed ORDER BY id DESC LIMIT " . $limit);
        $Feed->execute();

        return $Feed->fetch(\PDO::FETCH_ASSOC);
    }
}