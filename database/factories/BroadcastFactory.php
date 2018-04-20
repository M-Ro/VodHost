<?php

use Faker\Generator as Faker;

$factory->define(App\Broadcast::class, function (Faker $faker) {
    return [
        'id' => App\Broadcast::generateID(),
        'user_id' => $faker->randomNumber(3, false),
        'title' => substr($faker->sentence(2), 0, -1),
        'description' => $faker->paragraph,
        'state' => 'processed',
        'filename' => $faker->word . '.mp4',
        'filesize' => $faker->randomNumber(9, false),
        'length' => $faker->randomFloat(),
        'views' => $faker->randomNumber(4, false),
        'public' => $faker->boolean
    ];
});
