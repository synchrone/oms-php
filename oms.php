<?php
/**
 * Библиотека для работы с SOAP шлюзом типа Outlook 2007 / Office 2010 Mobile Service
 * @package    OMS
 * @author     Alexander Bogdanov
 * @license    http://zahymaka.com/license.html
 */

require_once 'OMS/exception.php';
require_once 'OMS/user.php';
require_once 'OMS/message.php';
require_once 'OMS/Body/sms.php';

class OMS extends SoapClient{

    /**
	 * Преобразование stdClass в DOMDocument
	 * @param  stdClass    $obj Исходный объект
	 * @param  DOMDocument $xml Не используйте этот параметр!
	 * @return DOMDocument      Результирующий XML
	 */
	public static function obj2xml ($obj, $xml = null, $rootName = 'root') {
		if ($xml == null) {
			$xml = new DOMDocument ('1.0', 'utf-8');
			$xml->formatOutput = true;
			$root = $xml->appendChild(new DOMElement($rootName));
		}
		else $root = $xml;
		if (is_object($obj)) {
			foreach ($obj as $name => $value){
				if (is_object($value)){
					OMS::obj2xml ($value, $root->appendChild (new DOMElement($name)));
                }
				elseif (is_array($value)) {
					foreach ($value as $item){
						OMS::obj2xml ($item, $root->appendChild (new DOMElement($name)));
                    }
				}
				elseif (substr($name,0,1) == '_') {
					if ($name == '_text'){
						$root->appendChild (new DomText($value));
                    }else{
						$root->setAttribute (substr($name,1), $value);
                    }
				}
				else{
					$root->appendChild (new DOMElement($name))->appendChild (new DomText($value));
                }
            }
		}
        else{
			$root->appendChild (new DomText($obj));
        }
		return $xml;
	}
    /**
	 * Преобразование DOMDocument в stdClass
	 * @param  DOMDocument $xml Исходный XML
	 * @return stdClass         Результирующий объект
	 */
	public static function xml2obj ($xml) {
		if (($xml->childNodes->length == 1) && ($xml->childNodes->item(0)->nodeName == '#text'))
			return $xml->nodeValue;
		$obj = new stdClass();
		for ($i=0; $i<$xml->childNodes->length; $i++) if ($xml->childNodes->item($i)->nodeName != "#text") {
			$node = $xml->childNodes->item($i);
			$name = $node->nodeName;
			if (isset($obj->$name)) {
				$obj->$name = array ($obj->$name);
				array_push($obj->$name, OMS::xml2obj($node));
			}
			else
				$obj->$name = OMS::xml2obj ($node);
		}
		if (count($xml->attributes)) foreach ($xml->attributes as $attrName => $attrNode) {
        	$attribute = '_'.$attrName;
            $obj->$attribute = $attrNode->nodeValue;
        }
		return $obj;
	}
    /**
     * Преобразование XML-строки в stdClass
     * @static
     * @param  $xml XML-Строка для парсинга
     * @return stdClass
     */
    public static function xmlstr2obj($xml){
        if(strpos($xml,'utf-16')){
            $xml = str_replace('utf-16','utf-8',$xml);
            try{
			    //$xml = iconv('UTF-16','UTF-8',$xml);
			    //not sure why that happens, but that's not really utf-16.
                //Prolly megafon implementation is fucked up, and reports utf-16 while sending utf-8
            }catch(ErrorException $e){
                //can't decode. so fuck it.
            }

		}
        
        $d = new DOMDocument;
        $d->loadXml($xml);
        return OMS::xml2obj($d);
    }
    /**
     * Преобразование XML-строки в ассоциативный массив
     * Нагло скопипащено, так что если будет глючить - меня не бить
     * @static
     * @param  $contents
     * @param int $get_attributes
     * @return array
     */
    public static function xml2array($contents, $get_attributes=1) {
        if(!$contents) return array();

        if(!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return array();
        }
        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
        xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
        xml_parse_into_struct( $parser, $contents, $xml_values );
        $err = xml_get_error_code($parser);
        xml_parser_free( $parser );

        if(!$xml_values) {
            echo 'xml2array parser err: '.$err;
            return;
        }



        //Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array;

        //Go through the tags.
        foreach($xml_values as $data) {
            unset($attributes,$value);//Remove existing values, or there will be trouble

            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data);//We could use the array by itself, but this cooler.

            $result = '';
            if($get_attributes) {//The second argument of the function decides this.
                $result = array();
                if(isset($value)) $result['value'] = $value;

                //Set the attributes too.
                if(isset($attributes)) {
                    foreach($attributes as $attr => $val) {
                        if($get_attributes == 1) $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                        /**  :TODO: should we change the key name to '_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too */
                    }
                }
            } elseif(isset($value)) {
                $result = $value;
            }

            //See tag status and do the needed.
            if($type == "open") {//The starting of the tag '<tag>'
                $parent[$level-1] = &$current;

                if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    $current = &$current[$tag];

                } else { //There was another element with the same tag name
                    if(isset($current[$tag][0])) {
                        array_push($current[$tag], $result);
                    } else {
                        $current[$tag] = array($current[$tag],$result);
                    }
                    $last = count($current[$tag]) - 1;
                    $current = &$current[$tag][$last];
                }

            } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if(!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;

                } else { //If taken, put all things inside a list(array)
                    if((is_array($current[$tag]) and $get_attributes == 0)//If it is already an array...
                            or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) {
                        array_push($current[$tag],$result); // ...push the new element into that array.
                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                    }
                }

            } elseif($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level-1];
            }
        }

        return($xml_array);
    }

    private $user;
    /**
     * Конструктор
     * @param OMS_User $user Наш пользователь
     * @param string $serviceUrl URL Soap службы
     * @param string $wsdl URL WSDL-описания сервиса, если вдруг сервис не захочет отдавать своё описание
     */
	public function __construct(OMS_User $user, $serviceUrl, $wsdl=null){
		if($wsdl == null){
			$wsdl = $serviceUrl.'?WSDL';
		}
        
        parent::__construct($wsdl,array(
			'location'=>$serviceUrl,
			'uri' => 'http://schemas.microsoft.com/office/Outlook/2006/OMS',
			'exceptions' => true,
			'trace'=>1,
			'cache_wsdl'=>defined('DEBUG') ? WSDL_CACHE_NONE : WSDL_CACHE_DISK,
			'features' => SOAP_WAIT_ONE_WAY_CALLS,
			'soap_version' => SOAP_1_1,
		));

        $this->user = $user;
        $info = $this->GetUserInfo($user);
        $this->user->replyPhone = $info->userInfo->replyPhone;
	}

    /**
     * Запрашивает информацию о сервисе. 
     * Например http://msdn.microsoft.com/en-us/library/bb277361(v=office.12).aspx#CodeSpippet1
     * @return stdClass
     */
    public function GetServiceInfo(){
        $response = $this->__soapCall('GetServiceInfo',array());
        return OMS::xmlstr2obj($response->GetServiceInfoResult);
    }
    
    /**
     * Запрашивает информацию о пользователе.
     * Например http://msdn.microsoft.com/en-us/library/bb277361(v=office.12).aspx#CodeSpippet4
     * @throws OMS_Exception
     * @param OMS_User $xmsUser
     * @return stdClass
     */
	public function GetUserInfo(OMS_User $xmsUser){
        $obj = $xmsUser->toStruct();
		$this->__soapCall('GetUserInfo',array($obj));
		//don't know why, but SoapClient returns NULL.
        //Probably because of UTF-16 stated in response

        $xml = OMS::xml2array($this->__getLastResponse()); //that's why such a terrible manual-parser
		$xml = $xml["SOAP-ENV:Envelope"]["SOAP-ENV:Body"]["UserInfoResponse"]["UserInfoResult"]["value"];

        $userInfo = OMS::xmlstr2obj($xml);
        if($userInfo->userInfo->error->_code != 'ok'){
            throw new OMS_Exception($userInfo->userInfo->error);
        }
		return $userInfo;
	}

    /**
     * Отправляет сообщение
     * @throws OMS_Exception
     * @param OMS_Message $xmsData Сообщение для отправки
     * @return mixed|stdClass Статусное сообщение. Обычно не представляет интереса
     */
    public function DeliverXms(OMS_Message $xmsData){
        //TODO: check if Message conforms to the data we got from GetServiceInfo.
        //such as _maxDbcsPerMessage and other limitations that are

        if(!isset($xmsData->user)){
            $xmsData->setFrom($this->user);
        }

        $response = $this->__soapCall('DeliverXms',array($xmsData->toStruct())); //could've used SendXms, but that's just an obsolete alias
        $response = OMS::xmlstr2obj($response->DeliverXmsResult);

        if($response->xmsResponse->error->_code != 'ok'){
            throw new OMS_Exception($response->xmsResponse->error);
        }
        return $response;
    }
    
}
?>
