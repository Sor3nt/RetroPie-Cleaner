<?php



class GameList {

    /** @var GameEntry[]  */
    private $games = [];

    public $gameListXml = 'gamelist.xml';


    public function load( ){

        if (!file_exists($this->gameListXml)) return false;
        $raw = file_get_contents($this->gameListXml);
        $xml = simplexml_load_string($raw);

        $array = json_decode(json_encode($xml),TRUE);

        if (isset($array['game']['path'])) $array['game'] = [$array['game']];
        foreach ($array['game'] as $game) {
            $entry = new GameEntry($game);
            $this->games[ $entry->get('path') ] = $entry;
        }

        return true;
    }

    public function backup(){
        $raw = file_get_contents($this->gameListXml);
        file_put_contents($this->gameListXml. '.' . time(), $raw);
    }

    public function get(){
        return $this->games;
    }

    public function set(GameEntry $gameEntry){
        $this->games[ $gameEntry->get('path') ] = $gameEntry;
    }


    public function toXml(){
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<gameList>\n";
        foreach ($this->games as $game) {
            $xml .= $game->toXml() . "\n";
        }
        $xml .= '</gameList>';

        return $xml;
    }

    public function save($file = 'gamelist.xml'){
        file_put_contents($file, $this->toXml());
    }

}
