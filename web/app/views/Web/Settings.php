<?php
namespace Witter\Views;

use Witter\Models\Level;

class Settings extends View {
    public function View() {
        $userModel = new \Witter\Models\User();
        $settingsModel = new \Witter\Models\Settings();
        $fmModel = new \Witter\Models\LastFM();

        $user = $userModel->GetUser($_SESSION['Handle']);

        $sig = $fmModel->createApiSig([
            'api_key' => getenv("LASTFM_API_KEY"),
            'method' => 'auth.getSession',
            'token' => $user['lastfm_token']
        ], getenv("LASTFM_API_SECRET"));

        print_r($sig);

        echo $fmModel->constructURL([
            'method' => 'auth.getSession',
            'token' => $user['lastfm_token'],
            'api_key' => getenv("LASTFM_API_KEY"), 
            'api_sig' => $sig,
        ]);

        if(isset($_GET['token'])) {
            $settingsModel->UpdateLastFMToken($_GET['token']);
        }

        echo $this->Twig->render('settings.twig', array(
            "PageSettings" => $this->PageSettings("Settings", "Settings page"),
            "User" => $user,
            "ActiveTab" => "general",
        ));
    }
}