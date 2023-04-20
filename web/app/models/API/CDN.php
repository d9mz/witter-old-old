<?php
namespace Witter\Models;

class CDN extends ModelBase
{
    // Return filename of PFP -- :D
    // most likely returns md5 hash of file
    public function GetUserThumbnail($username, $latest = true) {
        // return PNG, file_get_contents?
    }

    public function ConstructCache($type, $owned_by, $file_name, $version = "1.0") : object {
        return (object) [
            "type" => $type,
            "owned_by" => $owned_by,
            "file_name" => $file_name,
            "version"   => $version
        ];
    }

    // takes ->GetUser array.
    public function SetAvatarThumnail(array $user, $path, $destination) {
        // Insert "cache" thing into "cache" table
        // Every "cached" element will follow this format:
        $cache = $this->ConstructCache("avatar", $user['username'], $path, $destination);

        $img = \Image::make('public/foo.jpg');
    }
}