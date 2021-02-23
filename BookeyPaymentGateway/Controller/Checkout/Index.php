<?php

namespace Bookey\BookeyPaymentGateway\Controller\Checkout;

use Magento\Sales\Model\Order;

/**
 * @package Bookey\BookeyPaymentGateway\Controller\Checkout
 */
class Index extends AbstractAction {

    private function getPayload($order) {
        if($order == null) {
            $this->getLogger()->debug('Unable to get order from last lodged order id. Possibly related to a failed database call');
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
        }

        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $billingAddressParts = preg_split('/\r\n|\r|\n/', $billingAddress->getData('street'));
        $shippingAddressParts = preg_split('/\r\n|\r|\n/', $shippingAddress->getData('street'));
        $orderId = $order->getRealOrderId();
        $data = array(
            'x_currency' => $order->getOrderCurrencyCode(),
            'x_url_callback' => $this->getDataHelper()->getCompleteUrl(),
            'x_url_complete' => $this->getDataHelper()->getCompleteUrl(),
            'x_url_cancel' => $this->getDataHelper()->getCancelledUrl($orderId),
            'x_shop_name' => $this->getDataHelper()->getStoreCode(),
            'x_account_id' => $this->getGatewayConfig()->getMerchantNumber(),
            'x_apikey' => $this->getGatewayConfig()->getApiKey(),
            'x_apipublickey' => $this->getGatewayConfig()->getApiPublicKey(),
            'x_reference' => $orderId,
            'x_invoice' => $orderId,
            'x_amount' => $order->getTotalDue(),
            'x_customer_first_name' => $order->getCustomerFirstname(),
            'x_customer_last_name' => $order->getCustomerLastname(),
            'x_customer_email' => $order->getData('customer_email'),
            'x_customer_phone' => $billingAddress->getData('telephone'),
            'x_customer_billing_address1' => $billingAddressParts[0],
            'x_customer_billing_address2' => count($billingAddressParts) > 1 ? $billingAddressParts[1] : '',
            'x_customer_billing_city' => $billingAddress->getData('city'),
            'x_customer_billing_state' => $billingAddress->getData('region'),
            'x_customer_billing_zip' => $billingAddress->getData('postcode'),
            'x_customer_shipping_address1' => $shippingAddressParts[0],
            'x_customer_shipping_address2' => count($shippingAddressParts) > 1 ? $shippingAddressParts[1] : '',
            'x_customer_shipping_city' => $shippingAddress->getData('city'),
            'x_customer_shipping_state' => $shippingAddress->getData('region'),
            'x_customer_shipping_zip' => $shippingAddress->getData('postcode'),
            'x_test' => 'false'
        );

        foreach ($data as $key => $value) {
            $data[$key] = preg_replace('/\r\n|\r|\n/', ' ', $value);
        }

        $apiKey = $this->getGatewayConfig()->getApiKey();
        $apiPublicKey = $this->getGatewayConfig()->getApiPublicKey();
        $signature = $this->getCryptoHelper()->generateSignature($data, $apiKey, $apiPublicKey);
        $data['x_signature'] = $signature;

        return $data;
    }

