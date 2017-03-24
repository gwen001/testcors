<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class HttpRequest
{
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_HEAD = 'HEAD';
	const METHOD_OPTIONS = 'OPTIONS';

	const DEFAULT_METHOD = self::METHOD_GET;
	const DEFAULT_HTTP = 'HTTP/1.1';

	protected $request_file = null;

	protected $host = '';

	protected $ssl = false;

	protected $redirect = true;

	protected $method = self::DEFAULT_METHOD;

	protected $http = self::DEFAULT_HTTP;

	protected $url = '';

	protected $headers = array();

	protected $cookies = '';
	protected $cookie_file = '';

	protected $params = '';

	protected $multipart = false;

	protected $content_length = false;

	protected $result = '';
	protected $result_body = '';
	protected $result_header = '';
	protected $result_headers = [];
	protected $result_cookies = [];
	protected $result_length = 0;
	protected $result_code = 0;


	public function __construct() {
		$this->cookie_file = tempnam('/tmp', 'cook_');
	}

	public function __clone() {
		$this->result = '';
		$this->result_length = 0;
		$this->result_code = 0;
	}


	public function getResult() {
		return $this->result;
	}

	public function getResultBody() {
		return $this->result_body;
	}

	public function getResultLength() {
		return $this->result_length;
	}

	public function getResultCode() {
		return $this->result_code;
	}

	public function getResultCookie( $key ) {
		if( isset($this->result_cookies[$key]) ) {
			return $this->result_cookies[$key];
		} else {
			return false;
		}
	}
	public function getResultCookies() {
		return $this->result_cookies;
	}

	public function getResultHeader() {
		return $this->result_header;
	}
	public function getResultHeaders( $key='' ) {
		if( $key != '' ) {
			if( isset($this->result_headers[$key]) ) {
				return $this->result_headers[$key];
			} else {
				return false;
			}
		}
		return $this->result_headers;
	}
	public function addResultHeader( $v, $k ) {
		$this->result_headers[$k] = $v;
	}


	public function getRequestFile() {
		return $this->request_file;
	}
	public function setRequestFile( $v ) {
		if( is_file($v) ) {
			$this->request_file = $v;
			return true;
		} else {
			return false;
		}
	}


	public function getHost() {
		return $this->host;
	}
	public function setHost( $v ) {
		$this->host = $v;
		return true;
	}


	public function getRedirect() {
		return $this->redirect;
	}
	public function setRedirect( $v ) {
		$this->redirect = (bool)$v;
		return true;
	}


	public function getSsl() {
		return $this->ssl;
	}
	public function setSsl( $v ) {
		$this->ssl = (bool)$v;
		return true;
	}


	public function isMultipart() {
		return $this->multipart;
	}
	public function setMultipart( $v ) {
		$this->multipart = (bool)$v;
		return true;
	}


	public function getContentLength() {
		return $this->content_length;
	}
	public function setContentLength( $v ) {
		$this->content_length = (bool)$v;
		return true;
	}


	public function getUrl( $base64=false ) {
		$v = $this->url;
		if( $base64 ) {
			$v = base64_encode( serialize($v) );
		}
		return $v;
	}
	public function setUrl($v) {
		$this->url = $v;
	}


	public function getMethod() {
		return $this->method;
	}
	public function setMethod($v) {
		$this->method = strtoupper($v);
	}


	public function getHttp() {
		return $this->http;
	}
	public function setHttp($v) {
		$this->http = $v;
	}


	public function getHeaders( $base64=false ) {
		$v = $this->headers;
		if( $base64 ) {
			$v = base64_encode( serialize($v) );
		}
		return $v;
	}
	public function setHeaders($array) {
		foreach ($array as $k => $v) {
			$this->setHeader($v, $k);
		}
	}

	public function getHeader( $key, $base64=false ) {
		$v = $this->headers[$key];
		if( $base64 ) {
			$v = base64_encode( $v );
		}
		return $v;
	}
	public function setHeader($v, $key) {
		$this->headers[$key] = $key.': '.$v;
	}


	public function getCookies( $base64=false ) {
		$v = $this->cookies;
		if( $base64 ) {
			$v = base64_encode( $v );
		}
		return $v;
	}
	public function setCookies($v) {
		$this->cookies = $v;
	}


	public function getParams( $base64=false )
	{
		$v = $this->params;
		if( $base64 ) {
			$v = base64_encode( $v );
		}
		return $v;
	}
	public function setParams($v)
	{
		$this->params = $v;
	}


	public function isPost() {
		return ($this->method==self::METHOD_POST);
	}


	public function request()
	{
		$surplace = array();
		$url = ($this->ssl?'https://':'http://') . $this->host . $this->url;
		//var_dump( $url );
		
		$c = curl_init();
		curl_setopt($c, CURLOPT_CUSTOMREQUEST, $this->method);
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTP_VERSION, $this->http);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HEADER, true);
		curl_setopt($c, CURLOPT_TIMEOUT, 5);
		//curl_setopt($c, CURLOPT_PROXY, '127.0.0.1:9050');
		//curl_setopt($c, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
		if( $this->redirect ) {
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		}
		if( strlen($this->cookies) ) {
			curl_setopt($c, CURLOPT_COOKIE, $this->cookies);
		}
		curl_setopt($c, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($c, CURLOPT_COOKIEFILE, $this->cookie_file);
		if( strlen($this->params) ) {
			if( $this->content_length ) {
				// this header seems to fuck the request...
				//$surplace['Content-Length'] = 'Content-Length: '.strlen( $this->params );
				// but this works great!
				$surplace['Content-Length'] = 'Content-Length: 0';
			}
			if( $this->isPost() ) {
				curl_setopt($c, CURLOPT_POST, true);
				curl_setopt($c, CURLOPT_POSTFIELDS, $this->params);
			}
		}
		curl_setopt($c, CURLOPT_HTTPHEADER, array_merge($this->headers,$surplace));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		$this->result = curl_exec($c);
		//var_dump($this->result);
		$this->result_length = strlen($this->result);
		$this->result_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		
		$this->result_header_size = curl_getinfo( $c, CURLINFO_HEADER_SIZE );
		$this->result_header = $this->extractHeader();
		$this->result_body = $this->extractBody();
		//$this->result_cookies = $this->extractCookies();

		return $this->result_code;
	}

	
	private function extractHeader()
	{
		$result_header = trim( substr($this->result, 0, $this->result_header_size) );
		//var_dump($result_header);
		
		$t_headers = explode( "\n", $result_header );
		
		foreach( $t_headers as $h ) {
			if( strstr($h,':') ) {
				$tmp = explode( ':', trim($h) );
				$key = array_shift( $tmp );
				$value = trim( implode(':',$tmp) );
				$this->addResultHeader( trim(implode(':',$tmp)), trim($key) );
			}
		}

		return $result_header;
	}
	
	
	private function extractBody()
	{
		$this->result_body = trim( substr($this->result, $this->result_header_size) );
	}
	
	
	private function extractCookies()
	{
		/*preg_match_all( '/^Set-Cookie:\s*([^;]*)/mi', $this->result_header, $matches );
		
		$t_cookies = array();
		
		foreach( $matches[1] as $item ) {
		    parse_str( $item, $cookie );
			$this->addResultCookie( trim($tmp[1]), trim($tmp[0]) );
		    $t_cookies = array_merge( $t_cookies, $cookie );
		}
		
		return $t_cookies;*/
	}

	
	public function loadFile( $file )
	{
		if( !$this->setRequestFile($file) ) {
			return false;
		}

		$request = trim( file_get_contents($file) ); // the full request
		$request = str_replace( "\r", "", $request );
		$t_request = explode( "\n\n", $request ); // separate headers and post parameters
		$t_headers = explode( "\n", array_shift($t_request) ); // headers
		$h_request = array_map( function($str){return explode(':',trim($str));}, $t_headers ); // splited headers
		array_shift( $h_request );

		$first = array_shift( $t_headers ); // first ligne is: method, url, http version
		list($method,$url,$http) = explode( ' ', $first );

		$params = ''; // post parameters
		if( count($t_request) ) {
			$params = implode( "\n\n", $t_request );
		}

		$host = '';
		$cookies = '';
		$h_replay = array(); // headers kept in the replay request

		foreach( $h_request as $header )
		{
			$h = trim( array_shift($header) );
			$v = trim( implode(':',$header) );

			switch( $h )
			{
				case 'Accept-Encoding':
				case 'Content-Length':
					break;

				case 'Cookie':
					$cookies = $h.': '.$v;
					break;

				case 'Host':
					$host = $v;
					break;

				/*case 'Accept':
				case 'Accept-Language':
				case 'Connection':
				case 'Referer':
				case 'User-Agent':
				case 'x-ajax-replace':
				case 'X-Requested-With':*/
				case 'Content-Type':
					if( stristr($v,'multipart') !== false ) {
						$this->setMultipart( true );
					}
				default:
					$h_replay[ $h ] = $h.': '.$v;
					break;
			}
		}

		$this->setHost( $host );
		$this->setUrl( $url );
		$this->setMethod( $method );
		$this->setHttp( $http );
		$this->setHeaders( $h_replay );
		$this->setCookies( $cookies );
		$this->setParams( $params );

		return true;
	}


	public function export( $echo=true )
	{
		$output = '';
		$output .= $this->method.' '.preg_replace('#http[s?]://#','',$this->url).' '.$this->http."\n";
		$output .= 'Host: '.$this->host."\n";
		foreach( $this->headers as $h ) {
			$output .= $h."\n";
		}
		$output .= $this->cookies."\n\n";
		$output .= $this->params."\n";

		if( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}
}

?>
