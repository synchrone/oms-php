<?php

class OMS_Exception extends Exception{
    public function __construct($errorObj){
        parent::__construct('OMS service returned errorCode: '.$errorObj->_code.' of level: '.$errorObj->_severity);
        //TODO: Differentiate exceptions by type

        /**
         * Code can be: 
         * invalidUser
         * unregisteredUser
         * expiredUser
         * perDayMsgLimit
         */
        /**
         * Severity can be:
         * neutral
         * failure
         */

    }
}
