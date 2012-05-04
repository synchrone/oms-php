<?php
require_once 'body.php';

class OMS_Message extends OMS_Object {
    public $_xmlns = 'http://schemas.microsoft.com/office/Outlook/2006/OMS';

    protected $rootElName = 'xmsData';
    /**
     * @param OMS_Body $message Message to send
     * @param $to Phone number (like: 79274445566) or an array of numbers
     * @param null|OMS_User $from Whom do we send from ? If null - we send as initial user
     * @param null $schedule When do we send it. String like 2005-04-20T14:20:00Z. If not set - current datetime
     */
    public function __construct(OMS_Body $message, $to, OMS_User $from=null, $schedule=null){
        if($from != null){
            $this->setFrom($from);
        }

        $this->xmsHead = (object)array(
            'scheduled' => is_null($schedule) ? gmdate('Y-m-d\TH:i:s\Z') : $schedule,
            'requiredService' => (is_a($message,'OMS_Body_MMS') ? 'M' : 'S').'MS_SENDER',
            'to'=>(object)array()
        );

        $to = is_array($to) ? $to : array($to);
        foreach($to as $recipient){
            $this->xmsHead->to->recipient[] = $recipient;
        }
        $this->xmsBody = $message;
    }

    public function setFrom(OMS_User $from){
        $this->_client = $from->_client;
        
        unset($from->_client);
        unset($from->_xmlns);
        $this->user = $from;
    }


}
