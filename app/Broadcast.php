<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    /**
    * Generates a 10 character random alphanumeric string.
    *
    * @return string $str - generated string
    */
    public static function generateID()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $str = '';
        for ($i = 0; $i < 10; $i++) {
            $str .= $characters[rand(0, $charactersLength - 1)];
        }
        return $str;
    }
}
