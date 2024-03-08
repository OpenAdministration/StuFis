
# Note 
This project is in the middle of a migration from a legacy framework to Laravel. Therefore, the documentation is not yet finalized. 

# Features 
For a Feature List have a look [here](https://open-administration.de/index.php/finanzverwaltungssoftware/)
## Demo 
You can find a demo of the old legacy code at demo.open-adminsitration.de

Login Information: https://open-administration.de/index.php/finanzverwaltungssoftware/
# Installation 
## Requirements: 
* php8.2
* MariaDB / Mysql
* a registration Number from [Deutsche Kreditwirtschaft](https://www.hbci-zka.de/register/hersteller.htm)
* SAML or OAuth Identity Provider (IdP) (this might change in the future), which has the following `groups` attributes (for matching rights), `ref-finanzen`, `ref-finanzen-hv`, `ref-finanzen-kv`, `ref-finanzen-belege` they can be prefixed by a realm and also free picked in future commits. 

## Installation Dev

```bash
git clone https://github.com/openAdministration/StuFis 
composer install 
npm install
npm audit fix
npm run dev
cp .env.example .env
php artisan key:generate
```
And fill in your Environment File!

```bash
php artisan migrate
php artisan serve
```
## Installation Production
# Feature Request 
Please write an issue in this github repository 



