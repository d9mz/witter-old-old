<?php
namespace Witter\Models;

use Intervention\Image\ImageManager;

class Settings extends Model
{
    // create database instance -- this shouldn't really be done here but it's
    // a temporary solution to the infinite connection issue
    public function __construct() {
        $connection = new \Witter\Models\Connection();
        $this->Connection = $connection->MakeConnection();
    }
    
    public function Description() {
        $alert  = new \Witter\Models\Alert();
        $user   = new \Witter\Models\User();
        $user   = $user->GetUser($_SESSION['Handle']);

        if (!isset($_POST['description']) || empty(trim($_POST['description']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a description.");
        }

        if (strlen($_POST['description']) < 4 || strlen($_POST['description']) > 200) {
            $alert->CreateAlert(Level::Error, "Your description must be longer than 3 characters and not longer than 200.");
        }

        // hacky shitty fix nbecasue my dumbass forgot to accoutn for this
        $_POST['description'] = trim($_POST['description']);

        $stmt = $this->Connection->prepare("UPDATE users SET description = ? WHERE id = ?");
        $stmt->execute([
            $_POST['description'],
            $user['id'],
        ]);
        $stmt = null;

        $alert->CreateAlert(Level::Success, "Successfully set your description.");
    }

    public function Location() { 
        $alert  = new \Witter\Models\Alert();
        $user   = new \Witter\Models\User();
        $util   = new \Witter\Models\Utility();
        $user   = $user->GetUser($_SESSION['Handle']);

        if (!$util->isValidCountry($_POST['country'])) {
            $alert->CreateAlert(Level::Error, "Invalid country");
        }

        $stmt = $this->Connection->prepare("UPDATE users SET country = ? WHERE id = ?");
        $stmt->execute([
            $_POST['country'],
            $user['id'],
        ]);
        $stmt = null;

        $alert->CreateAlert(Level::Success, "Successfully set your location.");
    }
    public function UpdateLastFMToken(string $token) : void {
        $alertsModel = new \Witter\Models\Alert();
        $user   = new \Witter\Models\User();
        $fmModel = new \Witter\Models\LastFM();
        $user   = $user->GetUser($_SESSION['Handle']);

        $stmt = $this->Connection->prepare("UPDATE users SET lastfm_token = ? WHERE id = ?");
        $stmt->execute([
            $token,
            $user['id'],
        ]);
        $stmt = null;

        $sig = $fmModel->createApiSig([
            'api_key' => getenv("LASTFM_API_KEY"),
            'method' => 'auth.getSession',
            'token' => $token,
        ], getenv("LASTFM_API_SECRET"));

        $url = $fmModel->constructURL([
            'method' => 'auth.getSession',
            'token' => $token,
            'api_key' => getenv("LASTFM_API_KEY"), 
            'api_sig' => $sig,
            'format' => 'json',
        ]);

        // im gonna kill myself

        $max_attempts = 5;
        $attempt = 0;
        $success = false;
        $response = null;
        
        while ($attempt < $max_attempts && !$success) {
            $ch = curl_init(urldecode($url));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Witter/1.0');
            
            $response = curl_exec($ch);
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // echo $httpCode;

            if ($httpCode == 200) { // HTTP OK
                $success = true;
            } else {
                $attempt++;
                sleep(1); 
            }
            
            curl_close($ch);
        }        
        
        if ($success) {
            $session = json_decode($response);
        } else {
            $alertsModel->CreateAlert(Level::Error, "We could not link your Last.FM to your profile.", false, true);
            header("Location: /settings/");
        }        

        $stmt = $this->Connection->prepare("UPDATE users SET lastfm_session = ?, lastfm_username = ? WHERE id = ?");
        $stmt->execute([
            $session->session->key,
            $session->session->name,
            $user['id'],
        ]);
        $stmt = null;

        $alertsModel->CreateAlert(Level::Success, "Successfully linked your Last.FM account to your profile!", false, true);
        header("Location: /settings/");
    }

    public function Unlink() {
        if(isset($_SESSION['Handle'])) {
            $user   = new \Witter\Models\User();
            $user   = $user->GetUser($_SESSION['Handle']);
            
            $stmt = $this->Connection->prepare("UPDATE users SET lastfm_username = '', lastfm_token = '', lastfm_session = '' WHERE id = ?");
            $stmt->execute([
                $user['id'],
            ]);
            $stmt = null;
        }   
    }

    public function CSS() {
        $alert  = new \Witter\Models\Alert();
        $user   = new \Witter\Models\User();
        $user   = $user->GetUser($_SESSION['Handle']);

        if (strlen(@$_POST['css']) > 7000) {
            $alert->CreateAlert(Level::Error, "Your CSS cannot be longer than 7000 characters.");
        }

        $stmt = $this->Connection->prepare("UPDATE users SET css = ?, moderated_css = 'f' WHERE id = ?");
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

        if (!isset($_POST['nickname']) || empty(trim($_POST['nickname']))) {
            $alert->CreateAlert(Level::Error, "You did not enter a display name.");
        }

        if (strlen($_POST['nickname']) < 4 || strlen($_POST['nickname']) > 20) {
            $alert->CreateAlert(Level::Error, "Your display name must be longer than 3 characters and not longer than 20.");
        }

        // hacky shitty fix nbecasue my dumbass forgot to accoutn for this
        $_POST['nickname'] = trim($_POST['nickname']);

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
        if($_FILES['banner']['size'] > 10000000) {
            $alert->CreateAlert(Level::Error, "Your upload cannot be larger than 10mb.");
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
        if($_FILES['asset']['size'] > 5000000) {
            $alert->CreateAlert(Level::Error, "Your upload cannot be larger than 5mb.");
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

    public function HideCSS() {
        $alert  = new \Witter\Models\Alert();

        if(isset($_POST['hide_css'])) {
            $stmt = $this->Connection->prepare("UPDATE users SET hide_css = 't' WHERE username = ?");
            $stmt->execute([
                $_SESSION['Handle'],
            ]);
            $stmt = null;
        } else {
            $stmt = $this->Connection->prepare("UPDATE users SET hide_css = 'f' WHERE username = ?");
            $stmt->execute([
                $_SESSION['Handle'],
            ]);
            $stmt = null;
        }

        $alert->CreateAlert(Level::Success, "Successfully updated your CSS preferences.");
    }

    public function Private() {
        $alert  = new \Witter\Models\Alert();

        if(isset($_POST['private'])) {
            $stmt = $this->Connection->prepare("UPDATE users SET private = 't' WHERE username = ?");
            $stmt->execute([
                $_SESSION['Handle'],
            ]);
            $stmt = null;
        } else {
            $stmt = $this->Connection->prepare("UPDATE users SET private = 'f' WHERE username = ?");
            $stmt->execute([
                $_SESSION['Handle'],
            ]);
            $stmt = null;
        }

        $alert->CreateAlert(Level::Success, "Successfully updated your privacy settings.");
    }
}