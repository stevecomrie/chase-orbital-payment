<?php

use SimpleXMLElement;

class ChasePayment {

   public function process( $order_id, $total, $credit_card_number, $exp_month, $exp_year, $cvd = '' ) {

      // hosts
      // test1 : https://orbitalvar1.paymentech.net
      // test2 : https://orbitalvar2.paymentech.net
      // prod1 : https://orbital1.paymentech.net
      // prod2 : https://orbital2.paymentech.net

      $host               = "https://orbitalvar1.paymentech.net";
      $username           = ''; // Orbital Connection UserName (required)
      $password           = ''; // Orbital Connection Password (required)
      $merchant_id        = ''; // Merchant ID (required)

      $CardSecValInd      = NULL;
      $CardSecVal         = NULL;
      // TODO: if card type is VISA or DISCOVER
      // $CardSecValInd      = 1;
      // $CardSecVal         = $cvd;

      $request    = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Request></Request>');
      $new_order  = $request->addChild("NewOrder");
      $post_array = array(
         "OrbitalConnectionUsername" => $username,
         "OrbitalConnectionPassword" => $password,
         "IndustryType"              => "EC",
         "MessageType"               => "AC",
         "BIN"                       => '000002',
         "MerchantID"                => $merchant_id,
         "TerminalID"                => '001',
         "CardBrand"                 => NULL,
         "AccountNum"                => str_replace( " ", "", $credit_card_number ),
         "Exp"                       => str_pad( $exp_month, 2, '0', STR_PAD_LEFT) . $exp_year,
         "CurrencyCode"              => '124',
         "CurrencyExponent"          => '2',
         // 'CardSecValInd'             => $CardSecValInd,
         // 'CardSecVal'                => $CardSecVal,
         // "AVSzip"                    => 'ZIPHERE',
         // "AVSaddress1"               => substr( 'ADDRESS 1 HERE', 0, 30),
         // "AVSaddress2"               => substr( 'ADDRESS 2 HERE', 0, 30),
         // "AVScity"                   => substr( 'CITY', 0, 20),
         // "AVSstate"                  => substr( 'STATE', 0, 2),
         // "AVSphoneNum"               => '0000000000', // no hypens or spaces
         // "AVSname"                   => "NAME HERE",
         "OrderID"                   => substr(substr(time(), 2, 8) . "-" . $order_id, 0, 22 ),
         "Amount"                    => round( $total * 100 ),
      );


      foreach ($post_array as $key=>$item) {
         if ($item != "") {
            $new_order->addChild($key, $item);
         }
      }

      $xml = (string) $request->asXML();
      $header = "POST /AUTHORIZE/ HTTP/1.0\r\n";
      $header.= "MIME-Version: 1.0\r\n";
      $header.= "Content-type: application/PTI52\r\n";
      $header.= "Content-length: "  .strlen($xml) . "\r\n";
      $header.= "Content-transfer-encoding: text\r\n";
      $header.= "Request-number: 1\r\n";
      $header.= "Document-type: Request\r\n";
      $header.= "Connection: close \r\n\r\n";
      $header.= $xml;

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL,$host);
      curl_setopt($ch, CURLOPT_TIMEOUT, 20);
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $header);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $data = curl_exec($ch);

      // parse response from chase
      $transaction = new SimpleXMLElement($data);

      // prepare the return response
      $response = [
         'authorized'     => FALSE,
         'declined'       => FALSE,
         'order_id'       => NULL,
         'transaction_id' => NULL,
         'failed'         => TRUE,
         'error_message'  => "",
      ];

      // error communicating with server
      if( curl_errno($ch) ) {
         $response['error_message'] = "Problem communicating with server";
         return $response;
      }

      // close connection
      curl_close($ch);

      // error parsing response object
      if( !$transaction ) {
         $response['error_message'] = "Problem communicating with server";
         return $response;
      }

      // approved
      if( (string) $transaction->NewOrderResp->ProcStatus == "0" && (string) $transaction->NewOrderResp->ApprovalStatus == 1 ) {
         $response['authorized']     = TRUE;
         $response['declined']       = FALSE;
         $response['order_id']       = (string) $transaction->NewOrderResp->OrderID;
         $response['transaction_id'] = (string) $transaction->NewOrderResp->TxRefNum;
         $response['failed']         = FALSE;
         $response['error_message']  = "";

      // not approved
      } else {
         $response['error_message']  = (string) $transaction->NewOrderResp->StatusMsg;
      }

      return $response;
   }
}
