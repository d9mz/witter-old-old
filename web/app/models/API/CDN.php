<?php
namespace Witter\Models;

enum ContentType: int {
    case ProfilePicture = 0;
    case Banner = 1;
}

class CDN extends Model
{
    // create database instance -- this shouldn't really be done here but it's
    // a temporary solution to the infinite connection issue
    public function __construct() {
        $connection = new \Witter\Models\Connection();
        $this->Connection = $connection->MakeConnection();
    }
    
    // Not actually md5, just too lazy to change to base64
    public function GetCSS(string $md5) {
        $userModel = new \Witter\Models\User();
        if(!$userModel->UserExists(base64_decode($md5))) die("User doesn't exist");
        $user = $userModel->GetUser(base64_decode($md5));

        header("Content-type: text/css");

        $prepend_css = "/* witter - " . $md5 . " */

body, html {
    margin-top: 5px;
}

#banner {
    background-image: url('/cdn/" . $user['banner'] . "');
    border-bottom: var(--primary-border-color);
    background-repeat: no-repeat;
    background-color: #d5d5d5;
    background-size: cover;
    background-position: center;
    width: 800px;
    margin-top: 0px;
}

.user-album-container {

}

/* witter - " . $md5 . " */\n\n";

        $user['css'] = $prepend_css . $user['css'];
        die($user['css']);
    }

    // Return filename of PFP -- :D
    // most likely returns md5 hash of file
    public function GetFile(string $md5) {
        $util  = new \Witter\Models\Utility();
        $default = "/var/www/volumes/profile_picture/default";

        // get cache from database
        $cache = $this->GetCache($md5);
        if(isset($cache['data'])) {
            $cache = json_decode($cache['data']);
            $type  = $this->StringToContentType($cache->type);

            // get mime type from file type stored in cache & set as content-type
            $mime = $util->ext2mime($cache->file_type);
            header("Content-type: " . $mime);
            
            $filename = "/var/www/volumes/" . $cache->type . "/" . $cache->file_name;
            // get actual file itself
            // stupid edge case for banners which i really shouldn't be doing but i don't care
            if($type == ContentType::Banner) {
                if(file_exists($filename)) $file = file_get_contents($filename);
                if(!file_exists($filename)) $file = "";
            } else {
                if(file_exists($filename)) $file = file_get_contents($filename);
                if(!file_exists($filename)) $file = file_get_contents($default);
            }
        } elseif($md5 == "default") {
            header("Content-type: image/png");
            $file = file_get_contents($default);
        } else {
            $file = "Invalid hash";
        }

        die($file);
    }

    public function ContentTypeToString(ContentType $type) : string {
        // BEWARE: this corresponds to what the folder in /var/www/volumes/* should be!!!
        // ex: ContentType::ProfilePicture => /var/www/volumes/profile_picture/

        return match ($type) {
            ContentType::ProfilePicture => "profile_picture",
            ContentType::Banner => "banner",
        };
    }

    public function StringToContentType(string $type) : ContentType {
        return match ($type) {
            "profile_picture" => ContentType::ProfilePicture,
            "banner" => ContentType::Banner,
        };
    }

    public function ConstructCache(ContentType $type, int $owned_by, string $file_name, string $file_type, string $version = "1.0") : object {
        // TODO: Kind of redundant? But it makes the Type look nice so I don't really care
        $type = $this->ContentTypeToString($type);

        return (object) [
            "type" => $type,
            "owned_by" => $owned_by,
            "file_name" => $file_name,
            "file_type" => $file_type,
            "version"   => $version
        ];
    }

    public function DeleteCache() {
        // TODO: Implement
    }

    public function GetCacheByOwner(int $owner, ContentType $type = ContentType::ProfilePicture) {
        $type = $this->ContentTypeToString($type);
        $stmt = $this->Connection->prepare("SELECT * FROM cache WHERE data->>'$.owned_by' = :owner AND data->>'$.type' = :type ORDER BY id DESC");
        $stmt->bindParam(":owner", $owner);
        $stmt->bindParam(":type", $type);
        $stmt->execute();

        return ($stmt->rowCount() === 0 ? 0 : $stmt->fetch(\PDO::FETCH_ASSOC));
    }

    public function GetCache(string $hash, $type = null) {
        if($type == null) {
            $stmt = $this->Connection->prepare("SELECT * FROM cache WHERE data->>'$.file_name' = :hash ORDER BY id DESC");
            $stmt->bindParam(":hash", $hash);
            $stmt->execute();
        } else {
            $type = $this->ContentTypeToString($type);
            $stmt = $this->Connection->prepare("SELECT * FROM cache WHERE data->>'$.file_name' = :hash AND data->>'$.type' = :type ORDER BY id DESC");
            $stmt->bindParam(":hash", $hash);
            $stmt->bindParam(":type", $type);
            $stmt->execute();
        }

        return ($stmt->rowCount() === 0 ? 0 : $stmt->fetch(\PDO::FETCH_ASSOC));
    }

    public function AddCache(object $data) {
        $data = json_encode($data); // encode data, we are expecting object

        $stmt = $this->Connection->prepare("INSERT INTO cache (data) VALUES (:data)");
        $stmt->bindParam(":data", $data);
        $stmt->execute();

        // data is json type :)
        // return something?
    }
}