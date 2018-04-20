<?php

use Illuminate\Database\Seeder;

class BroadcastsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Broadcast::class, 15)->create();
    }
}
