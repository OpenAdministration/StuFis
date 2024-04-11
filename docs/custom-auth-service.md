# Auth provider

## Usage

To pick your user provider change the following line in your `.env` file

```dotenv
AUTH_METHOD='local'
```
Supported values (since v4.0.1): 
* `stumv` for [StuMV](#stumv---studentische-mitgliederverwaltung)
* `local` for [local](#local---for-developing-purpose-only) development only 

Implementing your own provider is pretty easy (Pull Requests are welcome!), read the [documentation](#implementing-custom-auth-provider). You can also contact us by [auth@stufis.de](mailto:auth@stufis.de) to get some help to do it yourself or a quote if you want us to do it for you.

### StuMV - "Studentische Mitgliederverwaltung"

Let's assume the following domains we will use in this example: 
* Stumv: stumv.de
* StuFis: demo.stufis.de

First register a new client in StuMV via: 

```bash
~/StuMV$ php artisan passport:client
```

Then you need to provide the following variables in your StuFis `.env` file: 

```dotenv
# the oauth2 client id and secret from stumv, generated like above
STUMV_CLIENT_ID=<your-client-id-goes-here>
STUMV_CLIENT_SECRET=<your-secret-goes-here>
# the uri of your stufis installtaion you want to connect
STUMV_REDIRECT_URI=https://demo.stufis.de/auth/callback
# stumv host url including http(s) and a trailing / 
STUMV_HOST=https://stumv.de/
STUMV_LOGOUT_PATH=logout
# note the demo realm, make sure to fix it to your relam, all empty groups will not be re-mapped
# equivalent to login stufis group
STUMV_GROUP_LOGIN=cn=members,ou=demo,ou=Communities,dc=open-administration,dc=de
# equivalent to ref-finanzen stufis group
STUMV_GROUP_REVISION=cn=ref-finanzen,ou=Groups,ou=demo,ou=Communities,dc=open-administration,dc=de
# equivalent to ref-finanzen-hv stufis group
STUMV_GROUP_HV=cn=ref-finanzen-hhv,ou=Groups,ou=demo,ou=Communities,dc=open-administration,dc=de
# equivalent to ref-finanzen-kv stufis group
STUMV_GROUP_KV=cn=ref-finanzen-kv,ou=Groups,ou=demo,ou=Communities,dc=open-administration,dc=de
# equivalent to ref-finanzen-belege stufis group
STUMV_GROUP_INVOICE=cn=ref-finanzen-belege,ou=Groups,ou=demo,ou=Communities,dc=open-administration,dc=de
# leave empty for production, equivalent to admin stufis group
STUMV_GROUP_ADMIN=
```

For the meaning of the group permissions see [Group Memberships](#group-memberships) below


### Local - for developing purpose only

It's pretty simple. There is only 1 value:  

```dotenv
# picks a known from database, best use LocalSeeder at db migration
# predefined usernames: user, hhv, kv
LOCAL_USERNAME=user
```

The 3 predefined users `user`, `hhv` and `kv` have also some sensible default groups and committees. All other users have none.

## Implementing Custom Auth Provider
So far (since v4.0.1) there are 2 AuthServices pre-implemented providers which have First-Party Support:
* LocalAuthService (for local development only)
* StumvAuthService (based on oauth2 and API calls)

To implement and use your own provider you need the following:
* included php library for your tech stack (ldap,saml,...) in composer.json
* your own XxxAuthService File in `app/Services/Auth/` which extends `AuthService`
* your custom config in `config/services.php` to be able to use the given env file variables
* A registration of your class in `app/Providers/AuthServiceProvider`

After that your Implementation you are ready to go! Please note: because of the nature of the AGPL Licence you are obligated to offer the written code for commit / pull request. Please do so! We would like to receive your implementations!

### General hints before you start
Your Auth Provider has to provide the following infos: 

* User specific info:
  * name, username, email, committee-memberships, group-memberships
  * so far optional: picture, iban, address
* General info: 
  * all available committees

Best: Copy StuMvAuthService and start from there. StuMV gets most of its information from oauth2 userinfo, but some additional infos via oauth2-guarded API calls. If you have all infos provided as claims inside the userinfo that should work out as well.
### Group Memberships
StuFis expects the following groups to be used: 
* login 
  * checked if user is allowed to log-in
* ref-finanzen 
  * checked if sensitive data should be abbreviated
* ref-finanzen-hv 
  * needs: ref-finanzen as well 
  * permissions of a Budget Officer (German: Haushaltsverantwortliche*r)
* ref-finanzen-kv 
  * needs: ref-finanzen as well
  * permissions of a Cash Officer (German: Kassenverantwortliche*r)
* ref-finanzen-belege 
  * can: mark paper documents as recieved
  * recommended to give at least to Budget and Cash Officer
* admin
  * can: everything
  * do not use in production, there should not be a person with that much power
### AuthServiceProvider

```php
$this->app->singleton(AuthService::class, function (Application $application){
            return match (config('auth.service')){
                'stumv' => new StumvAuthService(),
                'local' => new LocalAuthService(),
                // new line for your xxx provider: 
                'xxx' => new XxxAuthService(),
            };
        });
```
Don't forget to include the class or give the full classpath!

## Debugging

The routes 
```php
Route::get('auth/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::get('auth/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
Route::get('auth/callback', [\App\Http\Controllers\AuthController::class, 'callback'])->name('login.callback');
```
Are defined in `routes/web.php`

The used Controller is `app/Http/Controllers/AuthController.php` which uses the given `app/Services/Auth/AuthService.php`
