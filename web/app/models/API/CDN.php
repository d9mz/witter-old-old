<?php
namespace Witter\Models;

enum ContentType: int {
    case ProfilePicture = 0;
    case Banner = 1;
}

class CDN extends ModelBase
{
    // Return filename of PFP -- :D
    // most likely returns md5 hash of file
    public function GetFile(string $md5) {
        $util  = new \Witter\Models\Utility();

        // get cache from database
        $cache = $this->GetCache($md5);
        $cache = json_decode($cache['data']);

        // get mime type from file type stored in cache & set as content-type
        $mime   = $util->ext2mime($cache->file_type);
        header("Content-type: " . $mime);

        // get actual file itself
        $file = file_get_contents("/var/www/volumes/" . $cache->type . "/" . $cache->file_name);

        die($file);
    }

    public function ContentTypeToString(ContentType $type) {
        // BEWARE: this corresponds to what the folder in /var/www/volumes/* should be!!!
        // ex: ContentType::ProfilePicture => /var/www/volumes/profile_picture/
        
        return match ($type) {
            ContentType::ProfilePicture => "profile_picture",
            ContentType::Banner => "banner",
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

    public function GetCache(string $hash, $type = null) {
        if($type == null) {
            $stmt = $this->Connection->prepare("SELECT * FROM cache WHERE data->>'$.file_name' = :hash");
            $stmt->bindParam(":hash", $hash);
            $stmt->execute();
        } else {
            $type = $this->ContentTypeToString($type);
            $stmt = $this->Connection->prepare("SELECT * FROM cache WHERE data->>'$.file_name' = :hash AND data->>'$.type' = :type");
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