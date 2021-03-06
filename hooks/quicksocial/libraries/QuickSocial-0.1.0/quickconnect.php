<?php
/**
 * @version      $Id$
 * @package      QuickSocial.Library
 * @subpackage   QuickConnect
 * @copyright    Copyright (C) 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org/quicksocial/
 * @license      GNU Lesser General Public License (LGPL) version 3.0
 */

if ( !class_exists ( "cQuickSocial" ) ) require ( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'quicksocial.php' );

/** QuickConnect Class
 * 
 * User remote login and connection class
 * 
 * @package     QuickSocial.Framework
 * @subpackage  QuickConnect
 */
class cQuickConnect extends cQuickSocial {

	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       
		parent::__construct();
	}
	
	public function Redirect ( $pTarget, $pUser, $pMethod = "http", $pReturnTo = null) {
		
		$request['_social'] = "true";
		$request['_task'] = "connect.check";
		
		$request['_username'] = $pUser;
		$request['_source'] = QUICKSOCIAL_DOMAIN;
		
		$request['_return'] = $pReturnTo ? $pReturnTo : QUICKSOCIAL_DOMAIN;
		
		$request['_return'] = base64_encode ( $request['_return'] );
		
		$request['_method'] = $pMethod;
		
		switch ( $pMethod ) {
			case 'https':
				$http = 'https://';
			break;
			default:
				$http = 'http://';
			break;
		}
		
		$redirect = $http . $pTarget . '/?' . http_build_query ( $request );
		
		header('Location: ' . $redirect);
		
		exit;
	}
	
	public function Check ( ) {
		
		$social = $this->_GET['_social'];
		$task = $this->_GET['_task'];
		
		if ( $social != "true" ) return ( false );
		if ( $task != "connect.check" ) return ( false );
		
		$source = $this->_GET['_source'];
		$username = $this->_GET['_username'];
		
		$method = $this->_GET['_method'];
		
		$returnTo = isset ( $this->_GET['_return'] ) ? $this->_GET['_return'] : $source;
		
		switch ( $method ) {
			case 'https':
				$http = 'https://';
			break;
			default:
				$http = 'http://';
			break;
		}
		
		$fCheckLogin = $this->GetCallBack ( "CheckLogin" );
		$fCreateLocalToken = $this->GetCallBack ( "CreateLocalToken" );
		
		// If either of the callbacks don't exist, redirect back with an error.
		if ( ( !is_callable ( $fCheckLogin ) ) OR ( !is_callable ( $fCreateLocalToken ) ) ) {
		
			$request['_social'] = "true";
			$request['_task'] = "connect.return";
			$request['_success'] = "false";
			$request['_error'] = "Invalid Callback";
			
			$redirect = $http . $source . '/?' . http_build_query ( $request );
			
			header('Location: ' . $redirect);
			exit;
		}
		
		// 1. Check login
		$loggedIn = @call_user_func ( $fCheckLogin, $username );
		
		// 2. Store identifier
		
		$request['_social'] = "true";
		$request['_task'] = "connect.return";
		
		if ( $loggedIn ) {
			$token = @call_user_func ( $fCreateLocalToken, $username, $source );
			
			if ( !$token ) {
				$request['_success'] = "false";
				$request['_error'] = "Token Not Stored";
				
			} else {
				$request['_success'] = "true";
				$request['_error'] = "";
				
				$request['_return'] = $returnTo;
				
				$request['_username'] = $username;
				$request['_source'] = QUICKSOCIAL_DOMAIN;
		
				$request['_token'] = $token;
			}
		} else {
			$request['_username'] = $username;
			$request['_source'] = QUICKSOCIAL_DOMAIN;
		
			$request['_success'] = "false";
			$request['_error'] = "Not Logged In To Node";
		}
		
		$redirect = $http . $source . '/?' . http_build_query ( $request );
		
		// 3. Redirect back
		header('Location: ' . $redirect);
		exit;
	}
	
	public function Process ( ) {
		
		$success = $this->_GET['_success'];
		$error = $this->_GET['_error'];
		$return = new stdClass ();
		
		$return->success = $success;
		$return->error = $error;
		
		$source = $this->_GET['_source'];
		$username = $this->_GET['_username'];
		
		$returnTo = $this->_GET['_return'];
		
		if ( $success != "true" ) {
			$return->username = $username;
			$return->domain = $source;
			
			$return->success = 'false';
			$return->error = $this->_GET['_error'];
			
			return ( $return );
		}
					
		$social = $this->_GET['_social'];
		$task = $this->_GET['_task'];
		
		$fCreateRemoteToken = $this->GetCallBack ( "CreateRemoteToken" );
		
		if ( !is_callable ( $fCreateRemoteToken ) ) return ( false );
		
		if ( $social != "true" ) return ( false );
		if ( $task != "connect.return" ) return ( false );
		
		$token = $this->_GET['_token'];
		
		$verification = $this->Verify( $username, $source, $token );
		
		if ( $verification->success == "true" ) {
			$stored = @call_user_func ( $fCreateRemoteToken, $username, $source, $token );
		
			$return->username = $username;
			$return->domain = $source;
			
			$return->returnTo = base64_decode ( $returnTo );
			
			return ( $return );
		} else {
			return ( $verification );
		}
	}
	
}
