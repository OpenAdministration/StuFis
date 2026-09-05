# Deploying the instance fleet

`bin/stufis-fleet` drives a deployment across all StuFiS instances at once. It
owns no deployment logic: every instance is updated through its own
`bin/stufis-update`, so a fleet deploy and a hand-run deploy do exactly the same
thing. What the fleet tool adds is the inventory, the ordering, the preflight
checks, and a summary you can read afterwards.

It has **no opinion about which ref an instance should run**. It reports what is
deployed and flags what looks unusual; the ref is always yours to pass.

Single-instance operation (setup, a one-off update, maintenance mode) is
unchanged — see [hostsharing-installation.md](hostsharing-installation.md).

## Where it runs

On the pac admin account, which can `sudo` into every instance user — and only
there; `stufis-fleet` refuses to run as anyone else. One ssh hop, no keys to
distribute, and since a full fleet deploy takes half an hour or so, it survives
you closing your laptop if you start it under `tmux`.

If your pac account's sudo asks for a password, note that sudo caches credentials
per terminal for a limited time, so a long fleet run may ask again partway
through — another reason to run it in the foreground under `tmux` rather than
detached.

```bash
ssh xyz00@<server>.hostsharing.net
git clone --depth 1 git@github.com:OpenAdministration/StuFis.git tools
cp tools/bin/templates/stufis-fleet.conf.example ~/.stufis-fleet.conf
$EDITOR ~/.stufis-fleet.conf
```

## Configuration

Everything hosting-specific lives in `~/.stufis-fleet.conf`, so the script itself
stays generic. See [stufis-fleet.conf.example](../bin/templates/stufis-fleet.conf.example) for the
annotated version; the short form is settings, then one line per instance:

```
pac = xyz00

stage   teststufis
demo    demo
beta    early-adopter
prod    asta-example
prod    stura-example
```

The four groups are `stage`, `demo`, `beta` and `prod`. They carry no policy
about which ref an instance runs — they decide only *ordering*: a fleet-wide run
walks them least-consequential first, so `stage` and `demo` have already answered
for a release before `beta` takes it, and `beta` before the rest of production.
`beta` is optional; leave it empty if no realm wants to go first.

Two details about how a realm name maps outwards:

- **The account name replaces `-` with `_`.** Realm `asta-ab` is the unix user
  `xyz00-asta_ab_finanzen`, because a Hostsharing account name cannot contain a
  dash beyond the pac separator.
- **The domain keeps the dash**, and defaults to `domain_template`, i.e.
  `https://asta-hhu.stufis.de`. Since `<realm>.stufis.de` exists for every
  instance and forwards to that instance's real domain when it has one, and the
  smoke test follows redirects, you rarely need the optional third column at all.
  Give a URL only when a realm has no `<realm>.stufis.de` entry.

`stage`, `demo`, `beta`, `prod` and `all` are reserved as targets. A realm may share the
name of *its own* group — the demo instance's realm really is called `demo`, and
the target resolves to the same single instance either way — but not the name of
a different group.

## Commands

```
stufis-fleet status [<target>]          what each instance is running (default: all)
stufis-fleet exec   <target> -- <cmd>   run a command in every selected checkout
stufis-fleet update <target> <ref>      deploy a tag or branch
```

`<target>` is a comma-separated list of groups (`stage`, `demo`, `beta`, `prod`,
`all`) and/or realm names. `status`
defaults to `all` because it changes nothing; **`exec` and `update` require it**,
so no invocation can touch the fleet by accident.

Options: `--except realm,...`, `--parallel N` (default 1), `--dry-run`, `--yes`,
`--force`.

### status

```
REALM       GROUP  VERSION    REF        KIND    TREE   MODE  /up
teststufis  stage  4.5.0      main       branch  clean  up    200
demo        demo   4.5.0      v4.5.0     tag     clean  up    200
asta-x      prod   4.4.4      v4.4.4     tag     clean  up    200
stura-y     prod   4.4.4      fix/thing  branch  dirty  down  200

  ! stura-y: on branch 'fix/thing' - prod instances normally run a tag
  ! stura-y: working tree is dirty - stufis-update will refuse to fast-forward
  ! stura-y: in maintenance mode - a previous deploy may have failed midway
```

The last column is Laravel's health route (`/up`, configurable via
`health_path`). It boots the framework and returns 500 if that throws, so it
answers "does the application still work". It does **not** answer "is the
instance in maintenance mode" — Laravel excludes the health route from
maintenance mode on purpose, so it stays 200 while the app is down. That is what
the `MODE` column is for, and `MODE` reads `storage/framework/down` directly
rather than inferring it from HTTP.

