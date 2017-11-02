<?php
/**
* Simple Zoho CRM inserter.
*
* MIT licensed. Originally written by Pete Sevander and Mikko Ohtamaa in 2011
* Enhanced by Jeremy Nagel to make it a bit easier to use and update it with latest changes to the API
*
*/

class ZohoException extends Exception { }

class Zoho {

  public function __construct($authtoken, $extra_auth_params = array(), $auth_url="https://accounts.zoho.com/login") {
    $this->authtoken = $authtoken;
    $this->ticket = null;
  }

  public function json_to_array($response_data, $module){
    $array_contents = array();
    foreach($response_data->response->result->$module->row->FL as $key => $record){
      $array_contents[$record->val] = $record->content;
    }
    return $array_contents;
  }

  /**
  *  Adds a new record in Zoho CRM
  *  @param  $module  The module in which the record will reside
  *  @param  $update_data  The new data for the record
  * https://crm.zoho.com/crm/private/xml/Module/updateRecords?newFormat=1&apikey=APIkey&ticket=Ticket
  **/
  public function insert_record($leads, $extra_post_parameters=array()) {
    $xmldata = $this->XMLfy($leads);
    $post = array(
      'newFormat' => 1,
      'authtoken' => $this->authtoken,
      'version' => 2,
      'xmlData' => $xmldata,
      'duplicateCheck' => 2,
      'wfTrigger' => 'true'
    );

    array_merge($post, $extra_post_parameters);
    $q = http_build_query($post);
    $response = $this->openUrl("https://crm.zoho.com/crm/private/xml/Leads/insertRecords", $q);
    $this->check_successful_xml($response);
    return true;

  }

  /**
  * Uses curl to open a URL
  */
  function openUrl($url, $data=null) {
    $ch = curl_init();
    $timeout = 5;

    if($data) {
      curl_setopt($ch,CURLOPT_POST,1);
      curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch,CURLOPT_VERBOSE, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    }
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }

  public function check_successful_xml($response) {
    $html = new DOMDocument();
    $html->loadXML($response);

    if ($err = $html->getElementsByTagName('error')->item(0)) {
      throw new ZohoException($err->getElementsByTagName('message')->item(0)->nodeValue);
    }

    return true;
  }

  public function XMLfy ($arr) {
    $xml = "<Leads>";
    $no = 1;
    foreach ($arr as $a) {
      $xml .= "<row no=\"$no\">";
      foreach ($a as $key => $val) {
        $xml .= "<FL val=\"$key\">$val</FL>";
      }
      $xml .= "</row>";
      $no += 1;
    }
    $xml .= "</Leads>";
    return $xml;
  }

}
