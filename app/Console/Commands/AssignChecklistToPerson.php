<?php

namespace App\Console\Commands;

use App\Models\Card;
use App\Models\CheckList;
use App\Models\User;
use Illuminate\Console\Command;

class AssignChecklistToPerson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-checklist-to-person';

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
            $person = User::where("inner_code_backup", "=", $checklist->inner_code_backup)->first();
            if (!$person) {
                $checklist->update(['user_id' => null]);
            }
            else {
                $checklist->update(['user_id' => $person->id]);
                $card = Card::where('user_id', '=', $person->id)->first();
                if (!$card && $person->inner_code_backup) {
                    Card::create([
                        'user_id' => $person->id,
                        'inner_code' => $person->inner_code_backup,
                        'status' => $person->status
                    ]);
                }
            }
        }
    }
}
