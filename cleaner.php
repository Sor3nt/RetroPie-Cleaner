<?php

include 'src/GameEntry.php';
include 'src/GameList.php';
include 'src/Matcher.php';
include 'src/DuplicateMatcher.php';
include 'src/Emulator.php';
include 'src/CompareFileNames.php';
include 'src/Helper.php';
include 'src/Log.php';
include 'src/System.php';


/**
 * TODO:
 * - settings auslagern in eine config file
 * - logger in den helper schieben
 * - namespaces verbauen
 */

$test = false;
$onlyEmulator = false;
$duplicateCheck = true;
$invalidEntryFix = true;
$autoMap = true;
$autoMove = true;
$keepJapan = false;
//we only want at first german source then europe and at least usa
$keepCountry = ['germany', 'de', 'europe', 'usa', 'en'];

array_shift($argv);

if (count($argv)){

    foreach ($argv as $arg) {

        $command = $arg;
        $param = false;

        if (strpos($arg, '=') !== false){
            $command = explode('=', $arg)[0];
            $param = explode('=', $arg)[1];
        }

        switch (strtolower($command)){

            case '--test':
                Helper::output(sprintf('Test mode is enable, use %s as test folder', getcwd() . '/vm'));
                $test = true;
                break;

            case '--only-emulator':
                $delimiter = ',';
                if (strpos($param, ';') !== false) $delimiter = ';';
                $onlyEmulator = explode($delimiter, $param);
                break;

            case '--keep-country':
                $delimiter = ',';
                if (strpos($param, ';') !== false) $delimiter = ';';
                $keepCountry = explode($delimiter, $param);
                break;

            case '--no-duplicate-check':
                $duplicateCheck = false;
                break;

            case '--no-invalid-entry-fix':
                $invalidEntryFix = false;
                break;

            case '--no-auto-map':
                $autoMap = false;
                break;

            case '--no-auto-move':
                $autoMove = false;
                break;

            case '--keep-japan':
                $keepJapan = true;
                break;

            case '--help':
            case '-h':
            case '-?':
            default:
                echo "Retropie - Cleaner - Created by Sor3nt 2017\n";
                echo str_repeat('¯', 48) . "\n";
                echo "=> Usage: cleaner [--option[=arguments]]\n";
                echo str_repeat('¯', 48) . "\n";
                echo "Options: \n";
                echo "\t--test\t\t\tEnable the test mode\n";
                echo "\t--only-emulator=snes\tComma seperated platform list (Default: autodetect)\n";
                echo "\t--no-duplicate-check\tIgnore duplicated roms\n";
                echo "\t--no-invalid-entry-fix\tIgnore corrupt roms and medias\n";
                echo "\t--no-auto-map\t\tDo not try to map media to roms\n";
                echo "\t--no-auto-move\t\tDo not move unused files\n";
                echo "\t--keep-japan\t\tPrevent moving japan roms (Default: false)\n";
                echo "\t--keep-country\t\tKeep one of the given names (Default: germany,de,europe,usa,en)\n";
                echo "\n";
                echo "Sample: cleaner --only-emulator=snes,nes,n64\n";
                echo "Sample: cleaner --keep-country=europe,usa\n\n";
                exit;

        }

    }

}

$prepend = $test ? 'vm' : '';

$path = [

    'system' => [
        $prepend . '/home/pi/.emulationstation/es_systems.cfg',
        $prepend . '/etc/emulationstation/es_systems.cfg'
    ],

    'unused' => $prepend . '/home/pi/RetroPie/check_roms/',

    'downloads' => $prepend . '/opt/retropie/configs/all/emulationstation/downloaded_images/',

    'gameList' => [
        $prepend . '/home/pi/RetroPie/roms/',
        $prepend . '/opt/retropie/configs/all/emulationstation/gamelists/'
    ]

];


$filters = [
    'keep' => $keepCountry,

    //remove japan roms
    'keep-japan' => $keepJapan
];

$logger = new Log();
$system = new System($path);

