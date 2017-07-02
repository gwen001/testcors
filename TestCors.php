<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class TestCors
{
	const T_PAYLOADS = array(
		'',
		'null',
		'__PAYLOAD__',
		'www.__PAYLOAD__.net',
		'http://www.__PAYLOAD__.net',
		'https://www.__PAYLOAD__.net',
		'http://__T_HOST__.__PAYLOAD__.net',
		'https://__T_HOST__.__PAYLOAD__.net',
		'74.6.50.24',
		'http://74.6.50.24',
		'https://74.6.50.24',
	);

	/**
	 * @var string
	 *
	 * protocol used
	 */
	private $protocol = 'http';

	/**
	 * @var string
	 *
	 * host to test
	 */
	private $host = null;

	/**
	 * @var string
	 *
	 * hostS to test
	 */
	private $input_file = null;
	
	private $redirect = true;
	
	private $ssl = false;

	/**
	 * @var array
	 *
	 * payloads table
	 */
	private $t_payloads = null;

	
	public function getProtocol() {
		return $this->protocol;
	}
	public function setProtocol( $v ) {
		$this->protocol = trim( $v );
		if( $this->protocol == 'https' ) {
			$this->ssl = true;
		}
		return true;
	}

	
	public function getHost() {
		if( $this->input_file ) {
			return $this->input_file;
		} else {
			return $this->host;
		}
	}
	public function setHost( $v ) {
		$v = trim( $v );
		if( is_file($v) ) {
			$this->input_file = $v;
		} else {
			$this->host = $v;
		}
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

	
	public function getPayloads() {
		return $this->t_payloads;
	}
	public function addPayload( $p )
	{
		$this->t_payloads[] = $p;
		return true;
	}

	
	public function run()
	{
		if( $this->input_file ) {
			echo "Loading data file...\n";
			$t_host = file( $this->input_file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES );
		} else {
            $t_host = [ $this->host ];
        }

		echo "\n";
		
		$n_payloads = $this->preparePayloads();
		if( !$n_payloads ) {
			exit( "No payloads configured!\n" );
		}

		echo "Testing ".$n_payloads." payloads on ".count($t_host)." host...\n\n";

		foreach( $t_host as $h )
		{
			echo 'Testing '.$h."\n";
			
			//$domain = Utils::extractDomain( $h );
			//var_dump( $domain );

			foreach( $this->getPayloads() as $p )
			{
				$cors = 0;
				$p = str_replace( '__T_HOST__', $h, $p );
				//echo $p."\n";
				
				$r = new HttpRequest();
				$r->setRedirect( $this->redirect );
				$r->setSsl( $this->ssl );
				$r->setHost( $h );
				//$r->setMethod( HttpRequest::METHOD_POST );
				$r->setUrl( '/' );
				$r->setHeader( $p, 'Origin' );
				//var_dump( $r );
				
				$result_code = $r->request();
	
				if( $result_code == 0 ) 
				{
					$cors = -1;
					$origin = '';
					$credentials = '';
					$this->result( $h, $p, $origin, $credentials, $cors );
					break;
				}
				else
				{
					$origin = $r->getResultHeaders( 'Access-Control-Allow-Origin' );
					$credentials = $r->getResultHeaders( 'Access-Control-Allow-Credentials' );
					//var_dump( $origin );
					//var_dump( $credentials );
					
					if( $origin )
					{
						if( $origin == '' ) {
							$cors = 1; // suspicious
						}
						elseif( $origin == '*' ) {
							$cors = 2; // suspicious
						}
						else {
							$r1 = preg_match('#\(?null\)?#i',$origin);
							$r2 = preg_match('#\(?'.$p.'\)?#i',$origin);
							$r3 = preg_match('#\(?true\)?#i',$credentials);
							$r4 = preg_match('#http[s]?://#i',$origin);
							/*var_dump( $r1 );
							var_dump( $r2 );
							var_dump( $r3 );
							var_dump( $r4 );*/
							
							if( ($r1 || ($p!=''&&$r2)) && $r3 ) {
								$cors = 3; // vulnerable
							} elseif( $r4 ) {
								$cors = 4; // to check
							}
						}
					}
					
					/*
					if( !$credentials && $origin ) {
						$cors = 1; // suspicious
					}
					
					if( $origin == '*' ) {
						$cors = 2; // suspicious						
					}
					else {
						$r1 = preg_match('#\(?null\)?#i',$origin);
						$r2 = preg_match('#\(?'.$p.'\)?#i',$origin);
						$r3 = preg_match('#\(?true\)?#i',$credentials);
						//var_dump( $r1 );
						//var_dump( $r2 );
						//var_dump( $r3 );
						
						if( $origin!='' && ($r1 || $r2) && $r3 ) {
							$cors = 3; // vulnerable
						}
					}
					*/
				}
				
				//var_dump( $cors );
				$this->result( $h, $p, $origin, $credentials, $cors );
			}

			echo "\n";
		}
	}
	

	private function preparePayloads()
	{
		$uniqid = uniqid();
		
		foreach( self::T_PAYLOADS as $p ) {
			$p = str_replace( '__PAYLOAD__', $uniqid, $p );
			$this->t_payloads[] = $p;
		}

		$n_payloads = count( $this->t_payloads );

		return $n_payloads;
	}
	
	
	private function result( $host, $payload, $origin, $credentials, $cors )
	{
		echo "Payload: '".$payload."' => ";
		echo 'Origin: '.(($origin===false)?'<not found>':$origin).', Credentials: '.(($credentials===false)?'<not found>':$credentials).' => ';

		switch( $cors )
		{
			case -1:
				$txt = 'DOWN';
				$color = 'purple';
				break;
			case 1:
				$txt = 'SUSPICIOUS';
				$color = 'orange';
				break;
			case 2:
				$txt = 'NOT EXPLOITABLE';
				$color = 'orange';
				break;
			case 3:
				$txt = 'VULNERABLE';
				$color = 'red';
				break;
			case 4:
				$txt = 'CONFIGURED';
				$color = 'yellow';
				break;
			default:
				$txt = 'SAFE';
				$color = 'green';
				break;
		}

		Utils::_print( $txt, $color );
		echo "\n";
	}
}

?>
