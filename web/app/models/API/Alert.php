<?php
namespace Witter\Models;

enum Level: int {
    case Info = 0;
    case Warning = 1;
    case Error = 2;
    case Fatal = 3;
    case Success = 4;
}

class Alert extends ModelBase {
    public function Log(Level $level, $message = "Message") {
        echo "not implemented";
    }

    public function CreateAlert(Level $level, $message = "Message", $redirect = true) {
        // $this->Log($level, $message);

        $level = match ($level) {
            Level::Info => "info",
            Level::Warning => "warning",
            Level::Error => "error",
            Level::Fatal => "fatal",
            Level::Success => "success",
        };

        $_SESSION['Alert'][] = [
            "Message" => $message,
            "Type" => $level,
        ];

        if($redirect) {
            if(isset($_SERVER["HTTP_REFERER"])) {
                header("Location: " . $_SERVER["HTTP_REFERER"]);
            } else {
                header("Location: /");
            }
            die();
        }
    }
}