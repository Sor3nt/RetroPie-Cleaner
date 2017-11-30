<?php

class Emulators {

    var $emulators = [];

    public function __construct( $paths ) {

        $emulators = scandir($paths['roms']);
        unset($emulators[0]);
        unset($emulators[1]);

        foreach ($emulators as $emulator) {
            $this->emulators[$emulator] = new Emulator($emulator, $paths);
        }
    }

    /**
     * @param null $emulator
     * @return bool|Emulator|Emulator[]
     */
    public function get( $emulator = null ){
        if (is_null($emulator)) return $this->emulators;
        return isset($this->emulators[$emulator]) ? $this->emulators[$emulator] : false;
    }


}
