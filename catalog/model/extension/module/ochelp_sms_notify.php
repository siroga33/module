<?php
class ModelExtensionModuleOchelpSmsNotify extends Model {

	public function sendServiceSms($order_id) {

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if($order_info['language_id']){
			$language_id = $order_info['language_id'];
		}else{
			$language_id = $this->config->get('config_language_id');
		}

		if ($order_info) {
			$phone = $this->prepPhone($order_info['telephone']);
			// Send Client SMS if configure
			$check_customer = $this->customerGroup($order_info);
			$sms_payments = array();

			$sms_payments = $this->config->get('sms_notify_payment');

			if ($sms_payments && in_array($order_info['payment_code'], $sms_payments)) {
				if ($this->config->get('sms_notify_payment') && $phone) {

					$payment_template = $this->config->get('sms_notify_payment_template');

					if ($check_customer) {
						$message = $this->prepareMessage($order_info, $payment_template[$order_info['payment_code']][$language_id], '', $order_info['comment']);

						$this->sendMessage($phone, $message);
					}
				}
			}else{
				if ($this->config->get('sms_notify_client_alert') && $phone) {

					$client_template = $this->config->get('sms_notify_client_template');

					if ($check_customer) {
						$message = $this->prepareMessage($order_info, $client_template[$language_id], '', $order_info['comment']);

						$this->sendMessage($phone, $message);
					}
				}
			}

			// Send Admin SMS if configure
			if ($this->config->get('sms_notify_admin_alert')) {

				$phone = $this->prepPhone($this->config->get('sms_notify_to'));

				$message = $this->prepareMessage($order_info, $this->config->get('sms_notify_admin_template'), '', $order_info['comment']);

				$this->sendMessage($phone, $message, $this->config->get('sms_notify_copy'));
			}
		}
	}

	public function sendOrderStatusSms($order_id, $order_status_id, $comment = false, $sendsms) {

		$this->load->model('checkout/order');
		//SMS send with order status
		if ($this->config->get('sms_notify_order_alert') && $this->config->get('sms_notify_order_status') && in_array($order_status_id, $this->config->get('sms_notify_order_status'))) {

			$order_info = $this->model_checkout_order->getOrder($order_id);

			if($order_info['language_id']){
				$language_id = $order_info['language_id'];
			}else{
				$language_id = $this->config->get('config_language_id');
			}

			$phone = $this->prepPhone($order_info['telephone']);

			if ($phone) {
				$sms_status_template = $this->config->get('sms_notify_status_template');

				$check_customer = $this->customerGroup($order_info);

				if ($check_customer) {
					$message = $this->prepareMessage($order_info, $sms_status_template[$order_status_id][$language_id], $order_status_id, $comment);

					$this->addOrderHistory($order_id, $order_status_id, $message);

					$this->sendMessage($phone, $message);
				}
			}
		}
	}

	public function sendReviewsSms($product_id) {

		$this->load->model('catalog/product');

		$product_data = $this->model_catalog_product->getProduct($product_id);

		if ($product_data) {

			$data['product'] = array(
				'name'  => $product_data['name'],
				'model' => $product_data['model'],
				'sku'   => $product_data['sku'],
				'date'  => date('d.m.Y H:i'),
			);

			$template = $this->config->get('sms_notify_reviews_template');

			$message = $this->renderMessage($template, $data);

			if($this->config->get('sms_notify_translit')){
				$message = $this->translit($message);
			}

			// Send Admin SMS if configure
			if ($this->config->get('sms_notify_reviews')) {
				$phone = $this->prepPhone($this->config->get('sms_notify_to'));

				$this->sendMessage($phone, $message);
			}
		}
	}

