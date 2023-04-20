<?php

/*
    View Autoloader
    Fetches all Views from /Views/
*/

$Views = preg_grep('/^([^.])/', scandir('../app/views/Web/'));
include_once('../app/views/Web/Base.php');
foreach($Views as $View) {
    include_once('../app/views/Web/' . $View);
}
