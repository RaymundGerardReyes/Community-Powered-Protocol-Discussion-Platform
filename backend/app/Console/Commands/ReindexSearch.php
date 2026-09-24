<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:reindex-search')]
#[Description('Command description')]
class ReindexSearch extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
