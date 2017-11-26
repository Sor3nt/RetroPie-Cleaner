<?php

class VideoMatcher{

    var $videos = [];
    var $folder;

    public function __construct( $folder = 'videos') {
        $this->folder = $folder;

        $videos = scandir($folder);
        unset($videos[0]);
        unset($videos[1]);

        $this->videos = $videos;
    }

    public function getUnusedVideos( GameList $gameList ){
        $notInUse = [];

        foreach ($this->videos as $video) {

            $found = false;

            foreach ($gameList->get() as $game) {

                if (
                    $game->get('video') !== false &&
                    basename($game->get('video') ) === $video
                ){
                    $found = true;
                    break;
                }
            }

            if ($video !== '..' && $found === false){
                $notInUse[] = $this->folder. '/' . $video;
            }
        }

        return $notInUse;
    }

}