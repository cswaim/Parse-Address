Parse-Address
=============
Intro
-----
Parse-Address is a branch of the Parse-Address repository developed by Jonathan Byrd.  This is a PHP class which will take an address string and parse out the components, returning them in an array.
  
There are several changes to this branch which are in progress but you might find useful now.  The intent is to push all the changes back to Jonathan's repository once completed.

Changes
---------
* Several validations have been changed to static call to allow them to be invoked without instantiating the class
* The city lookup requires the state be passed to the lookup routine. The static call addresses this.
* Code for mulit-word cities, like Los Angeles, was added.  The this is still a rough prototype