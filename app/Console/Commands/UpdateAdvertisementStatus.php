<?php

namespace App\Console\Commands;

use App\Models\Advertisement;
use Illuminate\Console\Command;

class UpdateAdvertisementStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = now();
        Advertisement::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->update(['status' => 'active']);
    
        Advertisement::where('end_date', '<', $now)
            ->update(['status' => 'inactive']);
    }
    
}
