<?php

class CommPortal {
	
/**
*  CommPortal - PHP library for MetaSwitch EAS CommPortal
*  version 1.0.1
*  https://github.com/zgr024/CommPortal
*  Copyright (C) 2014 Zachary Rosenberg
*  please submit problem or error reports to https://github.com/zgr024/CommPortal/issues
*
*  All rights reserved.
*  Permission is hereby granted, free of charge, to use, copy or modify this software.  Use at your own risk.
*
*/

	public	$_loginStatus 		= 1,
		$_session,
		$dn,
		$calls,
		$debug			= false															// Turn on/off debugging
	;

	const 	url 			= 'http://eas.sandbox.innovators.metaswitch.com/unbranded/',
		admin_key 		= 'AdminType',
		admin_val 		= 'admin1',
		dn_key			= 'DirectoryNumber',
		cert_key		= 'TokenKeyId',
		valid_key		= 'ValidTo',
		version_key		= 'version',
		client_version 		= '8.1',
		cert			= 'CERT',
		keyfile			= '/path/to/dsaprivkey.pem',
		keypwd  		= 'PWD',
		duration		= 600000,
		offset			= 3600000 		
			
	;
	
	
	/**
	 * Initialize object with a directory number and password, session, or directory number only (SSO)
	 *
	 * Usage:
	 *		$cp = new CommPortal(2012032040);
	 *
	 *		// Using magic method
	 *		foreach ($cp->Voicemails as $vm) {
	 *	 		print_r($vm);
	 * 		}
	 *
	 *		// Using traditional method
	 *		$voicemails = $cp->getVoicemails();
	 *		foreach ($vicemails as $vm) {
	 *	 		print_r($vm);
	 * 		}
	 *		
	 *		$busyForwarding = $cp->getCallForwarding('Busy');
	 *
	 * Note: To use SSO you must install a certificate on the EAS server and create a private key file on your web server
	 */	
	 
	function __construct($a,$b=NULL,$c=false) 
	{		
		if ($b===true||$c===true) $this->debug = true;
		
		$this->login($a,$b);
	}
	

/*
 *
 * LOGIN AND MAGIC GET METHOD
 *
 */
	
	
	/**
	 * Logs in to the CommPortal Server
	 */
	private function login($a,$b=NULL)
	{
		if (strlen((string) $a) == 10) {
			
			if (!isset($b) || is_bool($b)) {
				// Login with directory number and SSO
				$privateKey = openssl_pkey_get_private("file://".self::keyfile, self::keypwd);
					
				$params = array(
					//self::admin_key   => self::admin_val,
					self::dn_key      => $a,
					self::cert_key    => self::cert,
					self::valid_key   => bcdiv(microtime(true) * 1000, 1, 0) + self::duration + self::offset,
					self::version_key => self::client_version,
				);
			
				foreach ($params as $k => $v) {
					$plainToken .= (isset($plainToken) ? "&" : "") . urlencode($k) . "=" . urlencode($v);
				}
				
				openssl_sign($plainToken, $signedToken, $privateKey, OPENSSL_ALGO_DSS1);
				
				$signedToken = urlencode(base64_encode($signedToken));
			
				$url = self::url."login?Token=$signedToken&$plainToken";
			
				$session = NULL;
				
				parse_str($this->getData($url));
				
				if ($session) {
					$this->_session = $session;
					$this->dn = $a;
					$this->_loginStatus = 1;
					if ($this->debug === true) echo '<div class="cp_debug">Login With SSO Successful</div>';
				}
				else {
					if ($this->debug === true) echo '<div class="cp_debug">Not Logged In</div>';
					$this->_loginStatus = 0;
				}
				
			}				
			else {
				// Login with username and password
				$url = self::url."login?version=".self::client_version;
				$data = "DirectoryNumber={$a}&Password={$b}";
				parse_str($this->postData($url,$data,'application/x-www-form-urlencoded'));
				if ($session) {
					$this->_session = $session;
					$this->dn = $a;
					$this->_loginStatus = 1;
					if ($this->debug === true) echo '<div class="cp_debug">Login With DN Successful</div>';
				}
				else {
					if ($this->debug === true) echo '<div class="cp_debug">Not Logged In With DN</div>';
					$this->_loginStatus = 0;
				}
			}
		}
		else {
			// Use existing session
			$this->_session = $a;
			$this->customer = $this->getCustomer();
			if ($this->customer->data[0]->errors[0]->type == 'sessionExpired') {
				$this->_loginStatus = 0;
				if ($this->debug === true) echo '<div class="cp_debug">Session Expired</div>';
			}
			else if (!$this->customer->data[0]->data) {
				$this->_loginStatus = 0;
				if ($this->debug === true) echo '<div class="cp_debug">Invalid Session</div>';
			}
			else {
				$this->_loginStatus = 1;
				if ($this->debug === true) echo '<div class="cp_debug">Login With Session Successful</div>';
			}
		}
	}
	

