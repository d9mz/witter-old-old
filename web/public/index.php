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

// jacksden.com

// witter.jacksden.com

$Router->Get('/cdn/css/{md5}', "\Witter\Models\CDN@GetCSS");
$Router->Get('/cdn/{md5}', "\Witter\Models\CDN@GetFile");

if(!isset($_SESSION['Handle'])) {
    $Router->Get('/', "\Witter\Views\Homepage@View");
    $Router->Post('/user/login', "\Witter\Models\User@SignIn");
    $Router->Post('/user/register', "\Witter\Models\User@Register");
} else {
    $Router->Get('/', "\Witter\Views\Homepage@Redirect");

    // [WEB] [API] Settings
    $Router->Get('/settings', "\Witter\Views\Settings@View");
    $Router->Post('/settings/description', "\Witter\Models\Settings@Description");
    $Router->Post('/settings/css', "\Witter\Models\Settings@CSS");
    $Router->Post('/settings/nickname', "\Witter\Models\Settings@nickname");
    $Router->Post('/settings/picture/profile', "\Witter\Models\Settings@ProfilePicture");
    $Router->Post('/settings/picture/banner', "\Witter\Models\Settings@Banner");

    // [WEB] Profiles
    $Router->Get('/likes/{user}', "\Witter\Views\User@Likes");
    $Router->Get('/user/followers/{user}', "\Witter\Views\User@Followers");
    $Router->Get('/user/following/{user}', "\Witter\Views\User@Following");
    $Router->Get('/user/{user}/{id}', "\Witter\Views\Feed@ViewWeet");
    $Router->Get('/user/{user}', "\Witter\Views\User@View");

    // [WEB] Feed
    $Router->Get('/feed', "\Witter\Views\Feed@View");
    $Router->Get('/feed/following', "\Witter\Views\Feed@Following");
    $Router->Get('/feed/trending', "\Witter\Views\Feed@Trending");
    $Router->Post('/feed', "\Witter\Models\Feed@NewPost"); 

    // [API] Actions for replies
    $Router->Post('/actions/reply/{id}/reply', "\Witter\Models\Feed@ReplyToReply");
    $Router->Post('/actions/reply/{id}/like', "\Witter\Models\Feed@LikeReply");

    // [API] Actions for posts
    $Router->Post('/actions/post/{id}/like', "\Witter\Models\Feed@LikePost");
    $Router->Post('/actions/post/{id}/reply', "\Witter\Models\Feed@Reply");
    $Router->Post('/actions/post/{id}/delete', "\Witter\Models\Feed@LikePost");


    // [API] Actions for user
    $Router->Post('/actions/user/{id}/follow', "\Witter\Models\User@Follow");
    $Router->Post('/actions/user/{id}/block', "\Witter\Models\User@Block");

    // todo: create tos / privacy under /info/
}

$Router->Set404(function() {
    // this shit is gay
    header("HTTP/1.0 404 Not Found");

    $page = new \Witter\Views\Error;
    $page->View();
});

$Router->run();