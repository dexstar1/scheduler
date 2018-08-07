<?php

use App\Customer;
use Illuminate\Database\Seeder;

class CustomerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Let's truncate our existing records to start from scratch.
        DB::statement("SET foreign_key_checks=0");
        Customer::truncate();
        DB::statement("SET foreign_key_checks=1");

        $faker = \Faker\Factory::create();

        // And now, let's create a few articles in our database:
        // for ($i = 0; $i < 50; $i++) {
        //     Customer::create([
        //         'fullName' => $faker->name,
        //         'email' => $faker->email,
        //     ]);
        // }
    }
}