 	public function __get($name)
	{
		$method = "get".$name;
		if (isset($this->$method)) {
			if ($this->debug===true) echo '<div class="cp_debug">Sent from reference</div>';
			return $this->$method;
		}
		if (method_exists ($this,$method)) {
			if ($this->debug===true) echo '<div class="cp_debug">Sent from method</div>';
			$this->$method = $this->$method();
			return $this->$method;
		}
		
		$trace = debug_backtrace();
        trigger_error(
            'Undefined method via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE
		);
	}
 
 
 
/**
 *
 * SUBSCRIBER INFORMATION
 *
 */
 
 
 
 	/*
	 * Gets the Class of Service for the current subscriber
	 */
	public function getClassOfService()
	{
		$dataType = 'Meta_Subscriber_MetaSphere_ClassOfService';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));			
		return $data->data[0]->data;
	}
 
 
 	/**
	 * Gets the customer information for the subscriber
	 */		
	public function getCustomer()
	{
		$dataType = 'Meta_Subscriber_CustomerInformation';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		$this->dn = $data->data[0]->objectIdentity->line;
		return $data->data[0]->data;
	}
	
	/**
	 * Gets the customer information for the subscriber
	 */		
	public function getAccountInfo()
	{
		$dataType = 'Meta_Subscriber_BaseInformation';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		$this->dn = $data->data[0]->objectIdentity->line;
		return $data->data[0]->data;
	}
	
	
	/**
	 * Gets whether the subscriber is subscribed to 3-Way Calling
	 */
	public function get3Way() 
	{
		$dataType = 'Meta_Subscriber_3-WayCalling';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}
	
	
	
