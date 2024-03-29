
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
* OAuth2 Identity Provider (IdP), SAML in the future 

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

# Feature Request 
Please write an issue in this github repository 



