<?php
namespace Witter\Models;

class SearchFlags extends BitwiseFlag
{
  const FLAG_WEETS = 1; // BIT #1 of $flags has the value 1
  const FLAG_HASHTAG = 2;     // BIT #2 of $flags has the value 2
  const FLAG_USER = 4;     // BIT #3 of $flags has the value 4

  public function __construct($flags = []) {
    
  }

  public function isSearchingForWeets() : bool {
    return $this->isFlagSet(self::FLAG_WEETS);
  }

  public function isSearchingForHashtags() : bool {
    return $this->isFlagSet(self::FLAG_HASHTAG);
  }

  public function isSearchingForUsers() : bool {
    return $this->isFlagSet(self::FLAG_USER);
  }

  public function setSearchingForWeets(bool $value) : void {
    $this->setFlag(self::FLAG_WEETS, $value);
  }

  public function setSearchingForHashtags(bool $value) : void {
    $this->setFlag(self::FLAG_HASHTAG, $value);
  }

  public function setSearchingForUsers(bool $value) : void {
    $this->setFlag(self::FLAG_USER, $value);
  }

  public function getFlagsAsObject() : object {
    return (object) [
      'FLAG_WEETS' => $this->isSearchingForWeets(),
      'FLAG_HASHTAG' => $this->isSearchingForHashtags(),
      'FLAG_USER' => $this->isSearchingForUsers()
    ];
  }  
}

class Search extends Model {
    public function __construct() {
        $connection = new \Witter\Models\Connection();
        $this->Connection = $connection->MakeConnection();
    }
    
    public function getSearchQuery(string $query, object $flags) : array {
        $searchFlags = new \Witter\Models\SearchFlags();

        echo $query;
        print_r($flags);

        return [];
    }
}