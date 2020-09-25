<?php
//As our file is revenuemonster_standard.php so Class name is ControllerExtensionPaymentRevenuemonsterStandard which extends the Controller base class
class ControllerExtensionPaymentRevenuemonsterStandard extends Controller
{
    public function index()
    {
        require_once dirname( __FILE__ ) . '/revenuemonster_sdk.php';
        $sdk = RevenueMonster::get_instance(
            array(
                'client_id'     => $this->config->get('payment_revenuemonster_standard_appid'),
                'client_secret' => $this->config->get('payment_revenuemonster_standard_appsecret'),
                'private_key'   => $this->config->get('payment_revenuemonster_standard_privatekey'),
                'version'       => 'stable',
                'is_sandbox'    => filter_var( $this->config->get('payment_revenuemonster_standard_test'), FILTER_VALIDATE_BOOLEAN ),
            )
        );

        //Loads the language file by which the varaibles of language file are accessible in twig files
        $this->load->language('extension/payment/revenuemonster_standard');
        //Text to show when it is in test mode.
        $data['text_testmode'] = $this->language->get('text_testmode');
        //Text to show for the button.
        $data['button_confirm'] = $this->language->get('button_confirm');
        //Get the configured value, and find when it is on test mode or not.
        $data['testmode'] = $this->config->get('payment_revenuemonster_standard_test');

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if ($order_info) {

            $data['return'] = $this->url->link('checkout/success');

            $data['notify_url'] = $this->url->link('extension/payment/revenuemonster_standard/callback', '', true);
            $data['cancel_return'] = $this->url->link('checkout/checkout', '', true);

            // $data['custom'] = $sdk->get_access_token();
            $data['total'] = $order_info['total'];

            $oid = $this->session->data['order_id'];
            $payload = array(
                'order'         => array(
                    'id'             => strval( $oid ) . '-' . time(),
                    'title'          => 'opencart order ' . strval( $oid ),
                    'detail'         => strval( $oid ),
                    'additionalData' => '',
                    'amount'         => (int) round( floatval( $data['total'] ) * 100),
                    'currencyType'   => 'MYR',
                ),
                'method'        => array(),
                'type'          => 'WEB_PAYMENT',
                'storeId'       => $this->config->get('payment_revenuemonster_standard_storeid'),
                'redirectUrl'   => $data['return'],
                'notifyUrl'     => $data['notify_url'],
                'layoutVersion' => 'v2',
            );
            try {
                $response = $sdk->create_order( $payload );
                if (isset ($response->url)) {
                    $data['action'] = $response->url;
                    $tmp = explode('=', $data['action']);
                    $data['checkoutid'] = $tmp[1];
                } else {
                    print_r($response);
                }
            } catch ( Exception $e ) {
                print_r($e);
            }

            return $this->load->view('extension/payment/revenuemonster_standard', $data);
        }
    }

    // notify_url
    public function callback()
    {
        $order_id = null;
        $oid = null;
        // $payment_status = null;
        $response = json_decode(file_get_contents('php://input'));
        // $this->log->write('notify_url response: ' . serialize($response));
        
        if ($response && property_exists($response, 'data')) {
            $data = $response->data;
            // $payment_status = strtoupper($data->status);
            $order = $data->order;
            if ($order) {
                $oid = $order->id;
                $order_id = $order->detail;
            }
        }

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {
            require_once dirname( __FILE__ ) . '/revenuemonster_sdk.php';
            $sdk = RevenueMonster::get_instance(
                array(
                    'client_id'     => $this->config->get('payment_revenuemonster_standard_appid'),
                    'client_secret' => $this->config->get('payment_revenuemonster_standard_appsecret'),
                    'private_key'   => $this->config->get('payment_revenuemonster_standard_privatekey'),
                    'version'       => 'stable',
                    'is_sandbox'    => filter_var( $this->config->get('payment_revenuemonster_standard_test'), FILTER_VALIDATE_BOOLEAN ),
                )
            );

            $response = $sdk->query_order($oid);

            if (isset($response->status) && strtoupper($response->status) == 'SUCCESS') {
                $order_status_id = $this->config->get('payment_revenuemonster_standard_success_status_id');
                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
            } else {
                print_r($response);
            }
        }
        // $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('config_order_status_id'));
    }
}