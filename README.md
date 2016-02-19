CommPortal
==========

### CommPortal PHP API

###### CommPortal - PHP library for MetaSwitch EAS CommPortal
###### version 1.0.1
###### https://github.com/zgr024/CommPortal
###### Copyright (C) 2014 Zachary Rosenberg
###### Please submit problem or error reports to https://github.com/zgr024/CommPortal/issues  

###### All rights reserved.
###### Permission is hereby granted, free of charge, to use, copy or modify this software.  Use at your own risk.
---
```php
// Initialize object with a directory number and password
$cp = new CommPortal($dn,$password);
	
// Initialize object with a directory number only (SSO)
$cp = new CommPortal($dn,$password);

// Initialize object with a session
$cp = new CommPortal($session);
	
// Using magic GET method
foreach ($cp->Voicemails as $vm) {
	print_r($vm);
}
	
// Using traditional method
$voicemails = $cp->getVoicemails();
foreach ($voicemails as $vm) {
	print_r($vm);
}
			
$busyForwarding = $cp->getCallForwarding('Busy');
```
	
*Note: To use SSO you must install a certificate on the EAS server and create a private key file on your web server*
		