/**
 *
 * MESSAGES
 *
 */	
 
	
	
	/**
	 * Gets the counts of read/unread voicemails for the subscriber
	 */
	public function getVoicemailCounts()
	{
		$dataType = 'Meta_Subscriber_MetaSphere_VoicemailMessageCounts';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));			
		return $data->data[0]->data;
	}
	
	
	/**
	 * Gets the voicemails for the subscriber
	 */
	public function getVoicemails()
	{
		$dataType = 'Meta_Subscriber_MetaSphere_VoicemailMessages';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));			
		if (is_array($data->data[0]->data)) return $data->data[0]->data;
		else return array();
	}
	
	
	/**
	 * Gets the counts of deleted voicemails for the subscriber
	 */
	public function getDeletedVoicemailCounts()
	{
		$dataType = 'Meta_Subscriber_MetaSphere_VoicemailMessageCounts?folder=Trash';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));			
		return $data->data[0]->data;
	}
	
	
	/**
	 * Gets the deleted voicemails for the subscriber
	 */
	public function getDeletedVoicemails()
	{
		$dataType = 'Meta_Subscriber_MetaSphere_VoicemailMessages?folder=Trash';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));			
		if (is_array($data->data[0]->data)) return $data->data[0]->data;
		else return array();
	}
	
	
	/**
	 * Gets the greetings for the subscriber
	 */
	public function getGreetings()
	{
		$dataType = 'Meta_Subscriber_MetaSphere_Greetings';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));			
		if (is_array($data->data[0]->data)) return $data->data[0]->data;
		else return array();
	}
	
	
	/**
	 * Get the url of a voicemail wav file given the id
	 * Includes cache-breaking cb attribute set to the subscriber's directory number
	 * You can download the file by setting the second parameter to true and setting a filename (optional) as the third parameter
	 */
	public function getVoicemailURL($vmID,$download=false,$filename='')
	{
		if (is_object($vmID)) $vmID = $vmID->_;
		$url = self::url."session{$this->_session}/line{$this->dn}/voicemail.wav?id={$vmID}&cb={$this->dn}";
		if ($download===true) {
			if ($filename=='') {
				$url .= "&downloadTo=voicemail{$vmID}-".date("Y-m-d h:i:s",time());
			}
			else {
				$url .= "&downloadTo={$filename}";
			}
			
		}
		return $url;
	}
	
	
	/**
	 * Sets one or more voicemails to read/unread using id(s)
	 * Accepts the following as the $vmIDs parameter...
	 *
	 *		1
	 *		array('_'=>1)
	 *		array(1,2)
	 *		array(
	 *			array('_'=>1),
	 *			array('_'=>2)
	 *		)
	 *
	 */
	public function setVoicemails ($vmIDs,$read=true)
	{
		if ($read === true) {
			$suffix = 'Read';	
		}
		else $suffix ='Unread';	
		$dataType = 'Meta_Subscriber_MetaSphere_VoicemailMessagesToMarkAs'.$suffix;
		
		$vmIDs = self::prepData($vmIDs);
		
		$post = array(
			'data' => array(
				array(
					'data' => $vmIDs,
					'dataType' => $dataType,
					'objectIdentity' => array(
						'line' => $this->dn
					)
				)
			)
		);
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
	}
	
	
	/**
	 * Deletes voicemail using id(s)
	 * Accepts the following as the $vmIDs parameter...
	 *
	 *		1
	 *		array('_'=>1)
	 *		array(1,2)
	 *		array(
	 *			array('_'=>1),
	 *			array('_'=>2)
	 *		)
	 *
	 */
	public function deleteVoicemail ($vmID)
	{
		$dataType = 'Meta_Subscriber_MetaSphere_VoicemailMessagesToDelete';
		
		$vmIDs = self::prepData($vmIDs);
		
		$post = array(
			'data' => array(
				array(
					'data' => array(
						array(
							"_"=>$vmID
						)
					),
					'dataType' => $dataType,
					'objectIdentity' => array(
						'line' => $this->dn
					)
				)
			)
		);
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
	}
	
	
	/**
	 * Undeletes voicemail using id(s)
	 * Accepts the following as the $vmIDs parameter...
	 *
	 *		1
	 *		array('_'=>1)
	 *		array(1,2)
	 *		array(
	 *			array('_'=>1),
	 *			array('_'=>2)
	 *		)
	 *
	 */
	public function undeleteVoicemail ($vmID)
	{
		$dataType = 'Meta_Subscriber_MetaSphere_VoicemailMessagesToUndelete?folder=Trash';
		
		$vmIDs = self::prepData($vmIDs);
		
		$post = array(
			'data' => array(
				array(
					'data' => array(
						array(
							"_"=>$vmID
						)
					),
					'dataType' => $dataType,
					'objectIdentity' => array(
						'line' => $this->dn
					)
				)
			)
		);
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
	}
	
	
	/**
	 * Permanently deletes voicemail from trash using id(s)
	 * Accepts the following as the $vmIDs parameter...
	 *
	 *		1
	 *		array('_'=>1)
	 *		array(1,2)
	 *		array(
	 *			array('_'=>1),
	 *			array('_'=>2)
	 *		)
	 *
	 */
	public function permDeleteVoicemail ($vmID)
	{
		$dataType = 'Meta_Subscriber_MetaSphere_VoicemailMessagesToDelete?folder=Trash';
		
		$vmIDs = self::prepData($vmIDs);
		
		$post = array(
			'data' => array(
				array(
					'data' => array(
						array(
							"_"=>$vmID
						)
					),
					'dataType' => $dataType,
					'objectIdentity' => array(
						'line' => $this->dn
					)
				)
			)
		);
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
	}
	
	
	/**
	 * Gets auto-forwarding of voicemails configuration
	 */
	public function getAutoForwarding()
	{
		$dataType = 'Meta_Subscriber_MetaSphere_AutoForwarding';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}
	
	
	/**
	 * Sets voicemail automatic forwarding settings
	 * Accepts the following as the $addresses parameter...
	 *
	 *		'email@address.com'
	 *		array('_'=>'email@address.com')
	 *		array('email@address.com','email@address.com')
	 *		array(
	 *			array('_'=>'email@address.com),
	 *			array('_'=>'email@address.com)
	 *		)
	 *
	 */
	public function setAutoForwarding($status=true,$leaveCopy=true,$includeLinks=false,$addresses=NULL)
	{
		$dataType = 'Meta_Subscriber_MetaSphere_AutoForwarding';
		
		$addresses = self::prepData($addresses);
			
		$post = array(			
			'data'=>array(
				array(
					'data'=>array(
						'ForwardingStatus'=>array(
							'_'=>$status
						),
						'LeaveCopyStatus'=>array(
							'_'=>$leaveCopy
						),
						'IncludeLinks'=>array(
							'_'=>$includeLinks
						)
					),
					'dataType'=>$dataType,
					'objectIdentity'=>array(
						'line'=>$this->dn
					)
				),
			)
		);
		
		if (is_array($addresses)) {
			$post['data'][0]['data']['ForwardingAddresses'] = $addresses;
		}
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
		  return(print_r($post, TRUE));
           
	}
	
	
	/**
	 * Gets the faxes for the subscriber
	 */
	public function getFaxes()
	{
		$dataType = 'Meta_Subscriber_MetaSphere_FaxMessages';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));			
		if (is_array($data->data[0]->data)) return $data->data[0]->data;
		else return array();
	}
	
	
	/**
	 * Get the url of a fax pdf file given the id
	 * Includes cache-breaking cb attribute set to the subscriber's directory number
	 * You can download the file by setting the second parameter to true and setting a filename (optional) as the third parameter
	 */
	public function getFaxURL($faxID,$download=false,$filename='')
	{
		if (is_object($faxID)) $faxID = $faxID->_;
		$url = self::url."session{$this->_session}/line{$this->dn}/fax.pdf?id={$faxID}&cb={$this->dn}";
		if ($download===true) {
			if ($filename=='') {
				$url .= "&downloadTo=fax{$vmID}-".date("Y-m-d h:i:s",time());
			}
			else {
				$url .= "&downloadTo={$filename}";
			}
			
		}
		return $url;
	}
	
	

