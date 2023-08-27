<?php
namespace Witter;

class Base extends Configurator {
    public $Connection;
    public $Configuration;
    public $Twig;

    function __construct() {
        parent::__construct();
        $this->MakeConnection();
    }

    function MakeConnection() : \PDO {
        try
        {
            $Connection = new \PDO("mysql:host=" . $this->Configuration->Database->DatabaseHost . ";dbname=" . $this->Configuration->Database->DatabaseName . ";charset=utf8mb4",
                $this->Configuration->Database->DatabaseUsername,
                $this->Configuration->Database->DatabasePassword,
                [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ]
            );

            $Connection->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }
        catch(\PDOException $e)
        {
            die("An error occured connecting to the database: " . $e->getMessage());
        }

        if(isset($_SESSION['Handle'])) {
            $userModel = new \Witter\Models\User();
            $cooldownModel = new \Witter\Models\Cooldown();
            $fmModel = new \Witter\Models\LastFM();

            // avg length of song according to google - 3 min 30 sec
            // 3 min 30 sec => 210 sec
            if($cooldownModel->GetCooldown("scrobble_cooldown", $_SESSION['Handle'], 210)) {
                $token = $userModel->getLastFmToken($_SESSION['Handle']);
                $username = $userModel->getLastFmUser($_SESSION['Handle']);

                if(!empty($token) && !empty($username)) {
                    $sig = $fmModel->createApiSig([
                        'api_key' => getenv("LASTFM_API_KEY"),
                        'method' => 'user.getRecentTracks',
                        'token' => $token,
                    ], getenv("LASTFM_API_SECRET"));
            
                    $url = $fmModel->constructURL([
                        'method' => 'user.getRecentTracks',
                        'limit' => 1,
                        'user' => $username,
                        'api_key' => getenv("LASTFM_API_KEY"), 
                        'format' => 'json',
                    ]);
                    
                    $url = urldecode($url);
                    $tracks = json_decode(file_get_contents($url));

                    // get the actually necessary info
                    $firstTrack = $tracks->recenttracks->track[0];

                    $albumCoverMedium = "";
                    foreach ($firstTrack->image as $image) {
                        if ($image->size == "medium") {
                            $albumCoverMedium = $image->{"#text"};
                            break; // No need to continue looping once we've found the medium size
                        }
                    }
                    
                    $isPlaying = isset($firstTrack->{"@attr"}) && isset($firstTrack->{"@attr"}->nowplaying) && $firstTrack->{"@attr"}->nowplaying == "true";
                    
                    $lastScrobbled = isset($firstTrack->date) ? $firstTrack->date->{"#text"} : null;
                    
                    $track = (object) [
                        "track_author" => $firstTrack->artist->{"#text"},
                        "track_album" => $firstTrack->album->{"#text"},
                        "track_title" => $firstTrack->name,
                        "is_playing" => $isPlaying,
                        "last_scrobbled" => $lastScrobbled,
                        "album_cover" => $albumCoverMedium,
                        "track_url" => $firstTrack->url 
                    ];                 

                    $track = json_encode($track);

                    $stmt = $Connection->prepare("UPDATE users SET lastfm_track_scrobbling = ? WHERE username = ?");
                    $stmt->execute([
                        $track,
                        $_SESSION['Handle'],
                    ]);
                }
            }

            $stmt = $Connection->prepare("UPDATE users SET last_login = NOW() WHERE username = ?");
            $stmt->execute([
                $_SESSION['Handle'],
            ]);
        }

        // Non-CF IP header
        if(isset($_SERVER['REMOTE_ADDR'])) {
            $SessionIP = $_SERVER['REMOTE_ADDR'];
        }

        // CF IP header
        if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $SessionIP = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        if(!isset($SessionIP)) {
            $SessionIP = "0.0.0.0";
        }

        return $Connection;
    }
}