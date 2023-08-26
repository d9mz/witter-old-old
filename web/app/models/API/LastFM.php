<?php
namespace Witter\Models;

use Witter\Models\Level;
use Witter\Models\Type;

class LastFM extends Model {
    public function createApiSig(array $params, string $secret) : string {
        // Step 1: Sort parameters alphabetically
        ksort($params);

        // Step 2: Concatenate parameters
        $concatenatedString = '';
        foreach($params as $key => $value) {
            // Step 3: Ensure parameters are utf8 encoded
            $key = utf8_encode($key);
            $value = utf8_encode($value);
            
            $concatenatedString .= $key . $value;
        }

        // Step 4: Append secret
        $concatenatedString .= $secret;

        // Step 5: Return md5 hash
        return md5($concatenatedString);

        /*
            Why the fuck is the API so complicated?? 
            $params = [
                'api_key' => 'xxxxxxxx',
                'method' => 'auth.getSession',
                'token' => 'xxxxxxx'
            ];
            $secret = 'mysecret';

            $apiSignature = createApiSignature($params, $secret);
        */
    }
}