/**
 *
 * CONTACTS AND CALL DATA
 *
 */	


	
	/**
	 * Gets the subscriber's contacts
	 */
	public function getContacts()
	{
		$dataType = 'Contacts';			
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		if (is_array($data->data[0]->data->Contact)) return $data->data[0]->data->Contact;
		else return array();
	}
	
	
	/**
	 * Gets the entire subscriber's call log
	 * Valid Types: (optional - returns only subset)
	 *		Missed
	 *		Answered
	 *		Dialed
	 */
	public function getCalls($type=NULL)
	{
		$dataType = 'Meta_Subscriber_MetaSphere_CallList';
		$this->calls = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		
		if (isset($type)) {
	
			$type = strtolower($type);
		
			if ($type == 'missed') {
				return $this->getMissedCalls();
			}
			else if ($type == 'answered') {
				return $this->getAnsweredCalls();
			}
			else if ($type == 'dialed') {
				return $this->getDialedCalls();
			}
			else if ($type == 'all') {
				return $this->calls;
			}
			else {
				return "Invalid Type";
			}
		}
		else {
			if (is_array($this->calls->data[0]->data)) return $this->calls->data[0]->data;
			else return array();
		}
	}
	
	
	/**
	 * Gets the subscriber's missed calls from the call log
 	 * Setting refresh parameter to true will refresh the list
	 */
	public function getMissedCalls($refresh=false)
	{
		if (!$this->calls || $refresh === true) $this->getCalls();
		if (is_array($this->calls->data[0]->data->MissedCalls->Call)) return $this->calls->data[0]->data->MissedCalls->Call;
		else return array();
		
	}
	
	
	/**
	 * Gets the subscriber's answered calls from the call log
	 * Setting refresh parameter to true will refresh the list
	 */
	public function getAnsweredCalls($refresh=false)
	{
		if (!$this->calls || $refresh === true) $this->getCalls();
		if (is_array($this->calls->data[0]->data->AnsweredCalls->Call)) return $this->calls->data[0]->data->AnsweredCalls->Call;
		else return array();
	}
	
	
	/**
	 * Gets the subscriber's dialed calls from the call log
	 * Setting refresh parameter to true will refresh the list
	 */
	public function getDialedCalls($refresh=false)
	{
		if (!$this->calls || $refresh === true) $this->getCalls();
		if (is_array($this->calls->data[0]->data->DialedCalls->Call)) return $this->calls->data[0]->data->DialedCalls->Call;
		else return array();
	}
	

	/**
	 * Adds a contact to the subscriber's contact list
	 */
	public function saveContact($fields)
	{
		$dataType = 'Contacts';
		$fields['UID']?$dataType.="?Contact.UID=".$fields['UID']:"";
					
		$post = array(			
			'data'=>array(
				array(
					'data'=>array(
						'Contact'=>array(
							array(
								'GivenName'=>array('_'=>mysql_escape_string($fields['GivenName'])),
								'FamilyName'=>array('_'=>mysql_escape_string($fields['FamilyName'])),
								'Nickname'=>array('_'=>mysql_escape_string($fields['Nickname'])),
								'JobTitle'=>array('_'=>mysql_escape_string($fields['JobTitle'])),
								'Organization'=>array('_'=>mysql_escape_string($fields['Organization'])),
								'HomePhone'=>array('_'=>mysql_escape_string($fields['HomePhone'])),
								'WorkPhone'=>array('_'=>mysql_escape_string($fields['WorkPhone'])),
								'CellPhone'=>array('_'=>mysql_escape_string($fields['CellPhone'])),
								'Fax'=>array('_'=>mysql_escape_string($fields['Fax'])),
								'OtherPhone'=>array('_'=>mysql_escape_string($fields['OtherPhone'])),
								'PreferredPhone'=>array('_'=>mysql_escape_string($fields['PreferredPhone'])),
								'Email1'=>array('_'=>mysql_escape_string($fields['Email1'])),
								'Email2'=>array('_'=>mysql_escape_string($fields['Email2'])),
								'SMS'=>array('_'=>mysql_escape_string($fields['SMS'])),
								'PreferredEmail'=>array('_'=>mysql_escape_string($fields['PreferredEmail'])),
								'HomeAddress'=>array(
									'Street'=>array('_'=>mysql_escape_string($fields['HomeAddressStreet'])),
									'Locality'=>array('_'=>mysql_escape_string($fields['HomeAddressLocality'])),
									'Region'=>array('_'=>mysql_escape_string($fields['HomeAddressRegion'])),
									'PostalCode'=>array('_'=>mysql_escape_string($fields['HomeAddressPostalCode'])),
									'Country'=>array('_'=>mysql_escape_string($fields['HomeAddressCountry']))),
								'WorkAddress'=>array(
									'Street'=>array('_'=>mysql_escape_string($fields['WorkAddressStreet'])),
									'Locality'=>array('_'=>mysql_escape_string($fields['WorkAddressLocality'])),
									'Region'=>array('_'=>mysql_escape_string($fields['WorkAddressRegion'])),
									'PostalCode'=>array('_'=>mysql_escape_string($fields['WorkAddressPostalCode'])),
									'Country'=>array('_'=>mysql_escape_string($fields['WorkAddressCountry']))),
								'UID'=>array('_'=>mysql_escape_string($fields['UID']))
										
								)		
						)
					),
					'dataType'=>$dataType,
					'objectIdentity'=>array(
						'line'=>$this->dn
					)
				),
			)
		);
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
       return(print_r($post, TRUE));
	}
	
	public function deleteContact($fields)
	{
		$dataType = 'Contacts';
		$fields['UID']?$dataType.="?Contact.UID=".$fields['UID']:"";
	
	
		$post = array(			
			'data'=>array(
				array(
					'data'=>array(
					'_Action'=>array('_'=>'delete')
					
					),
					'dataType'=>$dataType,
					'objectIdentity'=>array(
						'line'=>$this->dn
					)
				),
			)
		);
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
       return(print_r($post, TRUE));
	}
	
