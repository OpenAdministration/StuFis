# StuFiS Installation on Hostsharing.net Servers

Quick guide for installation on hostsharing.net. The whole toolchain setup is
automated by `bin/stufis-setup`; only the hosting bits (domain, database, `.env`)
are manual.

## 1. Account & checkout

1. Add a user account in hs-admin.
2. Optional: Add your ssh key to authorized_keys / use ssh-copy-id
3. log in via ssh

```bash
git clone git@github.com:OpenAdministration/StuFis.git
cd StuFis
```

## 2. Flux Pro credentials

StuFiS depends on the paid Flux UI components, so Composer needs your license.
Add your `auth.json` (from https://fluxui.dev) to the project root **before**
running setup — otherwise `composer install` fails.

## 3. Run the setup script

```bash
bash bin/stufis-setup
```

This installs and builds everything:

- downloads `composer.phar`, installs nvm + Node 22 (with npm),
- runs `composer install --no-dev` and `npm ci`,
- builds the frontend assets (`npm run build`),
- writes a managed block to `~/.bash_profile`: nvm autoload, `bin/` on `PATH`,
  and the `php` → `php8.4`, `composer` and `artisan` aliases.

Pick up the new aliases and `PATH` in your current shell:

```bash
source ~/.bash_profile
```

`bin/stufis-setup` is idempotent — re-run it any time to refresh the toolchain.

## 4. Hosting configuration (manual)

- Add the domain in hs-admin. If it is not in use yet, make sure
  `<realmname>.stufis.de` exists for forwarding.
- Add a database in hs-admin.
- Add the client in StuMV.
- Edit `.env` with the domain, database and IdP/StuMV settings, then:

```bash
artisan key:generate
artisan migrate
```

(`artisan` is the alias from step 3 → `php8.4 artisan`.)

## 5. Point the webspace at public/

```bash
rm -r subs*
rm -r htdocs-ssl
ln -s ~/StuFis/public/ htdocs-ssl
```

In hs-admin set the default PHP to `/usr/lib/cgi-bin/php8.4`, options
`fastcgi, letsencrypt`, no valid subdomains.

## 6. Finish

Import your Budgetplan and everything should work :)

Optional: add a bank for the bank-import feature.

## Updating later

From the project directory (`bin/` is on your `PATH` after step 3):

```bash
stufis-update            # update the current branch (fast-forward), then rebuild
stufis-update v4.4.0     # deploy a specific release tag (recommended, reproducible)
stufis-update main       # or switch to a branch and follow its tip
```

Each run goes into maintenance mode, backs up, fetches, self-updates the
toolchain, migrates and rebuilds. Deploying a **tag** pins the instance to that
exact release (detached HEAD); pass the next release tag to move it forward.

Use `stufis-rebuild` to re-warm the production caches and rebuild assets without
pulling or reinstalling dependencies (e.g. after a local config change).
