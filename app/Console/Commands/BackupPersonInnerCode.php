<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BackupPersonInnerCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-person-inner-code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $people = User::all();
        foreach ($people as $key => $person) {
            $person->update(['inner_code_backup' => $person->inner_code]);
        }
    }
}
