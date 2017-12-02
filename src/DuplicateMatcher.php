<?php

class DuplicateMatcher{

    var $folder;
    var $roms = [];

    public function __construct( Emulator $emulator ) {
        $this->folder = $emulator->romLocation;

        if (is_dir($this->folder)){

            $roms = array_slice(scandir($this->folder), 2);

            foreach ($roms as $rom) {
                if (is_dir($this->folder . $rom)) continue;

                if (in_array(strtolower(substr($rom, -4)), $emulator->allowedExtensions)){
                    $this->roms[] = $rom;
                }
            }
        }
    }

    public function filter($countries = ['en'], $keepJapan = false){

        $removeList = [];
        $keepList = [];

        foreach ($this->groupRoms() as $gameGroup) {
            list($keep, $remove) = $this->detectCountryRom($gameGroup, $countries);

            if (
                $keepJapan === false &&
                (
                    strpos(strtolower($keep), 'japan') !== false ||
                    strpos(strtolower($keep), '(j)') !== false ||
                    strpos(strtolower($keep), '[j]') !== false
                )
            ){
                $remove[] = $keep;
                $keep = false;
            }

            $removeList = array_merge($removeList, $remove);

            if ($keep) $keepList[] = $keep;
        }

        return [$removeList, $keepList];
    }

    public function move( $fileList, $moveTo ){

        @mkdir($moveTo, 0777, true);
        foreach ($fileList as $file){
            rename($this->folder . '/'. $file, $moveTo. $file);
        }
    }

    private function detectCountryRom( $gameGroup, $countries ){

        $keep = null;
        $remove = [];

        foreach ($gameGroup as $game) {

            foreach ($countries as $country) {
                preg_match('/\(.*' . $country . '.*\)/i', $game, $result);
                if (count($result)){
                    if (is_null($keep)){
                        $keep = $game;
                        break;
                    }
                }else{
                    preg_match('/\[.*' . $country . '.*\]/i', $game, $result);
                    if (count($result)){
                        if (is_null($keep)){
                            $keep = $game;
                            break;
                        }
                    }
                }
            }
        }

        //we have not found our right version :/
        if (is_null($keep)){

            //we use the shortest game name
            $len = 1000;
            foreach ($gameGroup as $item) {
                if (strlen($item) < $len){
                    $len = strlen($item);
                    $keep = $item;
                }
            }

            //just in case...
            if (is_null($keep)) $keep = $gameGroup[0];
        }

        //prepare not wanted games
        foreach ($gameGroup as $game) {

            if ($game !== $keep && !in_array($game, $remove)){
                $remove[] = $game;
            }
        }

        return [$keep, $remove];
    }

    private function groupRoms( ){
        $games = [];
        foreach ($this->roms as $game) {
            $cleanName = Helper::cleanName($game);

            if (!isset($games[$cleanName])) $games[$cleanName] = [];
            $games[$cleanName][] = $game;
            
        }

        return $games;
    }
}