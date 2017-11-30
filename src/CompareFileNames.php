<?php

class CompareFileNames{

    /** @var array */
    private $dic;


    public function __construct( $dic) {

        $this->dic = $this->prepareInput($dic);
    }

    private function prepareInput( $dic ){

        $results = [];
        foreach ($dic as $entry) {
            $romName = substr($entry, 0, strrpos($entry, '.'));
            $romName = Helper::cleanName($romName);

            $results[$romName] = $entry;
        }

        return $results;
    }

    public function find( GameEntry $entry){
        $romFileName = basename($entry->get('path'));
        $romName = substr($romFileName, 0, strrpos($romFileName, '.'));
        $romName = Helper::cleanName($romName);

        if (isset($this->dic[$romName])) return $this->dic[$romName] ;

        return false;
    }

    private function exists( $name ){
        $key = $name;
        return isset($this->dic[$key]) ? true : false;
    }

}