if (is_array($onlyEmulator)){
    Helper::output(sprintf("Process \033[1;32m%s\033[0m Emulators", count($onlyEmulator)));
}else{
    Helper::output(sprintf("Process \033[1;32m%s\033[0m Emulators", count($system->get())));

}

/**
 * loop over every emulator
 */
foreach ($system->get() as $emulator) {

    if (is_array($onlyEmulator) && in_array($emulator->platform, $onlyEmulator) === false) continue;

    if (
        is_array($emulator->platform) ||
        $emulator->platform == 'psx' ||
        $emulator->platform == 'pc' ||
        $emulator->platform == 'scummvm' ||
        $emulator->platform == 'port'
    ){
        Helper::output(sprintf("Skip \033[1;34m%s\033[0m", $emulator->platform),1);
        continue;
    }

    Helper::output(sprintf("Emulator \033[1;34m%s\033[0m", $emulator->platform),1);
    $logger->log[] = 'Process Emulator ' . $emulator->platform;


    if ($emulator->has('gamelist')){
        $gameList = $emulator->getGameList();

        $movedFiles = $emulator->moveSystemDownloads($gameList);

        if (count($movedFiles)){
            $logger->movedFiles($movedFiles, 'these are stored inside the download folder');
        }

        if ($duplicateCheck){

            /**
             * Find duplicated / unwanted roms
             */
            $duplicateMatcher = new DuplicateMatcher( $emulator );
            list($removeList, $keepList) = $duplicateMatcher->filter(
                $filters['keep'],
                $filters['keep-japan']
            );

            if (count($removeList)){
                /**
                 * Move duplicated / unwanted roms
                 */
                Helper::output(sprintf("Move \033[1;32m%s\033[0m unwanted Roms", count($removeList)), 2);
                $duplicateMatcher->move($removeList, $path['unused'] . $emulator->platform . '/');

                $logger->movedFiles($removeList, 'these are duplicated');

                /**
                 * Mark moved roms as deleted (xml)
                 */
                $xmlDeletedGames = [];
                foreach ($gameList->get() as $game) {
                    foreach ($removeList as $rom){

                        $realPath = str_replace(
                            './',
                            $emulator->romLocation . '/',
                            $game->get('path')
                        );

                        if ($realPath == $emulator->romLocation . '/'. $rom){
                            $xmlDeletedGames[] = $realPath;
                            $game->remove();
                        }
                    }
                }

                if (count($xmlDeletedGames)){
                    $logger->markRemoved($xmlDeletedGames, 'we deleted the duplicated roms');
                }
            }
        }

        if ($invalidEntryFix){

            /**
             * Check the XML for old entries (entries with invalid roms, images or videos)
             */
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
        }

        if ($autoMap){

            /**
             * Looking for games that miss a media mapping (video/image)
             */
            list($imageResult, $videoResult) = $emulator->mapImagesAndVideos();

            list($imageMapped, $imageNotAvailable, $unusedImages) = $imageResult;
            list($videoMapped, $videoNotAvailable, $unusedVideos) = $videoResult;

            if (count($imageMapped)) Helper::output(sprintf("Successful mapped \033[1;32m%s\033[0m images", count($imageMapped)), 2);
            if (count($videoMapped)) Helper::output(sprintf("Successful mapped \033[1;32m%s\033[0m videos", count($videoMapped)), 2);

            if ($autoMove){

                /**
                 * Move unused Videos
                 */
                if (count($unusedVideos)) {
                    Helper::output(sprintf("Move \033[1;32m%s\033[0m unused Videos", count($unusedVideos)), 2);
                    $logger->movedFiles($unusedVideos, 'these videos are not in use');

                    //TODO : das hier verschieben in die klass rein...
                    $moveTo = $path['unused'] . $emulator->platform . '/videos/';
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

                    //TODO : das hier verschieben in die klass rein...
                    $moveTo = $path['unused'] . $emulator->platform . '/images/';
                    @mkdir($moveTo, 0777, true);
                    foreach ($unusedImages as $file){
                        rename($file, $moveTo. basename($file));
                    }
                }
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
