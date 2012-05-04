<?php

class OMS_Body_SMS extends OMS_Body{
    public $_format = 'SMS';
    public $content = array();

    /**
     * Constructor
     * @param  $message Message body, or an array of bodies
     */
    public function __construct($message){
        if(is_array($message)){
            foreach($message as $msg){
                $this->addContent($msg);
            }
        }else{
            $this->addContent($message);
        }
    }

    /**
     * Adds a text chunk
     * @param  $text More text
     * @return void
     */
    public function addContent($text){
        $thisNum = sizeof($this->content);
        $msgHash = md5($text);
        $msgHash = strtoupper(substr($msgHash,0,16).'.'.substr($msgHash,16));

        $this->content[] = (object)array(
            '_contentType'      => 'text/plain',
            '_contentId'        => 'Att'.$thisNum.'.txt@'.$msgHash,
            '_contentLocation'  => ++$thisNum.'.txt',
            '_text' => $text
        );
    }
}
