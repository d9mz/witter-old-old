<?php

/*
    View Autoloader
    Fetches all Views from /Views/
*/

$Models = preg_grep('/^([^.])/', scandir('../app/models/API/'));
include_once('../app/models/API/Base.php');
foreach($Models as $Model) {
    include_once('../app/models/API/' . $Model);
}