`VERSION` is the *installed* version, read from `vendor/composer/installed.php`
rather than from the checkout, so a mismatch against `composer.json` means
`composer install` did not finish — that gets its own warning. The probe never
boots the application: a broken instance must still be able to report its state.

### exec

The primitive the rest is built on, and useful by itself. Banks move their FinTS
endpoints every few weeks, so this is worth running monthly:

```bash
stufis-fleet exec all -- artisan stufis:fints-institutes-update
```

### update

Preflight runs over every selected instance **before** anything is touched: the
ref has to exist on origin, the checkout has to be present and clean, and the
instance must not already be in maintenance mode (which would mean an earlier
deploy failed midway). If any instance fails preflight, nothing is deployed.

Each instance is then locked (`flock`), updated through its own
`bin/stufis-update`, and smoke-tested: `<url>/up` must return 200, proving the
framework booted without throwing. Redirects are followed, so a `<realm>.stufis.de`
alias forwarding to the instance's real domain resolves correctly. Only the health
route is ever fetched and it is not behind auth, so this never follows anything
into the OIDC provider. The final URL is written to the deploy log, so an alias
pointing at the wrong instance is visible rather than silently passing.

Coming back **out** of maintenance mode is verified separately, by re-probing the
instance and reading `storage/framework/down`. That is the authoritative answer;
HTTP cannot give it, since Laravel keeps the health route responding 200 while
the app is down, and the root URL only ever redirects. Instances already on the target
**tag** are skipped; instances on a **branch** are always redeployed, because a
branch tip can have moved since the last deploy.

Deploys are sequential by default. Ten instances take roughly half an hour, and
parallel Vite builds on shared hosting mostly thrash. `--parallel N` is there if
you want it, but the live output goes to the log files instead.

## The release workflow

Steps 1 and 2 are the existing release process (see the `stufis-release` skill),
unchanged.

```bash
# during development - staging follows the branch, re-run as it moves
stufis-fleet update stage release/4.5.0

# 1. merge to main, tag v4.5.0, publish the GitHub release

# 2. demo takes the tag
stufis-fleet update demo v4.5.0
```

Demo is the cheapest real test you have. Its nightly reset rebuilds the schema
from zero and reseeds it, so the release's migrations get proven end-to-end on
the one instance whose data is disposable. Let it sit through a night before
going further, and click through it.

```bash
# 3. any beta realms that asked to go first
stufis-fleet update beta v4.5.0

# 4. the remaining production realms, sequential, under tmux
stufis-fleet update prod v4.5.0

# 5. confirm
stufis-fleet status
```

A failed deploy does not stop the run — the remaining instances are still
deployed, and the summary at the end names what failed with the path to its log
(`~/stufis-deploy-logs/<realm>-<timestamp>.log`). Exit status is non-zero if
anything failed.

**Read that summary.** `bin/stufis-update` enables maintenance mode before it
does anything else, so an instance that fails partway through stays down on
purpose — it must not serve half-migrated code. That is right for one instance
and dangerous across ten, which is why the failure summary says so explicitly and
why `status` has a `MODE` column.

## A note on git credentials

Instances must be able to fetch from origin **without a human present**. Agent
forwarding cannot provide that: `sudo -u <instance> -i` resets the environment so
`SSH_AUTH_SOCK` is dropped, and the agent socket is mode 0600 owned by the pac
account, so another uid could not open it even if the variable survived. This is
structural — it is not a misconfiguration you can fix.

Since StuFiS is a public repository, the answer is not a deploy key but an HTTPS
remote, which needs no credentials at all:

```bash
stufis-fleet exec all -- git remote set-url origin https://github.com/OpenAdministration/StuFis.git
stufis-fleet status
```

Preflight verifies this per instance before touching anything, because each
account is independent and `stufis-update` enables maintenance mode *before* it
fetches — an account that cannot reach origin would otherwise go dark and stay
dark. Every command the fleet runs also has `GIT_TERMINAL_PROMPT=0` and SSH
`BatchMode=yes` set, so a credential prompt fails immediately instead of hanging
a fleet run forever.

## Rollback

```bash
stufis-fleet update prod v4.4.4
```

A few minutes, and **code only**. Migrations do not roll back. If the schema is
what broke, the path is the backup `stufis-update` takes before migrating — worth
knowing before you need it rather than during.
