## Migrating from v3 to laravel based v4.0

### Update Repo
Do a fresh repo clone, or switch branches to main 
``` bash
  git clone git@github.com:OpenAdministration/StuFis.git
```
or
``` bash
  git checkout main
```

### Adjust your .env file

the .env file has changed a lot! Take the .env.example and copy it to .env and fill in your stuff. Having your old .env file ready might come handy.

Some important mentions: 

```dotenv
APP_NAME=StuFiS
APP_ENV=production
...
APP_DEBUG=false
APP_URL=https://example.com
...
LOG_LEVEL=error
...
DB_DATABASE=db_upgrade_to_laravel
DB_USERNAME=root
DB_PASSWORD=
DB_TABLE_PREFIX=live__ #new name!

# auth realm, relevant for config.org.php file (legacy)
AUTH_REALM='demo' # yours goes here 

# can be local or stumv
AUTH_METHOD='stumv'

```

The following vars have to be filled with your oauth client by StuMV

```bash
stumv@host:~/StuMV$ php artisan passport:client
```
```
 Which user ID should the client be assigned to? (Optional):
 > 

 What should we name the client?:
 > demo.open-administration.de

 Where should we redirect the request after authorization? [https://stumv.open-administration.de/auth/callback]:
 > https://demo.open-administration.de/auth/callback
```

### Init laravel 

```bash
composer install --no-dev
php artisan key:generate
npm install
npm run production
php artisan migrate --force # this is the bit dangerous part ^^

# optional   
php artisan legacy:migrate-files-to-storage # migrates old files from DB to storage

# prod env (experimental)
php artisan config:cache
php artisan route:cache
php artisan view:cache

```

## webserver changes

you need php8.2 and to fix the symbolic link like 

```bash
ln -s ~/StuFis/public htdocs-ssl
```
