<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Watchlist;

class WatchlistTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // user_id 1
        Watchlist::create([ 
            'id' => 1,
            'mack' => 'AAA',
            'user_id' => 1,
            'my_watchlist_id' => 1,
        ]);
        Watchlist::create([ 
            'id' => 2,
            'mack' => 'BBB',
            'user_id' => 1,
            'my_watchlist_id' => 1,
        ]);
        Watchlist::create([ 
            'id' => 3,
            'mack' => 'CCC',
            'user_id' => 1,
            'my_watchlist_id' => 1,
        ]);
        Watchlist::create([ 
            'id' => 4,
            'mack' => 'DDD',
            'user_id' => 1,
            'my_watchlist_id' => 1,
        ]);
        Watchlist::create([ 
            'id' => 5,
            'mack' => 'EEE',
            'user_id' => 1,
            'my_watchlist_id' => 1,
        ]);
        Watchlist::create([ 
            'id' => 6,
            'mack' => 'FFF',
            'user_id' => 1,
            'my_watchlist_id' => 1,
        ]);
        Watchlist::create([ 
            'id' => 7,
            'mack' => 'GGG',
            'user_id' => 1,
            'my_watchlist_id' => 1,
        ]);
        Watchlist::create([ 
            'id' => 8,
            'mack' => 'HHH',
            'user_id' => 1,
            'my_watchlist_id' => 2,
        ]);
        Watchlist::create([ 
            'id' => 9,
            'mack' => 'III',
            'user_id' => 1,
            'my_watchlist_id' => 2,
        ]);
        Watchlist::create([ 
            'id' => 10,
            'mack' => 'KKK',
            'user_id' => 1,
            'my_watchlist_id' => 2,
        ]);
        Watchlist::create([ 
            'id' => 11,
            'mack' => 'LLL',
            'user_id' => 1,
            'my_watchlist_id' => 2,
        ]);
        Watchlist::create([ 
            'id' => 12,
            'mack' => 'MMM',
            'user_id' => 1,
            'my_watchlist_id' => 2,
        ]);
        Watchlist::create([ 
            'id' => 13,
            'mack' => 'NNN',
            'user_id' => 1,
            'my_watchlist_id' => 2,
        ]);

        // user_id 2
        Watchlist::create([ 
            'id' => 14,
            'mack' => 'AAA',
            'user_id' => 2,
            'my_watchlist_id' => 3,
        ]);
        Watchlist::create([ 
            'id' => 15,
            'mack' => 'BBB',
            'user_id' => 2,
            'my_watchlist_id' => 3,
        ]);
        Watchlist::create([ 
            'id' => 16,
            'mack' => 'DDD',
            'user_id' => 2,
            'my_watchlist_id' => 3,
        ]);
        Watchlist::create([ 
            'id' => 17,
            'mack' => 'EEE',
            'user_id' => 2,
            'my_watchlist_id' => 3,
        ]);
        Watchlist::create([ 
            'id' => 18,
            'mack' => 'FFF',
            'user_id' => 2,
            'my_watchlist_id' => 3,
        ]);
        Watchlist::create([ 
            'id' => 19,
            'mack' => 'GGG',
            'user_id' => 2,
            'my_watchlist_id' => 3,
        ]);
        Watchlist::create([ 
            'id' => 20,
            'mack' => 'HHH',
            'user_id' => 2,
            'my_watchlist_id' => 4,
        ]);
        Watchlist::create([ 
            'id' => 21,
            'mack' => 'III',
            'user_id' => 2,
            'my_watchlist_id' => 4,
        ]);
        Watchlist::create([ 
            'id' => 22,
            'mack' => 'KKK',
            'user_id' => 2,
            'my_watchlist_id' => 4,
        ]);
        Watchlist::create([ 
            'id' => 23,
            'mack' => 'LLL',
            'user_id' => 2,
            'my_watchlist_id' => 4,
        ]);
        Watchlist::create([ 
            'id' => 24,
            'mack' => 'MMM',
            'user_id' => 2,
            'my_watchlist_id' => 4,
        ]);
        Watchlist::create([ 
            'id' => 25,
            'mack' => 'NNN',
            'user_id' => 2,
            'my_watchlist_id' => 4,
        ]);
       
    }
}
