<?php
namespace Witter\Models;

class SearchFlags extends BitwiseFlag
{
    const FLAG_WEETS = 1; // BIT #1 of $flags has the value 1
    const FLAG_HASHTAG = 2; // BIT #2 of $flags has the value 2
    const FLAG_USER = 4; // BIT #3 of $flags has the value 4
    public function isSearchingForWeets(): bool {
        return $this->isFlagSet(self::FLAG_WEETS);
    }

    public function isSearchingForHashtags(): bool {
        return $this->isFlagSet(self::FLAG_HASHTAG);
    }

    public function isSearchingForUsers(): bool {
        return $this->isFlagSet(self::FLAG_USER);
    }

    public function setSearchingForWeets(bool $value): void {
        $this->setFlag(self::FLAG_WEETS, $value);
    }

    public function setSearchingForHashtags(bool $value): void {
        $this->setFlag(self::FLAG_HASHTAG, $value);
    }

    public function setSearchingForUsers(bool $value): void {
        $this->setFlag(self::FLAG_USER, $value);
    }

    public function setFlags(object $flagsObject): void {
        if (isset($flagsObject->FLAG_WEETS)) {
            $this->setSearchingForWeets($flagsObject->FLAG_WEETS);
        }
        if (isset($flagsObject->FLAG_HASHTAG)) {
            $this->setSearchingForHashtags($flagsObject->FLAG_HASHTAG);
        }
        if (isset($flagsObject->FLAG_USER)) {
            $this->setSearchingForUsers($flagsObject->FLAG_USER);
        }
    }

    public function getFlagsAsObject(): object {
        return (object) [
            "FLAG_WEETS" => $this->isSearchingForWeets(),
            "FLAG_HASHTAG" => $this->isSearchingForHashtags(),
            "FLAG_USER" => $this->isSearchingForUsers(),
        ];
    }
}

class Search extends Model
{
    public function __construct() {
        $connection = new \Witter\Models\Connection();
        $this->Connection = $connection->MakeConnection();
    }

    public function getSearchQuery(string $query, object $flags): array {
        $searchFlags = new \Witter\Models\SearchFlags();
        $searchFlags->setFlags($flags);

        $userModel = new \Witter\Models\User();
        $feedModel = new \Witter\Models\Feed();
    
        // Prepare the query

        // ugly as shit
        if($searchFlags->isSearchingForWeets()) {
            $searchTerm = "%" . $query . "%"; // Make sure to sanitize $query

            // construct query
            $queryString = "
                SELECT f.feed_id, u.username, f.feed_text, f.feed_created
                FROM feed AS f
                JOIN users AS u ON f.feed_owner = u.id
                WHERE (f.feed_text LIKE :search2)
                ORDER BY f.feed_created DESC LIMIT 30";

            $query = $this->Connection->prepare($queryString);
            $query->bindParam(':search2', $searchTerm, \PDO::PARAM_STR); // Bind the same search term for feed text
            $query->execute();
        } elseif($searchFlags->isSearchingForUsers()) {
            $searchTerm = "%@" . $query . "%"; // Make sure to sanitize $query
            $searchTerm2 = "%" . $query . "%";

            // construct query
            $queryString = "
                SELECT f.feed_id, u.username, f.feed_text, f.feed_created
                FROM feed AS f
                JOIN users AS u ON f.feed_owner = u.id
                WHERE (u.username LIKE :search1 OR f.feed_text LIKE :search2)
                ORDER BY f.feed_created DESC LIMIT 30";

            $query = $this->Connection->prepare($queryString);
            $query->bindParam(':search1', $searchTerm2, \PDO::PARAM_STR); // Bind the search term for username
            $query->bindParam(':search2', $searchTerm, \PDO::PARAM_STR); // Bind the same search term for feed text
            $query->execute();
        } elseif($searchFlags->isSearchingForHashtags()) {
            $searchTerm = "%#" . $query . "%"; // Make sure to sanitize $query
            $searchTerm2 = "%" . $query . "%";

            // construct query
            $queryString = "
                SELECT f.feed_id, u.username, f.feed_text, f.feed_created
                FROM feed AS f
                JOIN users AS u ON f.feed_owner = u.id
                WHERE (f.feed_text LIKE :search2)
                ORDER BY f.feed_created DESC LIMIT 30";

            $query = $this->Connection->prepare($queryString);
            $query->bindParam(':search2', $searchTerm, \PDO::PARAM_STR); // Bind the same search term for feed text
            $query->execute();
        } else {
            return [];
        }
        
        // Fetch the results
        $feed = $query->fetchAll(\PDO::FETCH_ASSOC);
    
        // Check if feed is empty
        if($query->rowCount() == 0) {
            return []; // No posts found, return empty array
        }
    
        $realFeed = [];

        foreach($feed as &$post) {
            $realFeed[] = $feedModel->GetWeet($post['feed_id'], false);
        }
    
        return $realFeed;
    }
}