	private function prepareMessage($order_data = array(), $template, $order_status_id = false, $comment = false) {
		$this->load->model('checkout/order');

		if($order_status_id){
			$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int) $order_status_id . "' AND language_id = '" . (int) $order_data['language_id'] . "'");
		}else{
			$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int) $order_data['order_status_id'] . "' AND language_id = '" . (int) $order_data['language_id'] . "'");
		}

		if ($order_status_query->num_rows) {
			$order_status = $order_status_query->row['name'];
		} else {
			$order_status = false;
		}

		$shipping_cost = 0;
		$order_total_noship = 0;

		if ($this->config->get('shipping_status')) {
			$order_shipping_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int) $order_data['order_id'] . "' AND code = 'shipping'");

			if ($order_shipping_query->num_rows) {
				$shipping_cost = $this->currency->format($order_shipping_query->row['value'], $order_data['currency_code'], $order_data['currency_value']);
				$order_total_noship = $order_data['total'] ? $order_data['total'] - $order_shipping_query->row['value'] : '';
			}
		}

		$query_order_product_total = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int) $order_data['order_id'] . "'");

		$query_order_product = $this->db->query("SELECT name, model, price, quantity FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int) $order_data['order_id'] . "'");

		$products = array();

		foreach ($query_order_product->rows as $product) {
			$products[] = array(
				'name'     => $product['name'],
				'model'    => $product['model'],
				'price'    => $this->currency->format($product['price'], $order_data['currency_code'], $order_data['currency_value']),
				'quantity' => $product['quantity']
			);
		}

		$data['order_date'] = $order_data['date_added'] ? $order_data['date_added'] : '';
		$data['current_date'] = date('d.m.Y');
		$data['current_time'] = date('H:i');
		$data['store_name'] = $order_data['store_name'] ? $order_data['store_name'] : $this->config->get('config_name');
		$data['store_url'] = $order_data['store_url'] ? $order_data['store_url'] : HTTP_SERVER;
		$data['firstname'] = $order_data['firstname'] ? $order_data['firstname'] : '';
		$data['lastname']  = $order_data['lastname'] ? $order_data['lastname'] : '';
		$data['order_id']  = $order_data['order_id'] ? $order_data['order_id'] : '';
		$data['order_total'] = $order_data['total'] ? $this->currency->format($order_data['total'], $order_data['currency_code'], $order_data['currency_value']) : '';
		$data['order_total_noship'] = $order_total_noship ? $this->currency->format($order_total_noship, $order_data['currency_code'], $order_data['currency_value']) : '';
		$data['order_phone'] = $order_data['telephone'] ? $order_data['telephone'] : '';
		$data['order_comment']  = $comment;
		$data['order_status'] = $order_status;
		$data['payment_method']  = $order_data['payment_method'] ? $order_data['payment_method'] : '';
		$data['payment_city'] = $order_data['payment_city'] ? $order_data['payment_city'] : '';
		$data['payment_address'] = $order_data['payment_address_1'] ? $order_data['payment_address_1'] : '';
		$data['shipping_method'] = $order_data['shipping_method'] ? $order_data['shipping_method'] : '';
		$data['shipping_cost']  = $shipping_cost;
		$data['shipping_city']  = $order_data['shipping_city'] ? $order_data['shipping_city'] : '';
		$data['shipping_address'] = $order_data['shipping_address_1'];
		$data['product_total']  = $query_order_product_total->row['total'];
		$data['products']  = $products;

		$result = $this->renderMessage($template, $data);

		if($this->config->get('sms_notify_translit')){
			return $this->translit($result);
		}else{
			return $result;
		}
	}

	private function customerGroup($order){
		$this->load->model('account/customer');

		$sms_customer_group = $this->config->get('sms_notify_customer_group');
		$customer_group_id = $this->config->get('config_customer_group_id');

		$customer = $this->model_account_customer->getCustomer($order['customer_id']);

		if($sms_customer_group){
			if($this->customer->isLogged() && in_array($customer['customer_group_id'], $sms_customer_group)){
				$result = true;
			}else if(in_array($customer_group_id, $sms_customer_group)){
				$result = true;
			}else{
				$result = false;
			}
		}else{
			$result = true;
		}

		return $result;
	}

	private function addOrderHistory($order_id, $order_status_id, $message){
		$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '1', comment = '" . $this->db->escape($message) . "', date_added = NOW()");
	}

	private function translit($message) {
		$scheme = array(
			'а' => 'a',   'б' => 'b',   'в' => 'v',
			'г' => 'g',   'д' => 'd',   'е' => 'e',    'є' => 'ye',
			'ё' => 'yo',   'ж' => 'zh',  'з' => 'z',   'і' => 'i',
			'и' => 'i',   'й' => 'j',   'к' => 'k',   'ї' => 'yi',
			'л' => 'l',   'м' => 'm',   'н' => 'n',
			'о' => 'o',   'п' => 'p',   'р' => 'r',
			'с' => 's',   'т' => 't',   'у' => 'u',
			'ф' => 'f',   'х' => 'kh',   'ц' => 'ts',
			'ч' => 'ch',  'ш' => 'sh',  'щ' => 'shch',
			'ь' => '\'',  'ы' => 'y',   'ъ' => '"',
			'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

			'А' => 'A',   'Б' => 'B',   'В' => 'V',
			'Г' => 'G',   'Д' => 'D',   'Е' => 'E',   'Є' => 'Ye',
			'Ё' => 'Yo',   'Ж' => 'Zh',  'З' => 'Z',   'І' => 'I',
			'И' => 'I',   'Й' => 'J',   'К' => 'K',   'Ї' => 'Yi',
			'Л' => 'L',   'М' => 'M',   'Н' => 'N',
			'О' => 'O',   'П' => 'P',   'Р' => 'R',
			'С' => 'S',   'Т' => 'T',   'У' => 'U',
			'Ф' => 'F',   'Х' => 'Kh',   'Ц' => 'Ts',
			'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Shch',
			'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '"',
			'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
		);

		$result = strtr($message, $scheme);

		return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $result);
	}

    private function prepPhone($phone) {

        $result = preg_replace('/[^0-9,]/', '', $phone);

        return $result;
    }

    private function renderMessage($template, $data) {
		include_once(DIR_SYSTEM . 'library/template/Twig/Autoloader.php');

		Twig_Autoloader::register();

		$twig = new \Twig_Environment(new Twig_Loader_String());

		return $twig->render($template, $data);
    }

    private function sendMessage($phone, $message, $copy = false) {
		$options = array(
			'to'       => $phone,
			'copy'	   => $copy,
			'from'     => $this->config->get('sms_notify_from'),
			'username' => $this->config->get('sms_notify_gate_username'),
			'password' => $this->config->get('sms_notify_gate_password'),
			'message'  => $message,
		);

		$sms = new Sms($this->config->get('sms_notify_gatename'), $options);
		$sms->send();
    }
}