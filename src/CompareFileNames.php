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
            $romName = Helper::cleanName($entry);

            $results[$romName] = $entry;
        }

        return $results;
    }

    public function find( GameEntry $entry){
        $romFileName = basename($entry->get('path'));
        $romName = Helper::cleanName($romFileName);

        if (isset($this->dic[$romName])) return $this->dic[$romName] ;

        return false;
    }

    private function exists( $name ){
        $key = $name;
        return isset($this->dic[$key]) ? true : false;
    }

}
