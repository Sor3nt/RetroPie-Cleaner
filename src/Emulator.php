<?php

class Emulator {

    /** @var GameList|bool */
    private $gameList = false;
    private $path = null;

    public $gameListLocation = null;
    public $allowedExtensions = null;
    public $platform = null;

    public function __construct( $emulator, $path ) {

        $this->path = $path;
        $this->romLocation = $emulator['path'];

        $this->platform = $emulator['platform'];
        $this->allowedExtensions = array_unique(explode(' ', strtolower($emulator['extension'])));

        foreach ($path['gameList'] as $gameListLocation) {
            $location = $gameListLocation . $this->platform . '/gamelist.xml';

            if (file_exists($location)){
                $this->gameListLocation = $location;
                break;
            }
        }

        if (!is_null($this->gameListLocation)){
            $this->gameList = new GameList( $this );
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

        $videoMapped = array_merge($videoMapped, $this->mapUnused('video'));
        $imageMapped = array_merge($imageMapped, $this->mapUnused('image'));


        return [
            [$imageMapped, $imageNotAvailable, $unusedImages],
            [$videoMapped, $videoNotAvailable, $unusedVideos]
        ];
    }

    public function mapUnused( $what ){
        $sourceFolder = $this->path['unused'] . $this->platform . '/' . $what . 's/';
        $matcher = new Matcher(
            $sourceFolder
        );

        list($mapped) = $what == 'image' ? $matcher->mapImages($this->gameList) : $matcher->mapVideos($this->gameList);

        /** @var GameEntry[] $mapped */
        foreach ($mapped as $game) {
            $fileRecover = $game->get($what);
            if ($fileRecover === false) continue;

            if (substr($fileRecover, 0, 2) === './'){
                $fileRecover = $sourceFolder . basename($fileRecover);
            }

            $fileTarget = $this->romLocation . '/' . $what . 's/' . basename($fileRecover);

            //move file from recovery to media folder
            rename($fileRecover, $fileTarget);

            //update the media url
            $game->set($what, './' . $what . 's/' . basename($fileRecover));
        }

        return $mapped;
    }



    public function map( $what ){

        $matcher = new Matcher(
            $this->romLocation . '/' . $what . 's/'
        );

        list($mapped, $notAvailable) =
            $what == 'image' ?
                $matcher->mapImages($this->gameList) :
                $matcher->mapVideos($this->gameList)
        ;

        $unused = $matcher->getUnused($this->gameList, $what);

        return [$mapped, $notAvailable, $unused];
    }

    public function moveSystemDownloads(GameList $gameList){
        $folder = $this->path['downloads'] . $this->platform . '/';
        $targetVideo = $this->romLocation . '/videos/';
        $targetImages = $this->romLocation . '/images/';

        @mkdir($targetVideo, 0777, true);
        @mkdir($targetImages, 0777, true);

        if (!is_dir($folder)) return [];

        $files = array_slice(scandir($folder), 2);

        if (count($files)){
            foreach ($files as $file){
                if (is_dir($folder . $file)) continue;

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
