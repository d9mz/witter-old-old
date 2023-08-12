<?php
namespace Witter\Models;

enum Level: int {
    case Info = 0;
    case Warning = 1;
    case Error = 2;
    case Fatal = 3;
    case Success = 4;
}

class Alert extends Model {
    
    public function InternalLog(Level $level, string $message = "Message") : void {
        $level = match ($level) {
            Level::Info => "info",
            Level::Warning => "warning",
            Level::Error => "error",
            Level::Fatal => "fatal",
            Level::Success => "success",
        };

        // because we use `diff` code highlighting,
        // - is red and + is green lines
        switch($level) {
            case "error":
            case "fatal":
            case "warning":
                $prefix = "-";
                break;
            case "info":
                $prefix = "";
                break;
            default:
                $prefix = "+";
                break;
        }

        $webhookurl = getenv("WEBHOOK_URL");

        // construct message
        if(isset($_SESSION['Handle'])) {
            /*
                end result vvvv 

                + [witter] (/user/login/)
                + [success] Successfully logged in.
            */

            $message = sprintf(
                "```diff\n+ [@%s] (%s %s)\n%s [%s] %s```",
                $_SESSION['Handle'],
                $_SERVER['REQUEST_METHOD'], 
                $_SERVER['REQUEST_URI'], 
                $prefix, 
                $level, 
                $message
            );
        } else {
            /*
                end result vvvv 

                + [witter] (/user/login/)
                + [success] Successfully logged in.
            */

            $message = sprintf(
                "```diff\n+ [%s] (%s %s)\n%s [%s] %s```",
                "(no session)",
                $_SERVER['REQUEST_METHOD'], 
                $_SERVER['REQUEST_URI'], 
                $prefix, 
                $level, 
                $message
            );
        }

        if($webhookurl != "your_url_here") {
            $json_data = json_encode([
                "content" => $message,
                "username" => "internal.witter",
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

            $ch = curl_init( $webhookurl );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            curl_setopt( $ch, CURLOPT_POST, 1);
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt( $ch, CURLOPT_HEADER, 0);
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec( $ch );
            curl_close( $ch );
        }
    }

    public function CreateAlert(Level $level, string $message = "Message", bool $redirect = true, bool $create_internal_log = true) {
        // should i really be doing this far up in the function?
        if($create_internal_log) $this->InternalLog($level, $message);
        
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