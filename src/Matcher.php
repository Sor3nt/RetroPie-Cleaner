<?php

class Matcher{

    var $files = [];
    var $folder;
    /** @var CompareFileNames  */
    var $compare;

    public function __construct( $folder) {
        $this->folder = $folder;

        $files = [];
        if (is_dir($folder)){
            $files = array_slice(scandir($folder), 2);

            foreach ($files as $file) {
                if (is_dir($folder . $file)) continue;
                $this->files[] = $file;
            }
        }

        $this->compare = new CompareFileNames($files);
    }


    public function mapVideos( GameList $gameList ){
        $mapped = [];
        $notAvailable = [];
        foreach ($gameList->get() as $game) {

            if ($game->get('video') === false){
                $video = $this->compare->find($game);
                if ($video){
                    $mapped[] = $game;
                    $video = str_replace('&', '&amp;', $video);
                    $game->set('video', realpath($this->folder . '/' . $video));
                }else{
                    $notAvailable[] = $game;
                }
            }
        }

        return [$mapped, $notAvailable];
    }

    public function mapImages( GameList $gameList ){
        $mapped = [];
        $notAvailable = [];
        foreach ($gameList->get() as $game) {

            if ($game->get('image') === false){
                $video = $this->compare->find($game);
                if ($video){
                    $mapped[] = $game;
                    $video = str_replace('&', '&amp;', $video);

                    $game->set('image', realpath($this->folder . '/' . $video));
                }else{
                    $notAvailable[] = $game;
                }
            }
        }

        return [$mapped, $notAvailable];
    }

    public function getUnused( GameList $gameList, $what){
        $notInUse = [];

        foreach ($this->files as $file) {

            $found = false;

            foreach ($gameList->get() as $game) {

                if ($game->removed) continue;

                if (
                    $game->get($what) !== false &&
                    basename($game->get($what) ) === $file
                ){
                    $found = true;
                    break;
                }
            }

            if ($file !== '..' && $found === false){
                $notInUse[] = $this->folder . $file;
            }
        }

        return $notInUse;
    }

}