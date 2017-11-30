<?php

class Log{

    public $log = [];

    public function movedFiles($files, $reason) {
        $msg = [];
        $msg[] = sprintf('Moved %s Files because %s', count($files), $reason);
        foreach ($files as $file) {
            $msg[] = ' => ' . basename($file);
        }

        $this->log[] = implode("\n", $msg);
    }


    public function markRemoved($files, $reason) {
        $msg = [];
        $msg[] = sprintf('Remove %s gamelist.xml entries because %s', count($files), $reason);
        foreach ($files as $file) {
            list($source, $target) = $file;
            $msg[] = ' => ' . $file;
        }

        $this->log[] = implode("\n", $msg);
    }


    public function optionRemoved($files, $option, $reason) {
        $msg = [];
        $msg[] = sprintf('Remove the option %s from % entries because %s', $option, count($files), $reason);
        foreach ($files as $file) {
            list($source, $target) = $file;
            $msg[] = ' => ' . $file;
        }

        $this->log[] = implode("\n", $msg);
    }


    public function save( $file = 'cleaner.log') {
        file_put_contents($file, implode("\n\n", $this->log));
    }


}