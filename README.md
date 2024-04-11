# Features 
For a (full) Feature List have a look [here](https://open-administration.de/index.php/finanzverwaltungssoftware/)
## Demo 
You can find a demo at https://demo.stufis.de

Login Information: https://open-administration.de/index.php/finanzverwaltungssoftware/
# Installation 

The installation process is pretty simple and straight forward. The configuration can be a bit more challenging. See our [User-Guide](https://doku.stufis.de) as reference. 

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
npm run dev
cp .env.example .env
php artisan key:generate
```
And fill in your Environment File! 

```bash
php artisan migrate
php artisan serve
# other terminal (for tailwindcss live-compiling)
npm run watch
```

# Feature Request 
Please write an issue in this github repository 

# Security 

Please write a mail to [service@open-administration.de](mailto:service@open-administration.de) for a responsible disclosure procedure.

# Sponsors 

StuFis is part of the 15th round of [PrototypeFund](https://prototypefund.de/project/studierendenfinanzsoftware/) and therefore financed by the German Federal Ministry of Education and Research [BMBF](https://www.bmbf.de)



