<?php
namespace Witter;

use \Twig;
use \Twig\RuntimeLoader\RuntimeLoaderInterface;
use \Rakit\Validation\Validator;

class Configurator {
    public $Configuration;
    public $Twig;

    // TODO: this is abhorrent, refactor?

    function __construct() {
        $this->replicateConfig();
        $this->replicateTwig();
    }

    protected function replicateConfig() {
        $Configuration = (object) [
            "Database" => (object) [
                "DatabaseHost"     => getenv("MYSQL_HOST"),
                "DatabaseName"     => getenv("MYSQL_DATABASE"),
                "DatabaseUsername" => getenv("MYSQL_ROOT_USER"),
                "DatabasePassword" => getenv("MYSQL_ROOT_PASSWORD"),
            ],
        ];

        $this->Configuration = $Configuration;
    }

    protected function replicateTwig() {
        $Loader = new \Twig\Loader\FilesystemLoader('../app/templates/');
        $Twig = new \Twig\Environment($Loader);

        $Filter = new \Twig\TwigFilter('timeago', function ($datetime) {
            $time = time() - strtotime($datetime);
            $units = array (
                31536000 => 'year',
                2592000 => 'month',
                604800 => 'week',
                86400 => 'day',
                3600 => 'hour',
                60 => 'minute',
                1 => 'second'
            );

            foreach ($units as $unit => $val) {
                if ($time < $unit) continue;
                $numberOfUnits = floor($time / $unit);
                return ($val == 'second')? 'a few seconds ago' :
                    (($numberOfUnits>1) ? $numberOfUnits : 'a')
                    .' '.$val.(($numberOfUnits>1) ? 's' : '').' ago';
            }
        });

        $files = glob("images/header/" . '/*.webp');
        $file = array_rand($files);

        $Twig->addFilter($Filter);
        $Twig->addGlobal("HeaderPhoto", "/" . $files[$file]);
        $Twig->addGlobal('Session',         $_SESSION);
        $Twig->addGlobal('Args',            @$_GET);
        $this->Twig = $Twig;
    }
}