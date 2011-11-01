Parse-Address
=============
Intro
-----
Parse-Address is a branch of the Parse-Address repository developed by Jonathon Byrd.  This is a PHP class which will take an address string and parse out the components, returning them in an array.
  
There are several changes to this branch which are in progress but you might find the existing partially modified code useful now.  

Changes
---------
* Usage of the class has changed:
	
	$pa = new ParseAddress;
	// because of conflict between state codes and country codes, set the country
	$pa->set("country","US");
	$pa->set("default_state","CA",true);   //optional force default state code in case no state in addr
	
	$rtn_addr = $pa->parseAddress($addr);
	//check for errors
	if ($rtn_addr['errors']) { handle errors}	
	if ($rtn_addr['warnings']) { handle warnings}	
	if ($rtn_addr['info']) { handle informational msgs}	

* This has only been test for limited US addresses.	
* The city lookup requires the state be passed to the lookup routine. 
* Code for mulit-word cities, like Los Angeles, was added.  The this is still a rough prototype