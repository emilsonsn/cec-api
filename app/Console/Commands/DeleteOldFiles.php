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
    protected $signature = 'app:delete-old-files';

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
        
        $now = Carbon::now();

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp(Storage::lastModified($file));

            if ($lastModified->diffInDays($now) >= 30) {
                Storage::delete($file);
                $this->info("Deleted: $file");
            }
        }

        $this->info("Old files deletion process completed.");
    }
}
