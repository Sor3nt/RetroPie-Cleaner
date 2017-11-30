<?php

class Emulators {

    var $emulators = [];

    public function __construct( $paths ) {

        $emulators = array_slice(scandir($paths['roms']), 2);

        foreach ($emulators as $emulator) {
            if (!is_dir($paths['roms'] . $emulator)) continue;
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
