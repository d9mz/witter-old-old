<?php
session_start();

require("../app/lib/Configuration.php");
require("../app/lib/Base.php");
require("../app/views/Include.php");
require("../app/models/Include.php");
require("../app/vendor/autoload.php");

$Configurator   = new Witter\Configurator();
$Base           = new Witter\Base();
$Router         = new \Bramus\Router\Router();

/*
 * GUIDELINE: Tag categories of endpoints with these following tags:
 *
 * [CLIENT]:   Client related endpoint
 * [AUTH]:     Authentication required endpoint
 * [API]:      POST requests
 * [WEB]:      Stuff like trades, games pages, etc.
 * [MISC]:     Anything that doesn't fall under these categories
 * [OBSOLETE]: Obsolete. Remove soon.
 */

if(!isset($_SESSION['Handle'])) {
    $Router->Get('/', "\Witter\Views\Homepage@View");
    $Router->Get('/user/{user}', "\Witter\Views\User@View");
    $Router->Post('/user/login', "\Witter\Models\User@SignIn");
    $Router->Post('/user/register', "\Witter\Models\User@Register");
} else {
    $Router->Get('/', "\Witter\Views\Homepage@Redirect");
    $Router->Get('/feed', "\Witter\Views\Feed@View"); // FINISH UP...
    $Router->Post('/feed', "\Witter\Models\Feed@NewPost"); // IMPLEMENT tomorrow
    $Router->Get('/user/{user}', "\Witter\Views\User@View"); // FINISH UP... (cdn)
}

$Router->Set404(function() {
    header("HTTP/1.0 404 Not Found");
});

$Router->run();