<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:superadmin';

    /**
     * The console command description.
     *
     * @var string
     */ 
    protected $description = 'Met à jour le statut admin du superadmin';

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
        $user = User::where('email', 'superadmin@bakoai.pro')->first();

        if ($user) {
            $user->admin = true;
            $user->save();
            $this->info('Le statut admin du superadmin a été mis à jour.');
        } else {
            $this->error('Superadmin introuvable.');
        }
    }
}
