<?php
class ControllerExtensionModuleOchelpSmsNotify extends Controller {
	public function order(&$route, &$args) {
		if (isset($args[0])) {
			$order_id = $args[0];
		} else {
			$order_id = 0;
		}

		if (isset($args[1])) {
			$order_status_id = $args[1];
		} else {
			$order_status_id = 0;
		}	

		if (isset($args[2])) {
			$comment = $args[2];
		} else {
			$comment = '';
		}	

		if (isset($args[5])) {
			$sendsms = $args[5];
		} else {
			$sendsms = 0;
		}

		if (isset($args[6])) {
			$admin_order = $args[6];
		} else {
			$admin_order = 0;
		}

		$this->load->model('extension/module/ochelp_sms_notify');
		$this->load->model('checkout/order');
						
		// We need to grab the old order status ID
		$order_info = $this->model_checkout_order->getOrder($order_id);
		
		if ($order_info) {
            // Send SMS if configure
            if(!$order_info['order_status_id'] && !$admin_order && !$sendsms) {
                $this->model_extension_module_ochelp_sms_notify->sendServiceSms($order_info['order_id']);
            } 

            // Send SMS for Order Status
            if($order_status_id && $admin_order && $sendsms) {
                $this->model_extension_module_ochelp_sms_notify->sendOrderStatusSms($order_id, $order_status_id, $comment, $sendsms);
            }elseif($order_status_id && !$admin_order && $this->config->get('sms_notify_force')){
                $this->model_extension_module_ochelp_sms_notify->sendOrderStatusSms($order_info['order_id'], $order_status_id, $comment, true);
            } 	
		}
	}

	public function review(&$route, &$args) {
		if (isset($args[0])) {
			$product_id = $args[0];
		} else {
			$product_id = 0;
		}
		
		$this->load->model('extension/module/ochelp_sms_notify');

		$this->model_extension_module_ochelp_sms_notify->sendReviewsSms($product_id);

	}
}