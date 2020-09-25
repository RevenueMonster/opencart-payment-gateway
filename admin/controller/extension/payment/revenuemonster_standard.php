<?php
class ControllerExtensionPaymentRevenuemonsterStandard extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/revenuemonster_standard');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_revenuemonster_standard', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['email'])) {
            $data['error_email'] = $this->error['email'];
        } else {
            $data['error_email'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/revenuemonster_standard', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['action'] = $this->url->link('extension/payment/revenuemonster_standard', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_revenuemonster_standard_appid'])) {
            $data['payment_revenuemonster_standard_appid'] = $this->request->post['payment_revenuemonster_standard_appid'];
        } else {
            $data['payment_revenuemonster_standard_appid'] = $this->config->get('payment_revenuemonster_standard_appid');
        }
        if (isset($this->request->post['payment_revenuemonster_standard_appsecret'])) {
            $data['payment_revenuemonster_standard_appsecret'] = $this->request->post['payment_revenuemonster_standard_appsecret'];
        } else {
            $data['payment_revenuemonster_standard_appsecret'] = $this->config->get('payment_revenuemonster_standard_appsecret');
        }
        if (isset($this->request->post['payment_revenuemonster_standard_storeid'])) {
            $data['payment_revenuemonster_standard_storeid'] = $this->request->post['payment_revenuemonster_standard_storeid'];
        } else {
            $data['payment_revenuemonster_standard_storeid'] = $this->config->get('payment_revenuemonster_standard_storeid');
        }
        if (isset($this->request->post['payment_revenuemonster_standard_privatekey'])) {
            $data['payment_revenuemonster_standard_privatekey'] = $this->request->post['payment_revenuemonster_standard_privatekey'];
        } else {
            $data['payment_revenuemonster_standard_privatekey'] = $this->config->get('payment_revenuemonster_standard_privatekey');
        }

        if (isset($this->request->post['payment_revenuemonster_standard_test'])) {
            $data['payment_revenuemonster_standard_test'] = $this->request->post['payment_revenuemonster_standard_test'];
        } else {
            $data['payment_revenuemonster_standard_test'] = $this->config->get('payment_revenuemonster_standard_test');
        }

        if (isset($this->request->post['payment_revenuemonster_standard_debug'])) {
            $data['payment_revenuemonster_standard_debug'] = $this->request->post['payment_revenuemonster_standard_debug'];
        } else {
            $data['payment_revenuemonster_standard_debug'] = $this->config->get('payment_revenuemonster_standard_debug');
        }

        if (isset($this->request->post['payment_revenuemonster_standard_total'])) {
            $data['payment_revenuemonster_standard_total'] = $this->request->post['payment_revenuemonster_standard_total'];
        } else {
            $data['payment_revenuemonster_standard_total'] = $this->config->get('payment_revenuemonster_standard_total');
        }

        if (isset($this->request->post['payment_revenuemonster_standard_success_status_id'])) {
            $data['payment_revenuemonster_standard_success_status_id'] = $this->request->post['payment_revenuemonster_standard_success_status_id'];
        } else {
            $data['payment_revenuemonster_standard_success_status_id'] = $this->config->get('payment_revenuemonster_standard_success_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_revenuemonster_standard_geo_zone_id'])) {
            $data['payment_revenuemonster_standard_geo_zone_id'] = $this->request->post['payment_revenuemonster_standard_geo_zone_id'];
        } else {
            $data['payment_revenuemonster_standard_geo_zone_id'] = $this->config->get('payment_revenuemonster_standard_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_revenuemonster_standard_status'])) {
            $data['payment_revenuemonster_standard_status'] = $this->request->post['payment_revenuemonster_standard_status'];
        } else {
            $data['payment_revenuemonster_standard_status'] = $this->config->get('payment_revenuemonster_standard_status');
        }

        if (isset($this->request->post['payment_revenuemonster_standard_sort_order'])) {
            $data['payment_revenuemonster_standard_sort_order'] = $this->request->post['payment_revenuemonster_standard_sort_order'];
        } else {
            $data['payment_revenuemonster_standard_sort_order'] = $this->config->get('payment_revenuemonster_standard_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/revenuemonster_standard', $data));
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/revenuemonster_standard')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        // if (!$this->request->post['payment_revenuemonster_standard_email']) {
        //     $this->error['email'] = $this->language->get('error_email');
        // }

        return !$this->error;
    }
}