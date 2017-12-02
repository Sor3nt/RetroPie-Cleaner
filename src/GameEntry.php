<?php

class GameEntry {

    private $options = [];
    private $attributes = [];
    var $removed = false;

    public function __construct($xmlEntry) {
        foreach ($xmlEntry as $key => $value) {
            if ($key === '@attributes'){
                $this->attributes = $value;
                continue;
            }

            if (is_array($value)) continue;

            $this->addOption( $key, $value);
        }

        // add some empty fields to avoid outputting "Unknown"
        foreach (['desc', 'developer', 'publisher', 'genre'] as $option) {
            if ($this->get($option) === false) $this->addOption($option, '---');
        }
    }

    public function get($key){
        return isset($this->options[$key]) && !empty($this->options[$key]) ? $this->options[$key] : false;
    }

    public function remove(){
        $this->removed = true;
    }

    public function delete($key){
        unset($this->options[$key]);
    }

    public function set($key, $value){
        $this->options[$key] = $value;
    }

    public function addOption($attr, $value){
        $this->options[$attr] = $this->toHtmlXml1Encoding($value);
    }

    private function toHtmlXml1Encoding( $value ){

        $map = [
            '&' => '&amp;',
            '"' => '&#34;',
            '\'' => '&#39;',
            "\n" => '&#xA;'
        ];

        foreach ($map as $replace => $to) {
            $value = str_replace($replace, $to, $value);
        }


        return $value;
    }

    public function toXml(){
        if ($this->removed) return '';

        $xml = "\t<game ";

        if (count($this->attributes)){
            foreach ($this->attributes as $key => $value) {
                $xml .= $key . '="' . $value . '" ';
            }

            $xml = substr($xml, 0, -1);
        }

        $xml .= ">\n";

        foreach ($this->options as $key => $value) {
            $xml .= "\t\t<" . $key . '>' . $value . '</' . $key . ">\n";
        }

        $xml .= "\t</game>";

        return $xml;
    }

}
