<?php

class DuplicateMatcher{

    var $folder;
    var $roms;

    public function __construct( $folder = '.') {
        $this->folder = $folder;

        $roms = scandir($folder);
        unset($roms[0]);
        unset($roms[1]);

        $this->roms = $roms;
    }

    public function keep($countries = ['en'], $keepJapan = false){

        $removeList = [];
        $keepList = [];
        $games = $this->getDuplicatedRoms();
        foreach ($games as $gameGroup) {
            list($keep, $remove) = $this->detectCountryRom($gameGroup, $countries);

            if ($keepJapan === false && strpos(strtolower($keep), 'japan') !== false){
                $remove[] = $keep;
                $keep = false;
            }

            $removeList = array_merge($removeList, $remove);

            if ($keep) $keepList[] = $keep;
        }

        return [$removeList, $keepList];

    }

    public function detectCountryRom( $gameGroup, $countries ){

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
                }
            }
        }

        //we have not found our right version :/
        if (is_null($keep)){
            //just keep the first version...
            $keep = $gameGroup[0];
        }

        foreach ($gameGroup as $game) {
            if ($game !== $keep && !in_array($game, $remove)){
                $remove[] = $game;
            }
        }

        return [$keep, $remove];
    }

    public function getDuplicatedRoms( ){
        $games = [];
        foreach ($this->roms as $game) {
            $cleanName = $this->cleanName($game);
            
            if (!isset($games[$cleanName])) $games[$cleanName] = [];
            $games[$cleanName][] = $game;
            
        }

        return $games;
    }


    private function cleanName($name){
        // replace country code or years like "Megaman (U) (19XX).xxx"
        $name = preg_replace('/\(.+\)/', '', $name);
        $name = preg_replace('/\[.+\]/', '', $name);
        $name = str_replace('&amp;', '', $name);
        $name = str_replace('&', '', $name);
        $name = str_replace(',', '', $name);
        $name = str_replace('.', '', $name);
        $name = str_replace(':', '', $name);
        $name = str_replace('[', '', $name);
        $name = str_replace(']', '', $name);
        $name = str_replace('!', '', $name);
        $name = str_replace('-', '', $name);
        $name = str_replace('+', '', $name);
        $name = str_replace('?', '', $name);
        $name = str_replace('\'', '', $name);
        $name = str_replace('&#39;', '', $name);
        $name = strtolower(trim($name));

        $name = str_replace('the ', '', $name);
        $name = str_replace(' the', '', $name);

        $name = str_replace(' ', '', $name);

        return $name;
    }

}