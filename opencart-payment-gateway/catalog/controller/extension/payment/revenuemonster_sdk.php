<?php
/**
 * Plugin Name: RevenueMonster Payment Gateway
 * Description: Accept all major Malaysia e-wallet, such as TnG eWallet, Boost, Maybank QRPay & credit cards. Fast, seamless, and flexible.
 *
 * @package RevenueMonster_Payment_Gateway
 */

if ( ! function_exists( 'array_ksort' ) ) {
	function array_ksort( &$array ) {
		if ( count( $array ) > 0 ) {
			foreach ( $array as $k => $v ) {
				if ( is_array( $v ) ) {
					$array[ $k ] = array_ksort( $v );
				}
			}

			ksort( $array );
		}
		return $array;
	}
}

if ( ! function_exists( 'random_str' ) ) {
	function random_str( $length = 8, $type = 'alphanum' ) {
		switch ( $type ) {
			case 'basic':
				return mt_rand();
				break;
			case 'alpha':
			case 'alphanum':
			case 'num':
			case 'nozero':
				$seedings             = array();
				$seedings['alpha']    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$seedings['alphanum'] = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$seedings['num']      = '0123456789';
				$seedings['nozero']   = '123456789';

				$pool = $seedings[ $type ];

				$str = '';
				for ( $i = 0; $i < $length; $i++ ) {
					$str .= substr( $pool, mt_rand( 0, strlen( $pool ) - 1 ), 1 );
				}
				return $str;
				break;
			case 'unique':
			case 'md5':
				return md5( uniqid( mt_rand() ) );
				break;
		}
	}
}

if ( ! function_exists( 'escape_url' ) ) {
	function escape_url( $url = '' ) {
		$url     = parse_url( $url );
		$fulluri = '';
		if ( array_key_exists( 'scheme', $url ) ) {
			$fulluri = $fulluri . $url['scheme'] . '://';
		}
		if ( array_key_exists( 'host', $url ) ) {
			$fulluri = $fulluri . $url['host'];
		}
		if ( array_key_exists( 'path', $url ) ) {
			$fulluri = $fulluri . $url['path'];
		}
		if ( array_key_exists( 'query', $url ) ) {
			$query   = urldecode( $url['query'] );
			$fulluri = $fulluri . '?' . urlencode( $query );
		}

		return $fulluri;
	}
}

class RevenueMonster {
	private static $domains = array(
		'oauth' => 'oauth.revenuemonster.my',
		'api'   => 'open.revenuemonster.my',
	);
	private static $instance = null;
	private $client_id = '';
	private $client_secret = '';
	private $private_key = '';
	private $is_sandbox = true;
	public $access_token = '';

	private function __construct( $arguments = array() ) {
		foreach ( $arguments as $property => $argument ) {
			if ( ! property_exists( $this, $property ) ) {
				continue;
			}
			if ( gettype( $this->{$property} ) != gettype( $argument ) ) {
				continue;
			}
			$this->{$property} = $argument;
		}

		$this->oauth();
	}

	public static function get_instance( $arguments = array() ) {
		if ( null == self::$instance ) {
			self::$instance = new RevenueMonster( $arguments );
		}

		return self::$instance;
	}

	private function oauth() {
		$uri  = $this->get_open_api_url( 'v1', '/token', 'oauth' );

		$headers = array(
			"Content-Type: application/json", 
			"Authorization: Basic ".base64_encode($this->client_id.':'.$this->client_secret));
		$data = [
			"grantType" => "client_credentials"
		];
      	$response = $this->httpsCurl($uri, $headers, $data, "POST");

// print_r($response);

		if (isset($response->accessToken)) {
			$this->access_token  = $response->accessToken;
		}
	}

	public function get_domain( $usage ) {
		$domain = self::$domains['api'];
		if ( array_key_exists( $usage, self::$domains ) ) {
			$domain = self::$domains[ $usage ];
		}
		return $domain;
	}

	public function get_open_api_url( $version = 'v1', $url, $usage = 'api' ) {
		$url = trim( $url, '/' );
		$uri = "{$this->get_domain($usage)}/$version/$url";
		if ( $this->is_sandbox ) {
			$uri = "sb-$uri";
		}
		return "https://$uri";
	}

	public function get_access_token() {
		return $this->access_token;
	}

	public function get_private_key() {
		return $this->private_key;
	}

	private function call_api( $method, $url, $payload = null ) {
		$method      = strtoupper( $method );
		$access_token = $this->get_access_token();
		$nonce_str    = random_str( 32 );
		$timestamp   = time();
		$signature   = $this->generate_signature( $method, $url, $nonce_str, $timestamp, $payload );
		$headers = array(
			"Content-Type: application/json", 
			"Authorization: Bearer $access_token",
			"X-Signature: sha256 $signature", 
			"X-Nonce-Str: $nonce_str", 
			"X-Timestamp: $timestamp"
		);
		$response = $this->httpsCurl($url, $headers, $payload, $method);
		return $response;
	}

	private function httpsCurl($url, $headers, $data, $method = "POST") {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		if (is_array($data) && !empty($data)) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		$response = curl_exec($curl);
		$result = json_decode($response);
		curl_close($curl);
		return $result;
	}

	public function create_order( $payload ) {
		$response = $this->call_api(
			'POST',
			$this->get_open_api_url( 'v3', '/payment/online', 'api' ),
			$payload
		);

		if ( ! isset( $response ) ) {
			return 'empty response';
		}

		if ( isset( $response->error ) ) {
			return  $response->error->code;
		}

		return $response->item;
	}

	public function query_order( $order_id ) {
		$response = $this->call_api(
			'GET',
			$this->get_open_api_url( 'v3', "/payment/transaction/order/$order_id", 'api' )
		);

		if ( ! isset( $response ) ) {
			return 'empty response';
		}

		if ( isset( $response->error ) ) {
			return  $response->error->code;
		}

		return $response->item;
	}

	public function generate_signature( $method, $url, $nonce_str, $timestamp, $payload = null ) {
		$method   = strtolower( $method );
		$res      = openssl_pkey_get_private( $this->private_key );
		$sign_type = 'sha256';

		$arr = array();
		if ( is_array( $payload ) && ! empty( $payload ) ) {
			$data = '';
			if ( ! empty( $payload ) ) {
				array_ksort( $payload );
				$data = base64_encode( json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS ) );
			}
			array_push( $arr, "data=$data" );
		}

		array_push( $arr, "method=$method" );
		array_push( $arr, "nonceStr=$nonce_str" );
		array_push( $arr, "requestUrl=$url" );
		array_push( $arr, "signType=$sign_type" );
		array_push( $arr, "timestamp=$timestamp" );

		$signature = '';
		openssl_sign( join( '&', $arr ), $signature, $res, OPENSSL_ALGO_SHA256 );
		openssl_free_key( $res );
		$signature = base64_encode( $signature );
		return $signature;
	}
}
