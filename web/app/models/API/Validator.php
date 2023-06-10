<?php
namespace Witter\Models;

class Validator extends Model {
    public function validate($request, array $fields)
    {
        if(!isset($request)) {
            echo 'Request is not being set';
            exit;
        }

        if(empty($fields)) {
            echo 'Provide fields to be validated';
            exit;
        }

        foreach($fields as $key => $value) {
            $rules = explode('|', $value);

            $mapRules = array_map(function($rule) use($request, $key) {

                if(str_contains($rule, ':')) {
                    list($k, $v) = explode(':', $rule);
                } else {
                    list($k, $v) = [$rule, $key];
                }

                return match($k) {
                    'string' => is_string($request[$key]),
                    'min' => $v <= strlen($request[$key]) ? 'true' : 'false',
                    'max' => $v >= strlen($request[$key]) ? 'true' : 'value'
                };
            }, $rules);

            dd($mapRules);
        }

        return $fields;
    }
}