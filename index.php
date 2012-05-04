<?php
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler"); //Library really counts on a rethrow like that. Don't disappoint it, please.


require_once 'oms.php';
$myNumber = '79274445566';
$u = new OMS_User($myNumber,'my-serviceguide-password');

try{
    $c = new OMS($u,
        'https://sms.megafon.ru/oms/service.asmx',
        //'https://sms.megafon.ru/oms/service.asmx?WSDL', // A russian carrier won't send us WSDL just like that
        //it obviously needs some browser-style headers, so we can fetch wsdl emulating it, but that's not our aim

        'https://www.intellisoftware.co.uk/smsgateway/oms/oms.asmx?WSDL' //simplier to get them there
        //'http://dl.dropbox.com/u/3477485/oms.wsdl' //or there. in case intellisoftware.co.uk is down
    );
    
}catch(OMS_Exception $e){
    die('Login should be like 79274445566, and a service guide password as that password');
}

var_dump($c->GetServiceInfo());

$m = new OMS_Message(new OMS_Body_SMS('Hi fella'),$myNumber);
//var_dump($c->DeliverXms($m)); //so we send that. Careful with it.

?>