/**
 *
 * FORWARDING, BARRING AND BLOCKING
 *
 */	
	
	
	
	/**
	 * Gets the curret configuration of call forwarding per type
	 * Valid Types: 
	 *		Unconditional (default)
	 *		Unavailable
	 *		Busy
	 *		Delayed
	 *		Selective
	 */
	public function getCallForwarding($type='Unconditional')
	{
		$dataType = "Meta_Subscriber_{$type}CallForwarding";
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}
	
	public function getCallForwardingNumbers()
	{
		$dataType = "Meta_Subscriber_SelectiveCallForwarding_NumbersList";
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}
	
	
	/**
	 * Get the current configuration of call barring
	 * Valid Types: (optional)
	 * 		International
	 *		NationalAndMobile
	 *		Local
	 *		Operator
	 *		AccessCodes
	 *		Premium
	 *		AccessCodesThatChangeConfiguration
	 *		DirectoryAssistance
	 */
	public function getCallBarring($type=NULL)
	{
		$dataType='Meta_Subscriber_CallBarring';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		if (isset($type)) {
			return $data->data[0]->data->CurrentSubscriberBarredCallTypes->Value->$type->_;
		}
		else return $data->data[0]->data;
	}
	
	
	/**
	 * Get the selective call acceptance / Do NOt Disturb Setting
	 */	
	public function getSelectiveCallAcceptance()
	{
		$dataType = 'Meta_Subscriber_DoNotDisturb';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;		
	}
	
	
	/**
	 * Get the Selective call acceptance numbers
	 */				
	public function getSelectiveCallAcceptanceNumbers()
	{
		$dataType = 'Meta_Subscriber_DoNotDisturb_SCANumbersList';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;		
	}
	
	
	/**
	 * Get the status of Selective Call Rejection
	 */
	public function getSelectiveCallRejection()
	{
		$dataType = 'Meta_Subscriber_SelectiveCallRejection';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;		
	}
	
	
	/**
	 * Get the selective call rejection numbers
	 */				
	public function getSelectiveCallRejectionNumbers()
	{
		$dataType = 'Meta_Subscriber_SelectiveCallRejection_NumbersList';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;		
	}
	
		
	/**
	 * Gets anonymous call rejection status
	 */
	public function getAnonymousCallRejection()
	{
		$dataType = 'Meta_Subscriber_AnonymousCallRejection';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}
	
	
	
	
	
	/**
	 * Gets current Find Me Follow Me settings
	 */
	public function getFMFM()
	{
		$dataType = 'Meta_Subscriber_Find-me-follow-me';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}
	
	
	/**
	 * Gets do not disturb settings
	 */
	public function getDoNotDisturb()
	{
		$dataType = 'Meta_Subscriber_DoNotDisturb';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}
	
	
	/**
	 * Sets call barring per type
	 * Valid Types: 
	 *		International (default)
	 *		NationalAndMobile
	 *		Local
	 *		Operator
	 *		AccessCodes
	 *		Premium
	 *		AccessCodesThatChangeConfiguration
	 *		DirectoryAssistance
	 */
	public function setCallBarring($enable=true,$types)
	{
		
			
		$dataType = 'Meta_Subscriber_CallBarring';
		$post = array(			
			'data'=>array(
				array(
					'data'=>array(
						"CurrentSubscriberBarredCallTypes"=>array(
									'UseDefault'=>array(
										'_'=>false
									),
									'Value'=>array(
										
									)
								)
						),
						'dataType'=>$dataType,
						'objectIdentity'=>array(
							'line'=>$this->dn
						
					)
				)
			)
		);
		$calltypes=array();
		foreach($types as $type=>$value){
			
			$post['data'][0]['data']['CurrentSubscriberBarredCallTypes']['Value'][$type]=array(
								'_Default'=>false,
								'_'=>$value
							);
		}
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
		return($post);
	}
	
	/**
	 * Sets selective call forwarding number(s)
	 */
	public function setSelectiveCallForwardNumbers($numbers)
	{
		
		$numbers = self::prepData($numbers);
	
	
		$dataType = 'Meta_Subscriber_SelectiveCallForwarding_NumbersList';
		$post = array(			
			'data'=>array(
				array(
					'data'=>array(
						'Number'=> $numbers
					),
					'dataType'=>$dataType,
					'objectIdentity'=>array(
						'line'=>$this->dn
					)
				)
			)
		);
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
	}	
	
	/**
	 * Sets anonymous call rejection status
	 */
	public function setSelectiveCallRejectionNumbers($numbers,$remove=false)
	{
		
	
		if (is_array($numbers)) {
			if ($remove === false) {
				$numbers[] = array('_'=>$number);
			}
			else {
				foreach($numbers as $key=>$num) {
					if ($num->_ == $number) {
						unset($numbers[$key]);
					}
				}
			}
		}
	
		$dataType = 'Meta_Subscriber_SelectiveCallRejection_NumbersList';
		$post = array(			
			'data'=>array(
				array(
					'data'=>array(
						'Number'=> $numbers
					),
					'dataType'=>$dataType,
					'objectIdentity'=>array(
						'line'=>$this->dn
					)
				)
			)
		);
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
		
		return($post);
	}
	
		
	
	/**
	 * Sets anonymous call rejection status
	 */
	public function setAnonymousCallRejection($enable=true)
	{
		$dataType = 'Meta_Subscriber_AnonymousCallRejection';
		$post = array(			
			'data'=>array(
				array(
					'data'=>array(
						'Enabled'=>array(
							'_'=>true
						)
					),
					'dataType'=>$dataType,
					'objectIdentity'=>array(
						'line'=>$this->dn
					)
				)
			)
		);
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
	}
		
	
	/**
	 * Enable/Disable call forwarding per type
	 * Number to forward to is an optional parameter that should be set when first enabling
	 * Valid Types: 
	 *		Unconditional (default)
	 *		Unavailable
	 *		Busy
	 *		Delayed
	 *		Selective
	 */
	public function setCallForwarding($enable=true,$type='Unconditional',$number=NULL,$addtlParam=NULL)
	{
		$dataType = "Meta_Subscriber_{$type}CallForwarding";
		
		$post = array(
			'data' => array(
				array(
					'data'=>array(
						'Enabled'=>array(
							'_'=>$enable
						)
					),
					'dataType'=>$dataType,
                  	'objectIdentity'=>array(
						'line'=>$this->dn
					)
				)
			)
		);

		if (isset($number)) {
			$post['data'][0]['data']['Number']['_'] = $number;
		}
		
		if (isset($addtlParam)) {
			if ($type == 'Delayed'){
				 $post['data'][0]['data']['NoReplyTime']['Value']['_'] = $addtlParam;
			}
			else if ($type == 'Unconditional') $post['data'][0]['data']['SingleRing']['_'] = $addtlParam;
		}
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
		return $post;

	}

	
	/**
	 * Sets the call forwarding number per type
	 * Valid Types: 
	 *		Unconditional (default)
	 *		Unavailable
	 *		Busy
	 *		Delayed
	 *		Selective
	 */
	public function setForwardingNumber($number,$type='Unconditional')
	{
		$dataType = 'Meta_Subscriber_{$type}CallForwarding';
		
		$post = array(
			'data' => array(
				array(
					'data' => array(
						'Number'=>array(
							'_'=>$number
						)
					),
					'dataType' => $dataType,
					'objectIdentity' => array(
						'line' => $this->dn
					)
				)
			)
		);

		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
	}
	
	
	/*
	 * Sets the selective call acceptance status
	 */
	public function setSelectiveCallAcceptance($enabled=TRUE)
	{
		$dataType = 'Meta_Subscriber_DoNotDisturb';
		
		$post = array(			
			'data'=>array(
				array(
					'data'=>array(
						'Enabled'=>array(
							'_'=>$enabled
						)					
					),
					'dataType'=>$dataType,
					'objectIdentity'=>array(
						'line'=>$this->dn
					)
				),
			)
		);
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
       return(print_r($post, TRUE));
	}
	
	/*
	 * Sets the selective call rejection status
	 */
	public function setSelectiveCallRejection($enabled=TRUE)
	{
		$dataType = 'Meta_Subscriber_SelectiveCallRejection';
		
		$post = array(			
			'data'=>array(
				array(
					'data'=>array(
						'Enabled'=>array(
							'_'=>$enabled
						)				
					),
					'dataType'=>$dataType,
					'objectIdentity'=>array(
						'line'=>$this->dn
					)
				),
			)
		);
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
       return(print_r($post, TRUE));
	}
	
	
