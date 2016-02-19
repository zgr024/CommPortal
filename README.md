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

Initialize object with a directory number and password, session, or directory number only (SSO)
Usage:
  $cp = new CommPortal(1234567890);
  
  // Using magic GET method
	foreach ($cp->Voicemails as $vm) {
	    print_r($vm);
	}
	
	// Using traditional method
	$voicemails = $cp->getVoicemails();
	foreach ($vicemails as $vm) {
	    print_r($vm);
	}
			
	$busyForwarding = $cp->getCallForwarding('Busy');
	
	*Note: To use SSO you must install a certificate on the EAS server and create a private key file on your web server*
		
