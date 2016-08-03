<?php

require_once 'CRM/Core/Page.php';

class CRM_iATS_Page_IATSDPMPostback extends CRM_Core_Page {
  function run() {
    CRM_Core_Error::debug_log_message('Got PostBack from iATS DPM');
    CRM_Core_Error::debug_var('POST', $_POST);
    exit(); // abruptly!
    // TODO: use the cid value to put the customer name in the title?
    // CRM_Utils_System::setTitle(ts('iATS CustomerLink'));
    $customerCode = CRM_Utils_Request::retrieve('customerCode', 'String');
    $paymentProcessorId = CRM_Utils_Request::retrieve('paymentProcessorId', 'Positive');
    $is_test = CRM_Utils_Request::retrieve('is_test', 'Integer');
    $this->assign('customerCode', $customerCode);
    require_once("CRM/iATS/iATSService.php");
    $credentials = iATS_Service_Request::credentials($paymentProcessorId, $is_test);
    $iats_service_params = array('type' => 'customer', 'iats_domain' => $credentials['domain'], 'method' => 'get_customer_code_detail');
    $iats = new iATS_Service_Request($iats_service_params);
    // print_r($iats); die();
    $request = array('customerCode' => $customerCode);
    // make the soap request
    $response = $iats->request($credentials,$request);
    $customer = $iats->result($response, FALSE); // note: don't log this to the iats_response table
    if (empty($customer['ac1'])) {
      $alert = ts('Unable to retrieve card details from iATS.<br />%1', array(1 => $customer['AUTHORIZATIONRESULT']));
      CRM_Core_Session::setStatus($alert, ts('Warning'), 'alert');
    }
    else {
      $ac1 = $customer['ac1']; // this is a SimpleXMLElement Object
      $attributes = $ac1->attributes();
      $type = $attributes['type'];
      $card = get_object_vars($ac1->$type);
      $card['type'] = $type;
      foreach(array('ac1','status','remote_id','auth_result') as $key) {
        if (isset($customer[$key])) {
          unset($customer[$key]);
        }
      }
      $this->assign('customer', $customer);
      $this->assign('card', $card);
    }
    parent::run();
  }
}