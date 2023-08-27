<?php
namespace Witter\Models;

use Witter\Models\Level;
use Witter\Models\Type;

class LastFM extends Model {
    public function __construct() {
        $connection = new \Witter\Models\Connection();
        $this->Connection = $connection->MakeConnection();
    }

    public function constructURL(array $params) : string {
        // http://ws.audioscrobbler.com/2.0/?method=artist.getsimilar&artist=cher&api_key=YOUR_API_KEY&format=json
        
        $queryString = http_build_query($params);
        $url = "http://ws.audioscrobbler.com/2.0/?%s";

        return sprintf($url, $queryString);
    }
    public function createApiSig(array $params, string $secret) : string {
        // Step 1: Sort parameters alphabetically
        ksort($params);

        // Step 2: Concatenate parameters
        $concatenatedString = '';
        foreach($params as $key => $value) {
            // Step 3: Ensure parameters are utf8 encoded
            $key = mb_convert_encoding($key, 'UTF-8');
            $value = mb_convert_encoding($value, 'UTF-8');
            
            $concatenatedString .= $key . $value;
        }

        // Step 4: Append secret
        $concatenatedString .= $secret;

        // Step 5: Return md5 hash
        return md5($concatenatedString);

        /*
            Why the fuck is the API so complicated?? 
            $params = [
                'api_key' => 'xxxxxxxx',
                'method' => 'auth.getSession',
                'token' => 'xxxxxxx'
            ];
            $secret = 'mysecret';

            $apiSignature = createApiSignature($params, $secret);
        */
    }

    // Performance on running this under Base is horrible
    // So we're going to be running this unnder a few pages
    // Performance hit with this is negligable -- maybe like 30ms?
    public function updateCurrentListeningSong() : void {
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

                $stmt = $this->Connection->prepare("UPDATE users SET lastfm_track_scrobbling = ? WHERE username = ?");
                $stmt->execute([
                    $track,
                    $_SESSION['Handle'],
                ]);
            }
        }
    }
}