/*
 *
 * CALLER ID
 *
 */
	
	
	/**
	 * Gets whether Caller ID name and number are withheld
	 */
	public function getCallerIDPresentation() 
	{
		$dataType = 'Meta_Subscriber_CallerIDPresentation';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}
	
	/**
	 * Gets whether Caller ID name is withheld
	 */
	public function getCallerIDName() 
	{
		$dataType = 'Meta_Subscriber_CallingNameDelivery';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}	
	
	/**
	 * Gets the Caller ID setting of number
	 */
	public function getCallerIDNumber() 
	{
		$dataType = 'Meta_Subscriber_CallingNumberDelivery';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}
	
		
	/**
	 * Gets whether Caller ID name and number are withheld over IP
	 */	
	public function getCallerIDoverIP() 
	{
		$dataType = 'Meta_Subscriber_CallingNameAndNumberDeliveryOverIP';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}	
	
	
	/**
	 * Gets whether the Caller ID name and number are delivered over IP
	 */
	public function getNameAndNumberDeliveryOverIP() 
	{
		$dataType = 'CallingNameAndNumberDeliveryOverIP';
		$data = json_decode($this->getData(self::url . 'session' . $this->_session . '/data?data='. $dataType . '&version=' . self::client_version));
		return $data->data[0]->data;
	}
	
	
	/**
	 * Sets whether to withhold Caller ID information
	 */
	public function setCallerIDPresentation($status=true) 
	{
		$dataType = 'Meta_Subscriber_CallerIDPresentation';
		
		$post = array(			
			'data'=>array(
				array(
					'data'=>array(
						'WithholdNumberByDefault'=>array(
							'Value'=>array(
								'_'=>$status
							),
							'Use_Default'=>array(
									'_'=>false
								)
						)
					),
					'dataType'=>$dataType,
					'objectIdentity'=>array(
						'line'=>$this->dn
					)
				),
			)
		);
		
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
		 return(print_r(json_encode($post), TRUE));
	}
	
	
	/*
	 * Sets the caller ID name delivery status
	 */
	public function setCallerIDName($status=true) 
	{
		$dataType = 'Meta_Subscriber_CallingNameDelivery';
		
		$post = array(			
			'data'=>array(
				array(
					'data'=>array(
						'Enabled'=>array('_'=>$status)
					),
					'dataType'=>$dataType,
					'objectIdentity'=>array(
						'line'=>$this->dn
					)
				),
			)
		);
		
		
		$this->postData (self::url . 'session' . $this->_session . '/data?version=' . self::client_version, json_encode($post));
		 return(json_encode($post));
	}
		
	
	
