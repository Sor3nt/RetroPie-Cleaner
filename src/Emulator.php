<?php

class Emulator {

    var $emulator = 'nes';
    var $romLocation = null;
    var $paths = null;

    private $gameListLocation = null;

    /** @var GameList|bool */
    private $gameList = false;

    public function __construct( $emulator, $paths ) {
        $this->paths = $paths;
        $this->emulator = $emulator;
        $this->romLocation = $paths['roms']  . $this->emulator . '/';

        foreach ($paths['gameList'] as $gameListLocation) {
            $location = str_replace('{system}', $emulator, $gameListLocation);

            if (file_exists($location)){
                $this->gameListLocation = $location;
                break;
            }
        }

        if (!is_null($this->gameListLocation)){
            $this->gameList = new GameList( $this->gameListLocation, $this->emulator, $paths );
        }
    }

    /**
     * @return bool|GameList
     */
    public function getGameList(){
        return $this->gameList === false ? false : $this->gameList;
    }

    public function mapImagesAndVideos(){
        list($videoMapped, $videoNotAvailable, $unusedVideos) = $this->map('video');
        list($imageMapped, $imageNotAvailable, $unusedImages) = $this->map('image');

        list($recVideoMapped) = $this->mapUnused('video');
        list($recImageMapped) = $this->mapUnused('image');

        array_merge($videoMapped, $recVideoMapped);
        array_merge($imageMapped, $recImageMapped);


        return [
            [$imageMapped, $imageNotAvailable, $unusedImages],
            [$videoMapped, $videoNotAvailable, $unusedVideos]
        ];
    }

    public function mapUnused( $what ){
        $matcher = new Matcher(
            str_replace(
                '{system}',
                $this->emulator,
                $this->paths[ $what == 'image' ? 'unusedImages' : 'unusedVideos' ]
            )
        );

        list($mapped, $notAvailable) = $what == 'image' ? $matcher->mapImages($this->gameList) : $matcher->mapVideos($this->gameList);

        /** @var GameEntry[] $mapped */
        foreach ($mapped as $game) {
            $fileRecover = $game->get($what);
            $fileTarget = str_replace(
                    '{system}',
                    $this->emulator,
                    $this->paths[ $what ]
                ) . basename($fileRecover);

            //move file from recovery to media folder
            rename($fileRecover, $fileTarget);

            //update the media url
            $game->set($what, $fileTarget);
        }

        return [$mapped];
    }

    public function map( $what ){
        $matcher = new Matcher(
            str_replace('{system}', $this->emulator, $this->paths[ $what ])
        );

        list($mapped, $notAvailable) = $what == 'image' ? $matcher->mapImages($this->gameList) : $matcher->mapVideos($this->gameList);
        $unused = $matcher->getUnused($this->gameList, $what);

        return [$mapped, $notAvailable, $unused];
    }

    public function moveSystemDownloads(){
        $folder = str_replace('{system}', $this->emulator, $this->paths['downloads']);
        $targetVideo = str_replace('{system}', $this->emulator, $this->paths['video']);
        $targetImages = str_replace('{system}', $this->emulator, $this->paths['image']);

        @mkdir($targetVideo, 0777, true);
        @mkdir($targetImages, 0777, true);

        $files = scandir($folder);
        unset($files[0]);
        unset($files[1]);

        if (count($files)){
            foreach ($files as $file){

                //move already loaded previews to our rom video folder
                if (substr($file, -4) === '.mp4'){
                    rename( $folder . $file,  $targetVideo . $file);

                //the other files are images, move to our rom image folder
                }else{
                    rename( $folder . $file,  $targetImages . $file);
                }

            }
        }

        return $files;
    }

    public function has( $what ){
        switch ($what){
            case 'gamelist':
                return $this->gameList !== false;
                break;
        }

        return false;
    }

}
