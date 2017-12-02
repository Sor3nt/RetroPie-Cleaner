<?php



class System {

    public $systemXmlLocation = null;
    public $emulators = [];

    /**
     * System constructor.
     * @param $path array
     */
    public function __construct( $path ) {

        foreach ($path['system'] as $location) {
            if (file_exists($location)){
                $this->systemXmlLocation = $location;
                break;
            }
        }

        if (!is_null($this->systemXmlLocation)){

            $raw = file_get_contents($this->systemXmlLocation);
            $xml = simplexml_load_string($raw);

            $array = json_decode(json_encode($xml),TRUE);
            if (isset($array['system']['path'])) $array['system'] = [$array['system']];

            foreach ($array['system'] as $system) {
                $this->emulators[] = new Emulator($system, $path);
            }

        }

    }

    /**
     * @return Emulator[]
     */
    public function get(){
        return $this->emulators;
    }

}
