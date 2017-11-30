<?php



class GameList {

    /** @var GameEntry[]  */
    private $games = [];

    public $gameListXml = null;
    var $paths = null;
    var $emulator = null;

    public function __construct( $gameListXml, $emulator, $paths ) {

        $this->emulator = $emulator;
        $this->paths = $paths;
        $this->gameListXml = $gameListXml;

        $raw = file_get_contents($gameListXml);
        $xml = simplexml_load_string($raw);

        $array = json_decode(json_encode($xml),TRUE);

        if (isset($array['game']['path'])) $array['game'] = [$array['game']];
        foreach ($array['game'] as $game) {
            $entry = new GameEntry($game);
            $this->add($entry);
        }

        return true;
    }

    public function backup(){
        $raw = file_get_contents($this->gameListXml);
        file_put_contents($this->gameListXml. '.' . time(), $raw);
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

    public function add(GameEntry $gameEntry){
        $this->games[] = $gameEntry;
    }


    public function removeCorruptedEntries(){
        $removed = [
            'rom' => [],
            'image' => [],
            'video' => []
        ];

        foreach ($this->games as $game) {
            if ($game->removed) continue;

            $rom = $game->get('path');
            if (substr($rom, 0, 2) === './'){
                $rom = $this->paths['roms']  . $this->emulator . '/' . basename($rom);
            }

            // the rom is missed, K.O. remove the rom
            if (!file_exists($rom)){
                $game->remove();
                $removed['rom'][] = $game;
            }

            foreach(['image', 'video'] as $media){
                $mediaFile = $game->get($media);
                if (substr($mediaFile, 0, 2) === './'){
                    $mediaFolder = str_replace('{system}', $this->emulator, $this->paths[$media]);
                    $mediaFile = $mediaFolder . basename($mediaFile);
                }

                if (!file_exists($mediaFile)){
                    $game->delete($media);
                    $removed[$media][] = basename($mediaFile);
                }

            }
        }

        return $removed;
    }


    public function toXml(){
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<gameList>\n";
        foreach ($this->games as $game) {
            $gameXml = $game->toXml();
            if ($gameXml){
                $xml .= $game->toXml() . "\n";
            }
        }
        $xml .= '</gameList>';

        return $xml;
    }

    public function save($backup = true){
        if($backup) $this->backup();
        file_put_contents($this->gameListXml, $this->toXml());
    }

}
