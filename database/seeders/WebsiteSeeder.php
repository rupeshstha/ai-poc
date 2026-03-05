<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Store;
use App\Models\Website;
use Exception;
use Illuminate\Database\Seeder;

class WebsiteSeeder extends Seeder
{
    public function run(): void
    {
        try {
            Website::factory(10, function (array $website) {
                $channel = Channel::factory(2, function (array $channel) use ($website) {
                    $store = Store::factory(3)->create([
                        "website_id" => $website["id"],
                        "channel_id" => $channel["id"],
                    ]);
                })->create([
                    "website_id" => $website["id"],
                ]);
                dd($channel);
            })->create();
        } catch (Exception $th) {
            dd($th);
            //throw $th;
        }
    }
}
