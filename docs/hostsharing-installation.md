# Stufis Installation on Hostsharing.net Servers

quick guide for installation on hostsharing.net, not perfectly polished but everything should be there :)

1. Add Useraccount 
2. Copy ssh key / log in via ssh

```bash
touch .bash_profile # for nvm autoload
git clone git@github.com:OpenAdministration/StuFis.git
cd StuFis
```
Download Composer.phar from https://getcomposer.org/download/
and nvm.sh https://github.com/nvm-sh/nvm

add to .bash_profile
```bash
alias php="php8.4"
alias composer="php composer.phar"
```
add auth.json from fluxui.dev
```bash
composer install --no-dev
nvm install 22
npm ci
npm run build
```
add domain in hostsharing - if not in use make sure <realmname>.stufis.de exists for forwarding
add database in hostsharing
add client in stumv
with all that edit .env file

```bash
artisan migrate
```

configure domain
```bash
rm -r subs*
rm -r htdocs-ssl
ln -s ~/StuFis/public/ htdocs-ssl
```

edit default php in hs-admin to `	
/usr/lib/cgi-bin/php8.4` options to `fastcgi, letsencrypt` no valid subdomains


then import your Budgetplan and everything should work :) 

optional: add bank for bank-import
