<?php

include 'src/GameEntry.php';
include 'src/GameList.php';
include 'src/Matcher.php';
include 'src/DuplicateMatcher.php';
include 'src/Emulator.php';
include 'src/Emulators.php';
include 'src/CompareFileNames.php';
include 'src/Helper.php';
include 'src/Log.php';


/**
 * TODO:
 * - vorhandene einträge in der xml checken ob video und image noch da is
 * -  create log (deleted, moved, missed...)
 * - settings auslagern in eine config file
 * - logger in den helper schieben
 * - namespaces verbauen
 */

$path = [

    //store here unwanted / unused stuff
    'unwantedRoms' => '/Users/matthias.friedrich/www/privat/RetroPie-Cleaner/vm/__unused/{system}/',
//    'unwantedRoms' => '/home/pi/RetroPrie/check_roms/{system}/',
    'unusedVideos' => '/Users/matthias.friedrich/www/privat/RetroPie-Cleaner/vm/__unused/{system}/videos/',
    'unusedImages' => '/Users/matthias.friedrich/www/privat/RetroPie-Cleaner/vm/__unused/{system}/images/',
//    'unusedVideos' => '/home/pi/RetroPie/check_roms/{system}/videos/',

    //the folder with our roms (inside this folder we have all emulators like nes,snes,gb,gba...)
    'roms' => '/Users/matthias.friedrich/www/privat/RetroPie-Cleaner/vm/emulators/',
//    'emulators' => '/home/pi/RetroPie/roms/',

    'video' => '/Users/matthias.friedrich/www/privat/RetroPie-Cleaner/vm/emulators/{system}/videos/',
    'image' => '/Users/matthias.friedrich/www/privat/RetroPie-Cleaner/vm/emulators/{system}/images/',
//    'video' => '/home/pi/RetroPie/roms/{system}/videos/',

    'downloads' => '/Users/matthias.friedrich/www/privat/RetroPie-Cleaner/vm//downloaded_images/{system}/',
//    'imagesSystem' => '/opt/retropie/configs/all/emulationstation/downloaded_images/{system}/',

    'gameList' => [
        '/Users/matthias.friedrich/www/privat/RetroPie-Cleaner/vm/emulators/{system}/gamelist.xml',
        '/Users/matthias.friedrich/www/privat/RetroPie-Cleaner/vm/system/{system}/gamelist.xml'

    ]

];

$filters = [
    //we only want at first german source then europe and at least usa
    'keep' => ['germany', 'de', 'europe', 'usa', 'en'],

    //remove japan roms
    'keep-japan' => false
];

$logger = new Log();
$emulators = new Emulators( $path );
Helper::output(sprintf("Process \033[1;32m%s\033[0m Emulators", count($emulators->get())));

/**
 * loop over every emulator
 */
foreach ($emulators->get() as $emulator) {

    Helper::output(sprintf("Emulator \033[1;34m%s\033[0m", $emulator->emulator),1);
    $logger->log[] = 'Process Emulator ' . $emulator->emulator;

    $movedFiles = $emulator->moveSystemDownloads();
    if (count($movedFiles)){
        $logger->movedFiles($movedFiles, 'these are stored inside the download folder');
    }

    if ($emulator->has('gamelist')){
        $gameList = $emulator->getGameList();

        $romsLocation = $emulator->romLocation;

        /**
         * Find duplicated / unwanted roms
         */
        $duplicateMatcher = new DuplicateMatcher( $romsLocation );
        list($removeList, $keepList) = $duplicateMatcher->filter(
            $filters['keep'],
            $filters['keep-japan']
        );

        if (count($removeList)){

            /**
             * Move duplicated / unwanted roms
             */
            Helper::output(sprintf("Move \033[1;32m%s\033[0m unwanted Roms", count($removeList)), 2);
            $moveTo = str_replace('{system}', $emulator->emulator, $path['unwantedRoms']);
            $duplicateMatcher->move($removeList, $moveTo);

            $logger->movedFiles($removeList, 'these are duplicated');

            /**
             * Mark moved roms as deleted (xml)
             */
            Helper::output(sprintf("Mark \033[1;32m%s\033[0m Roms as deleted", count($removeList)), 2);
            $xmlDeletedGames = [];
            foreach ($gameList->get() as $game) {
                foreach ($removeList as $rom){

                    $realPath = str_replace(
                        './',
                        $romsLocation . '/',
                        $game->get('path')
                    );

                    if ($realPath == $romsLocation . '/'. $rom){
                        $xmlDeletedGames[] = $realPath;
                        $game->remove();
                    }
                }
            }

            if (count($xmlDeletedGames)){
                $logger->markRemoved($xmlDeletedGames, 'we deleted the duplicated roms');
            }
        }

        /**
         * Check the XML for old entries (entries with invalid roms, images or videos)
         */
        Helper::output('Looking for corrupted entries', 2);
        $deleted = $gameList->removeCorruptedEntries();

        if (count($deleted['rom'])){
            Helper::output(sprintf("Remove \033[1;32m%s\033[0m entries", count($deleted['rom'])), 2);
            $logger->movedFiles($deleted['rom'], 'these are not available (rom not found)');
        }

        if (count($deleted['image'])){
            Helper::output(sprintf("Remove \033[1;32m%s\033[0m old image relations", count($deleted['image'])), 2);
            $logger->optionRemoved($deleted['image'], 'image', 'the file relation is not valid');
        }

        if (count($deleted['video'])){
            Helper::output(sprintf("Remove \033[1;32m%s\033[0m old video relations", count($deleted['video'])), 2);
            $logger->optionRemoved($deleted['video'], 'video', 'the file relation is not valid');
        }

        /**
         * Looking for games that miss a media mapping (video/image)
         */
        Helper::output('Looking for games that miss a media mapping', 2);
        list($imageResult, $videoResult) = $emulator->mapImagesAndVideos();

        list($imageMapped, $imageNotAvailable, $unusedImages) = $imageResult;
        list($videoMapped, $videoNotAvailable, $unusedVideos) = $videoResult;

        if (count($imageMapped)) Helper::output(sprintf("Successful mapped \033[1;32m%s\033[0m images", count($imageMapped)), 2);
        if (count($videoMapped)) Helper::output(sprintf("Successful mapped \033[1;32m%s\033[0m videos", count($videoMapped)), 2);

        /**
         * Move unused Videos
         */
        if (count($unusedVideos)) {
            Helper::output(sprintf("Move \033[1;32m%s\033[0m unused Videos", count($unusedVideos)), 2);
            $logger->movedFiles($unusedVideos, 'these videos are not in use');

            $moveTo = str_replace('{system}', $emulator->emulator, $path['unusedVideos']);
            @mkdir($moveTo, 0777, true);
            foreach ($unusedVideos as $file){
                rename($file, $moveTo. basename($file));
            }
        }

        /**
         * Move unused Videos
         */
        if (count($unusedImages)) {
            Helper::output(sprintf("Move \033[1;32m%s\033[0m unused Images", count($unusedImages)), 2);
            $logger->movedFiles($unusedImages, 'these images are not in use');

            $moveTo = str_replace('{system}', $emulator->emulator, $path['unusedImages']);
            @mkdir($moveTo, 0777, true);
            foreach ($unusedImages as $file){
                rename($file, $moveTo. basename($file));
            }
        }

        /**
         * Update gamelist.xml
         */
        Helper::output('Update gamelist.xml', 2);
        $gameList->save();
    }else{
        Helper::output("\033[1;31mNo gamelist.xml found, please scrape first this folder, skipped\033[0m",2);
        continue;
    }
}

Helper::output('Done.');
$logger->save();
