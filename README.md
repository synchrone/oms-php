Brings Outlook 2007 / Office 2010 Mobile Service SOAP API to PHP

Sends SMS via OMS-compliant gateway.

Usage:
```php
$myNumber = '79274445566';
$myPassword = 'my-serviceguide-password';
$recipientNumber = '79274445577';
$text = 'Привед кросафчег';

$c = new OMS(new OMS_User($myNumber,$myPassword),
'https://sms.megafon.ru/oms/service.asmx',
'https://www.intellisoftware.co.uk/smsgateway/oms/oms.asmx?WSDL'
);

$m = new OMS_Message(new OMS_Body_SMS($text),$recipientNumber);
$c->DeliverXms($m);
```
For more advanced example see index.php