    private function postToCheckout($checkoutUrl, $payload)
    {

			$orderId = $payload['x_reference'];
			$amount = $payload['x_amount'];
			$mid = $payload['x_account_id'];
			$secret_key = $payload['x_apikey'];
            $public_key = $payload['x_apipublickey'];
			$cancelurl = $payload['x_url_cancel'];
			$callbackurl = $payload['x_url_callback'];
            $tex = mt_rand(1000000000000000, 9999999999999999);
            // $mid = "mer1800063";
            $txnRefNo = $tex;
            $su = $callbackurl."?orderid=".$orderId;
            $fu = $cancelurl;
            $amt =$amount;
            // $txnTime = "1545633631518";
            $txnTime = date("ymdHis");
            $crossCat = "GEN";
            // $secret_key = "4794175";
            $paymentoptions = "Bookeey";
            $data = "$mid|$txnRefNo|$su|$fu|$amt|$txnTime|$crossCat|$secret_key|$public_key";
            $hashed = hash('sha512', $data);?>

            <?php
             $credit = $_GET['type'];
            //Form Post Params
            //Important: The order of the following parameters are ESSENTIAL for the encryption to work.
            $params['mid']       = $mid;
            $params['txnRefNo']  = $txnRefNo;
            $params['surl']      = $su;
            $params['furl']      = $fu;
            $params['amt']       = $amt;
            $params['crossCat']  = $crossCat;
            $params['hashMac']   = $hashed;
            $params['status']    = '';
            $params['code']      = '';
            $params['msg']       = '';
            $params['txnid'] = '';
            $params['txnTime'] = $txnTime;
            $params['customerHash'] = '';
            $params['returnHash'] = '';
            $params['paymentoptions'] = $credit;
            //Encrypt values to create the AuthHash
            
            $post_values = "";
            foreach ($params as $key => $value) {
                $post_values .= $value;
            }
            $post_values .= $secret_key;

            // $params['ShowTransactionResult'] = 0;
            //Adding to the form params the AuthHash
            $params['AuthHash'] = $this->encryptAndEncode($post_values);
            $bookeey_arg_array = array();
            foreach ($params as $key => $value) {
                $bookeey_arg_array[] = '<input type="hidden" placeholder="'.$key.'" name="'.$key.'" value="'.$value.'" />';
            }

            $redirectingMessage = "testing";

//            echo "<pre>";print_r($checkoutUrl)."<br/>";
//            echo "<pre>";print_r($params)."<br/>";
//             echo "<pre>";print_r($payload)."<br/>";
//              echo "<pre>";print_r($bookeey_arg_array)."<br/>";

// exit;

            echo  '<form action="'.$checkoutUrl.'" method="post" id="bookeey_payment_form">
                    ' . implode('', $bookeey_arg_array) . '';
                     foreach ($payload as $key => $value) {
             		echo "<input type='hidden' id='$key' name='$key' value='".htmlspecialchars($value, ENT_QUOTES)."'/>";
        			}
               echo '</form>';





        // echo
        // "<html>
        //     <body>
        //     <form id='form' action='$checkoutUrl' method='post'>";
        // foreach ($payload as $key => $value) {
        //     echo "<input type='hidden' id='$key' name='$key' value='".htmlspecialchars($value, ENT_QUOTES)."'/>";
        // }
        // echo
        //     '</form>
        //     </body>';
        echo
            '<script>
                var form = document.getElementById("bookeey_payment_form");
                form.submit();
            </script>
        </html>';
    }



public function encryptAndEncode($strIn)
        {
            //The encryption required by bookeey is SHA-512
            $result = mb_convert_encoding($strIn, 'UTF-16LE', 'ASCII');
            $result = hash('sha512', $result);
            return $result;
        }


    /**
     * 
     *
     * @return void
     */
    public function execute() {


        try {
            $order = $this->getOrder();
            if ($order->getState() === Order::STATE_PENDING_PAYMENT) {
                $payload = $this->getPayload($order);
                $this->postToCheckout($this->getGatewayConfig()->getGatewayUrl(), $payload);
            } else if ($order->getState() === Order::STATE_CANCELED) {
                $errorMessage = $this->getCheckoutSession()->getBookeyErrorMessage(); //set in InitializationRequest
                if ($errorMessage) {
                    $this->getMessageManager()->addWarningMessage($errorMessage);
                    $errorMessage = $this->getCheckoutSession()->unsBookeyErrorMessage();
                }
                $this->getCheckoutHelper()->restoreQuote(); //restore cart
                $this->_redirect('checkout/cart');
            } else {
                $this->getLogger()->debug('Order in unrecognized state: ' . $order->getState());
                $this->_redirect('checkout/cart');
            }
        } catch (Exception $ex) {
            $this->getLogger()->debug('An exception was encountered in bookey/checkout/index: ' . $ex->getMessage());
            $this->getLogger()->debug($ex->getTraceAsString());
            $this->getMessageManager()->addErrorMessage(__('Unable to start Bookey Checkout.'));
        }
    }

}
