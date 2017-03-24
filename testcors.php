#!/usr/bin/php
<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

function __autoload( $c ) {
	include( $c.'.php' );
}


// parse command line
{
	$testcors = new TestCors();

	$argc = $_SERVER['argc'] - 1;

	for ($i = 1; $i <= $argc; $i++) {
		switch ($_SERVER['argv'][$i]) {
			case '-f':
				$testcors->setHost( $_SERVER['argv'][$i + 1] );
				$i++;
				break;

			case '-h':
				Utils::help();
				break;

			case '-o':
				$testcors->setHost($_SERVER['argv'][$i + 1]);
				$i++;
				break;

			case '-p':
				$testcors->setProtocol($_SERVER['argv'][$i + 1]);
				$i++;
				break;

			case '-r':
				$testcors->setRedirect( false );
				break;

			case '-s':
				$testcors->setSsl( true );
				break;

			default:
				Utils::help('Unknown option: '.$_SERVER['argv'][$i]);
		}
	}

	if( !$testcors->getHost() ) {
		Utils::help('Host not found!');
	}
}
// ---


// main loop
{
	$testcors->run();
}
// ---


exit();

?>
