<?php
/**
 * @version      $Id$
 * @package      Appleseed.Components
 * @subpackage   Privacy
 * @copyright    Copyright (C) 2004 - 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org
 * @license      GNU General Public License version 2.0 (See LICENSE.txt)
 */

// Restrict direct access
defined( 'APPLESEED' ) or die( 'Direct Access Denied' );

/** Privacy Component
 * 
 * Privacy Component Entry Class
 * 
 * @package     Appleseed.Components
 * @subpackage  Privacy
 */
class cPrivacy extends cComponent {
	
	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       
		parent::__construct();
	}
	
	function Store ( $pData ) {
		$Privacy = $pData['Privacy'];
		$Identifier = $pData['Identifier'];
		$Type = $pData['Type'];
		
		// Nobody was selected, so disregard any other settings.
		if ( (bool) $Privacy['nobody'] == true ) return ( true );
		
		$this->_Focus = $this->Talk ( 'User', 'Focus' );
		
		$Everybody = (bool) $Privacy['everybody'];
		$Friends = (bool) $Privacy['friends'];
		
		unset ( $Privacy['everybody'] );
		unset ( $Privacy['friends'] );
		
		include ( ASD_PATH . 'components/privacy/models/privacy.php' );
		$Model = new cPrivacyModel();
		
		if ( count ( $Privacy ) > 0 ) {
			// One or more circles was selected, so preference them.
			$circles = $this->Talk ( 'Friends', 'Circles' );
			$circles = array_flip ( $circles );
			foreach ( $Privacy as $circle => $on ) {
				$id = $circles[$circle];
				$Model->Store ( $id, $Type, $Identifier, $this->_Focus->Id );
			}
		} else if ( $Friends ) {
			// Friends Only
			$Model->Store ( null, $Type, $Identifier, $this->_Focus->Id, false, true );
		} else if ( $Everybody ) {
			// Public
			$Model->Store ( null, $Type, $Identifier, $this->_Focus->Id, true, false );
		} else {
			// No privacy data was given, so assume nobody
			return ( true );
		}
		
		return ( true );
	}
	
}