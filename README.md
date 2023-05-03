CommPortal
==========

### CommPortal - PHP library for MetaSwitch EAS CommPortal - Version 1.0.1
### https://github.com/zgr024/CommPortal

###### Permission is hereby granted, free of charge, to use, copy or modify this software.  Use at your own risk.
###### Please submit problem or error reports to https://github.com/zgr024/CommPortal/issues
---
#### Examples... 
###### Initialize object with a directory number and password
```php
$cp = new CommPortal($dn,$password);
```	
###### Get the current session
```php
$session = $cp->_session;
```
###### Initialize object with a previous session
```php
$cp = new CommPortal($session);
```
###### Initialize object with a directory number only (SSO)
*Note: To use SSO you must install a certificate on the EAS server and create a private key file on your web server*
```php
$cp = new CommPortal($dn);
```
###### Get voicemails using magic GET method
```php
foreach ($cp->Voicemails as $voicemail) {
	print_r($voicemail);
}
```
###### Get voicemails using traditional method
```php
$voicemails = $cp->getVoicemails();
foreach ($voicemails as $voicemail) {
	print_r($voicemail);
}
```
###### Get voicemail counts
```php
$oicemailCounts = $cp->getVoicemailsCounts();
```
###### Get deleted voicemails counts
```php
$deletedVoicemailCounts = $cp->getDeletedVoicemailsCounts();
```
###### Get deleted voicemails
```php
$deletedVoicemails = $cp->getDeletedVoicemails();
```
###### Get voicemail message audio for embedded player
```PHP
$url = getVoicemailURL($voicemailID);
```
###### Download voicemail message audio
```PHP
getVoicemailURL($voicemailID,true,'filename.wav');
```
###### Get call forwarding settings for 'Busy'			
```php
$busyForwarding = $cp->getCallForwarding('Busy');
```
###### Enable call forwarding for 'Busy'
```php
$busyForwarding = setCallForwarding(true,'Busy',$number);
```
###### Disable call forwarding for 'Busy'
```php
$busyForwarding = setCallForwarding(false,'Busy');
```
###### Get the Class of Service for the current subscriber		
```php
$classOfService = $cp->getClassOfService();
```
###### Get the Class of Service for the current subscriber		
```php
$classOfService = $cp->getClassOfService();
```
###### Get the Customer Information for the current subscriber		
```php
$classOfService = $cp->getCustomer();
```
###### Get whether the customer is subscibed to 3 Way Calling	
```php
$classOfService = $cp->get3Way();
```
###### Get the Customer Information for the current subscriber		
```php
$classOfService = $cp->getCustomer();
```
