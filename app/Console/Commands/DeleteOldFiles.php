<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DeleteOldFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-all-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete files older than 30 days from files_assign directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $directory = 'public/files_assign';
        $files = Storage::files($directory);

        foreach ($files as $file) {
            Storage::delete($file);
            $this->info("Deleted: $file");
        }

        $this->info("All files deletion process completed.");
    }
}
