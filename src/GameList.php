<?php

class GameList {

    /** @var GameEntry[]  */
    private $games = [];

    /** @var Emulator */
    public $emulator;

    public function __construct( Emulator $emulator ) {

        $this->emulator = $emulator;

        if (file_exists($emulator->gameListLocation)){
            $raw = file_get_contents($emulator->gameListLocation);
            $xml = simplexml_load_string($raw);

            $array = json_decode(json_encode($xml),TRUE);

            if (isset($array['game'])){
                if (isset($array['game']['path'])) $array['game'] = [$array['game']];

                foreach ($array['game'] as $game) {
                    $this->games[] = new GameEntry($game);
                }

            }

        }
    }

    public function backup(){
        $raw = file_get_contents($this->emulator->gameListLocation);
        file_put_contents($this->emulator->gameListLocation. '.' . time(), $raw);
    }

    /**
     * @return GameEntry[]
     */
    public function get(){
        $result = [];
        foreach ($this->games as $game) {
            if ($game->removed) continue;
            $result[] = $game;
        }
        return $result;
    }


    /**
     * @return array
     */
    public function removeCorruptedEntries(){
        $removed = [
            'rom' => [],
            'image' => [],
            'video' => []
        ];

        foreach ($this->games as $game) {
            if ($game->removed) continue;

            $rom = $this->relativeToAbsolute($game->get('path'));


            // the rom is missed, K.O. remove the entry
            if (!file_exists($rom)){
                $game->remove();
                $removed['rom'][] = basename($rom);
                continue;
            }

            foreach(['images', 'videos'] as $media){
                $mediaFolder = $this->emulator->romLocation . '/' . $media . '/';

                $mediaFile = $game->get($media == 'images' ? 'image' : 'video' );
                if ($mediaFile == false) continue;

                $mediaFile = $mediaFolder . basename($mediaFile);

                if (!file_exists($mediaFile)){
                    $game->delete($media == 'images' ? 'image' : 'video');
                    $removed[$media][] = basename($mediaFile);
                }

            }
        }

        return $removed;
    }

    private function relativeToAbsolute( $path ){
        if (substr($path, 0, 2) === './'){
            $path = $this->emulator->romLocation . '/' . basename($path);
        }

        return $path;
    }

    public function toXml(){
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<gameList>\n";
        foreach ($this->games as $game) {
            if ($game->removed) continue;
            $xml .= $game->toXml() . "\n";
        }
        $xml .= '</gameList>';

        return $xml;
    }

    public function save($backup = true){
        if($backup) $this->backup();
        file_put_contents($this->emulator->gameListLocation, $this->toXml());
    }

}
