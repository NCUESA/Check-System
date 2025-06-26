<?php

namespace App\Console\Commands;

use App\Models\CheckList;
use Illuminate\Console\Command;

class BackupChecklistInnerCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-checklist-inner-code';

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
        $checklists = CheckList::all();
        foreach ($checklists as $checklist) {
            $checklist->update(['inner_code_backup' => $checklist->inner_code]);
        }
    }
}
