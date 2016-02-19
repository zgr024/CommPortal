CommPortal
==========

### CommPortal - PHP library for MetaSwitch EAS CommPortal
### version 1.0.1
### https://github.com/zgr024/CommPortal
### Copyright (C) 2014 Zachary Rosenberg
### Please submit problem or error reports to https://github.com/zgr024/CommPortal/issues  

### All rights reserved.
### Permission is hereby granted, free of charge, to use, copy or modify this software.  Use at your own risk.
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
###### Get call forwarding settings for 'Busy'			
```php
$busyForwarding = $cp->getCallForwarding('Busy');
```
###### Enable call forwarding for 'Busy'
```php
$result = setCallForwarding(true,'Busy',$number);
```
###### Disable call forwarding for 'Busy'
```php
$result = setCallForwarding(false,'Busy');
```
		
