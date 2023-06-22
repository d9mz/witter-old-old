<?php
namespace Witter\Models;

use Intervention\Image\ImageManager;

class Settings extends Model
{
    public function Description() {
        $alert  = new \Witter\Models\Alert();
        $user   = new \Witter\Models\User();
        $user   = $user->GetUser($_SESSION['Handle']);

        if (!isset($_POST['description']) && !empty(trim($_POST['description']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a description.");
        }

        if (strlen($_POST['description']) < 4 || strlen($_POST['description']) > 200) {
            $alert->CreateAlert(Level::Error, "Your description must be longer than 3 characters and not longer than 200.");
        }

        $stmt = $this->Connection->prepare("UPDATE users SET description = ? WHERE id = ?");
        $stmt->execute([
            $_POST['description'],
            $user['id'],
        ]);
        $stmt = null;

        $alert->CreateAlert(Level::Success, "Successfully set your description.");
    }

    public function CSS() {
        $alert  = new \Witter\Models\Alert();
        $user   = new \Witter\Models\User();
        $user   = $user->GetUser($_SESSION['Handle']);

        if (strlen(@$_POST['css']) > 2048) {
            $alert->CreateAlert(Level::Error, "Your description must be longer than 3 characters and not longer than 200.");
        }

        $stmt = $this->Connection->prepare("UPDATE users SET css = ? WHERE id = ?");
        $stmt->execute([
            $_POST['css'],
            $user['id'],
        ]);
        $stmt = null;

        $alert->CreateAlert(Level::Success, "Successfully set your CSS.");
    }

    public function Nickname() {
        $cdn    = new \Witter\Models\CDN();
        $alert  = new \Witter\Models\Alert();
        $util   = new \Witter\Models\Utility();
        $user   = new \Witter\Models\User();
        $user   = $user->GetUser($_SESSION['Handle']);

        if (!isset($_POST['nickname']) && !empty(trim($_POST['nickname']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a display name.");
        }

        if (strlen($_POST['nickname']) < 4 || strlen($_POST['nickname']) > 20) {
            $alert->CreateAlert(Level::Error, "Your display name must be longer than 3 characters and not longer than 20.");
        }

        $stmt = $this->Connection->prepare("UPDATE users SET nickname = ? WHERE id = ?");
        $stmt->execute([
            $_POST['nickname'],
            $user['id'],
        ]);
        $stmt = null;

        $alert->CreateAlert(Level::Success, "Successfully set your display name to " . $_POST['nickname'] . ".");
    }

    public function Banner() {
        // TODO: This is okay, but it could be better.

        $cdn    = new \Witter\Models\CDN();
        $alert  = new \Witter\Models\Alert();
        $util   = new \Witter\Models\Utility();
        $user   = new \Witter\Models\User();
        $user   = $user->GetUser($_SESSION['Handle']);

        // Is the file bigger than 1mb?
        if($_FILES['banner']['size'] > 1000000) {
            $alert->CreateAlert(Level::Error, "Your upload cannot be larger than 1mb.");
        }

        // Check if PHP reported an error with the upload.
        switch ($_FILES['banner']['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $alert->CreateAlert(Level::Error, "You have uploaded no file.");
            default:
                $alert->CreateAlert(Level::Error, "There was an unknown error processing your request.");
        }

        // Check MIME type of file (Always read from the file!)
        $finfo     = new \finfo(FILEINFO_MIME_TYPE);
        $mime      = $finfo->file($_FILES['banner']['tmp_name']);
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
        $md5 = md5_file($_FILES['banner']['tmp_name']);

        // resize image with image lib
        // really wanna use imagick here but it's being a little bitch :(
        $manager = new ImageManager(['driver' => 'imagick']);
        $image = $manager
            ->make($_FILES['banner']['tmp_name'])
            ->resize(800, 106)
            ->save('/var/www/volumes/banner/' . $md5); // TODO: Stop hardcoding contenttype bullshit

        // add said uploaded image to cache
        // attach owner to user id
        $cache = $cdn->ConstructCache(ContentType::Banner, $user['id'], $md5, "." . $extension);

        // push to db
        $cdn->AddCache($cache);

        $alert->CreateAlert(Level::Success, "Successfully uploaded your banner.");
    }

    public function ProfilePicture() {
        // TODO: This is okay, but it could be better.
        // TODO: Put all of this in a file uploading handler class, because this is going to get very messy quickly.

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
        $manager = new ImageManager(['driver' => 'imagick']);
        $image = $manager
            ->make($_FILES['asset']['tmp_name'])
            ->resize(500, 500)
            ->save('/var/www/volumes/profile_picture/' . $md5);

        // add said uploaded image to cache
        // attach owner to user id
        $cache = $cdn->ConstructCache(ContentType::ProfilePicture, $user['id'], $md5, "." . $extension);

        // push to db
        $cdn->AddCache($cache);

        $alert->CreateAlert(Level::Success, "Successfully uploaded your profile.");
    }
}