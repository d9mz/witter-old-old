<?php
namespace Witter\Models;

use Intervention\Image\ImageManager;

class Settings extends ModelBase
{
    public function ProfilePicture() {
        // TODO: This is okay, but it could be better.

        $cdn    = new \Witter\Models\CDN();
        $alert  = new \Witter\Models\Alert();
        $util   = new \Witter\Models\Utility();
        $user   = new \Witter\Models\User();
        $user   = $user->GetUser($_SESSION['Handle']);

        // Is the file bigger than 500kb?
        if($_FILES['asset']['size'] > 500000) {
            $alert->CreateAlert(Level::Error, "Your upload cannot be larger than 500Kb.");
        }

        // Check if PHP reported an error with the upload.
        switch ($_FILES['asset']['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $alert->CreateAlert(Level::Error, "You have uploaded no file.");
            default:
                $alert->CreateAlert(Level::Error, "There was an unknown error processing your request.");
        }

        // Check MIME type of file (Always read from the file!)
        $finfo     = new \finfo(FILEINFO_MIME_TYPE);
        $mime      = $finfo->file($_FILES['asset']['tmp_name']);
        $extension = $util->mime2ext($mime);

        if (false === $ext = array_search(
                $mime,
                array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                ),
                true
            )) {
            $alert->CreateAlert(Level::Error, "Your file is not an image.");
        }

        // For CDN
        $md5 = md5_file($_FILES['asset']['tmp_name']);

        // resize image with image lib
        // really wanna use imagick here but it's being a little bitch :(
        $manager = new ImageManager(['driver' => 'gd']);
        $image = $manager
            ->make($_FILES['asset']['tmp_name'])
            ->resize(500, 500)
            ->save('/var/www/volumes/profile_picture/' . $md5);

        // add said uploaded image to cache
        // attach owner to user id
        $cache = $cdn->ConstructCache(ContentType::ProfilePicture, $user['id'], $md5, "." . $extension);

        // push to db
        $cdn->AddCache($cache);

        $alert->CreateAlert(Level::Success, "Successfully uploaded your profile!.");
    }
}