/**
 *
 *	HELPER FUNCTIONS
 *
 */
	
	
	
	/**
	 * Formats a 10 digit number to (123) 456-7890
	 */
	public static function formatPhone ($number) 
	{
		return "(".substr($number, 0, 3).") ".substr($number, 3, 3)."-".substr($number,6);
	}
	
	
	/**
	 * Parses strings, numbers or "traditional" arrays to metasphere arrays
	 * Accepts the following as the $value parameter...
	 *
	 *		value
	 *		array('_'=>value)
	 *		array(value1,value2)
	 *		array(
	 *			array('_'=>value1),
	 *			array('_'=>value2)
	 *		)
	 *
	 */	
	public static function prepData($val)
	{
		if (is_numeric($val) || is_string($val)) {
			$val = array(array('_'=>$val));
		}
		else if (is_array($val) && (is_numeric($val['_'] || is_string($val['_'])))) {
			$val = array($val);
		}
		else if (is_array($val) && (is_numeric($val[0]) || is_string($val[0]))) {
			$arr = array();
			foreach ($val as $val) {
				$arr[] = array('_'=>$val);
			}
			$val = $arr;
		}
		return $val;
	}
	
	
	/**
	 * Get data via cURL. 
	 * Falls back to file() if cURL is not installed
	 */
	private function getData ($url)
	{
		if ($this->_loginStatus == 0) return false;
		if ($this->debug===true) echo '<div class="cp_debug">'.$url.'</div>';
		if(function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, $agent);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_REFERER, "http://".$_SERVER['HTTP_HOST']);
		  	//echo time();
		 	$ret = curl_exec ($ch);
		  	//echo time();
			curl_close ($ch);
		}
		else{
			$ret = @implode('', @file ($url));
		}
		return $ret;	
	}
	
	
	/**
	 * Post data via cURL with Content-Type set to application/json
	 * Requires cURL or PECL_HTTP
	 */
	private function postData ($url,$data,$contentType='application/json') 
	{
		if ($this->_loginStatus == 0) return false;
		
		if (is_array($data) && $contentType=='application/json') {
			$data = json_encode($data);
		}
		
		if ($this->debug===true) {
			echo '<div class="cp_debug">'.$url.'</div>';
			echo '<pre class="cp_debug">';
			if ($contentType == 'application/json') print_r($data);
			echo '</pre>';	
			echo '<div class="cp_debug">'.$contentType.'</div>';	
		}
		
		if(function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: '.$contentType,
				'Content-Length: ' . strlen($data))
			);	
			$ret = curl_exec($ch);
			curl_close ($ch);
		}
		else if (class_exists(HTTPRequest)) {
			$request = new HTTPRequest($url, HTTP_METH_POST);
			$request->setHeaders(
				array(
					'Content-Type:' => $contentType, 
					'Content-Length:' => strlen($data)
				)
			);
			$request->setRawPostData($data);
			$request->send();
			$ret = $request->getResponseBody();
		}
	
		return $ret;
		
	}

		
}
