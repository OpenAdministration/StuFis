
# Note 
This project is in the middle of a migration from a legacy framework to Laravel. Therefore, the documentation is not yet finalized. 

# Features 
For a Feature List have a look [here](https://open-administration.de/index.php/finanzverwaltungssoftware/)
## Demo 
You can find a demo of the old legacy code at demo.open-adminsitration.de
# Installation 
See the laravel documentation for basic installation. 
Above that you need some special credentials to use all the features of this Software in your configuration:
* a registration Number from [Deutsche Kreditwirtschaft](https://www.hbci-zka.de/register/hersteller.htm)
* a SAML oder CAS Identity Provider (IdP) (this might change in the future), which has the following `groups` attributes (for matching rights), `ref-finanzen`, `ref-finanzen-hv`, `ref-finanzen-kv`, `ref-finanzen-belege` they can be prefixed by a realm and also free picked in future commits. 

# Feature Request 
Please write an issue in this github repository 



