<?php
namespace Witter\Views;

use Witter\Models\Level;
use Witter\Models\Type;

class Test extends View {
    public function User() {
        // testing functionality

        $userModel = new \Witter\Models\User();
        $userA = $userModel->getUser(7, Type::ID);
        $userB = $userModel->getUser(6, Type::ID);

        // ugly? 
        // - yes, very
        // why nl2br?
        // - because JSON_PRETTY_PRINT uses newlines 
        //   instead of making it html breaklines

        echo "<h2>userA</h2><code>" . nl2br(json_encode($userA, JSON_PRETTY_PRINT)) . "</code>";
        echo "<h2>userB</h2><code>" . nl2br(json_encode($userB, JSON_PRETTY_PRINT)) . "</code>";
    }
}