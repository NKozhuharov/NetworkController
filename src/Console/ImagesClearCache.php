<?php

namespace Nevestul4o\NetworkController\Console;

use Illuminate\Console\Command;

class ImagesClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network-controller:images-clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes all resized images';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $resizedImagesPath = config('networkcontroller.images.resized_path');
        if (empty($resizedImagesPath)) {
            $this->error('To use the images controller, set IMAGES_RESIZED_PATH configuration variable in the .env file to a non-empty string!');
            return;
        }

        $resizedImagesPath = base_path().'/../'.$resizedImagesPath;
        foreach (scandir($resizedImagesPath) as $directory) {
            if ($directory === '.' || $directory === '..') {
                continue;
            }
            foreach (scandir($resizedImagesPath.'/'.$directory) as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                unlink($resizedImagesPath.'/'.$directory.'/'.$file);
            }
            rmdir($resizedImagesPath.'/'.$directory);
        }
        $this->info('Image cache cleaned successfully');
    }
}
