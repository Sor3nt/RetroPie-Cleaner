<?php

include 'src/GameEntry.php';
include 'src/GameList.php';
include 'src/VideoMatcher.php';

//the GameList Class loads the current gamelist.xml
$gameList = new GameList();
$status = $gameList->load();

if ($status === false){
    die('gamelist.xml not found in this directory');
}

$videoMatcher = new VideoMatcher();
$unusedVideos = $videoMatcher->getUnusedVideos($gameList);

if (count($unusedVideos)){
    echo "Move " . count($unusedVideos) . " unused videos to ./unused-videos\n";
    @mkdir('unused-videos');
    foreach ($unusedVideos as $video){
        rename($video, str_replace('videos/', 'unused-videos/', $video));
    }
}