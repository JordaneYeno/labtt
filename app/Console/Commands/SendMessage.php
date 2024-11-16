<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationController;
use App\Models\Abonnement;
use Illuminate\Console\Command;

class SendMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'envoyer les messages qui sont dans la table notification';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // $controller = new NotificationController();
        // $controller->test();
        Abonnement::where('user_id',2)->increment('solde', 21111111);
        $this->info('The happy birthday messages were sent successfully!');
    }
}
