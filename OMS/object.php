<?php 
abstract class OMS_Object extends stdClass{

    public function toXmlDoc(){
        return OMS::obj2xml($this,null,$this->rootElName);
    }
    public function toStruct(){
		$str = new stdClass();
		$str->{$this->rootElName} = $this->toXmlDoc()->saveXml();
		return $str;
	}
    public function toXmlNode(){
        return $this->toXmlDoc()->childNodes->item(0);
    }
    public function __toString(){
        return $this->toXmlNode()->C14N();
    }
}
