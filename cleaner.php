<?php

include 'src/GameEntry.php';
include 'src/GameList.php';
include 'src/VideoMatcher.php';
include 'src/DuplicateMatcher.php';

$options = getopt(false, [
    "move-videos",
    "keep-only-usefull-roms",
    "remove-japan"
]);

if (count($options) === 0){
    die("Usage: cleaner [--move-videos|--keep-only-usefull-roms|--remove-japan]\n");
}

$folder = '.';

if (isset($options['keep-only-usefull-roms'])){
    $dupMatcher = new DuplicateMatcher($folder);
    list($removeList, $keepList) = $dupMatcher->keep(
        ['germany', 'de', 'europe', 'usa'],
        !isset($options['remove-japan'])
    );

    echo "Found " . count($removeList) . " not wanted items, move to ./unwanted-roms\n";

    if (file_exists('gamelist.xml')){
        echo "Remove unwanted game entries from gamelist.xml\n";

        $gameList = new GameList();
        $gameList->load();

        foreach ($gameList->get() as $game) {
            foreach ($removeList as $rom){
                if ($game->get('path') == $folder . '/'. $rom){
                    $game->remove();
                }
            }
        }

        moveUnusedVideos($gameList);

        $gameList->backup();
        $gameList->save();
    }

    @mkdir($folder . '/unwanted-roms');
    foreach ($removeList as $rom){
        rename($folder . '/'. $rom, $folder . '/unwanted-roms/'. $rom);
    }



}

if (isset($options['move-videos'])){

//the GameList Class loads the current gamelist.xml
    $gameList = new GameList();
    $status = $gameList->load();

    if ($status === false){
        die('gamelist.xml not found in this directory');
    }

    moveUnusedVideos($gameList);
}

function moveUnusedVideos(GameList $gameList){

    if (!is_dir('videos')) return;

    $videoMatcher = new VideoMatcher();
    $unusedVideos = $videoMatcher->getUnusedVideos($gameList);

    if (count($unusedVideos)){
        echo "Move " . count($unusedVideos) . " unused videos to ./unused-videos\n";
        @mkdir('unused-videos');
        foreach ($unusedVideos as $video){
            rename($video, str_replace('videos/', 'unused-videos/', $video));
        }
    }
}