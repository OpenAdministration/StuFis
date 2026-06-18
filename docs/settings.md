# Settings

Runtime settings live in the `settings` database table and are accessed through the
`App\Models\Setting` model. They were migrated out of the legacy PHP array config
(`legacy/config/config.orgs.php`) into the database; see
`settings:import-from-legacy-config`.

## Storage & access

- **Table:** `settings` — `key` (string primary key), `value` (JSON), timestamps.
- **Model:** `App\Models\Setting`
  - `Setting::get(string $key, mixed $default = null)` — dot notation supported. Falls back to
    the passed `$default`, then to the hard-coded defaults in `Setting::defaults()`.
  - `Setting::set(string $key, mixed $value)` — upsert.
  - `Setting::drop(string $key)` — delete; the key then falls back to its default.
  - `Setting::toMap()` — all settings as a nested array, defaults merged in.
- **Nested values:** when a value is an associative array, `get()` returns a read-only
  `App\Support\SettingsBag` so you can do
  `Setting::get('project')->description->min_length`.

## Managing settings

### CLI

```bash
php artisan stufis:settings list [--json]
php artisan stufis:settings get  <key>
php artisan stufis:settings set  <key> <value>   # value parsed as JSON when possible
php artisan stufis:settings forget <key>
```

Dot notation works for nested keys, e.g.
`php artisan stufis:settings set tax.active true`.

### Web

`GET /config` (route name `config`, auth-protected) renders the full settings map via
`AdminConfigPage`. This is read-only (a dump) — there is no settings editor UI yet; use the
CLI to change values.

## Available settings

Keys marked **(default)** have a fallback in `Setting::defaults()` and work even when no row
exists. Keys marked **(no default)** return `null` (or the caller-supplied default) unless a
row has been written, usually by the legacy-config migration.

| Key | Type | Default | Purpose / where used |
| --- | --- | --- | --- |
| `finance_mail` | string | `service@open-administration.de` | Finance contact address. |
| `mail_domain` | string | `open-administration.de` | Domain appended to bare project mail aliases (`Legacy\Project::mail`); used by `stufis:change-project-mail-domain`. |
| `project.description.min_length` | int | `50` | Min length of a project description. |
| `project.description.max_length` | int | `99999` | Max length of a project description. |
| `project.protocol_url.active` | bool | `false` | Whether the protocol-link field is shown in the project form (`show-project`, `edit-project`). |
| `project.protocol_url.label` | string | `""` | Label for the protocol link. |
| `project.protocol_url.prefix` | string | `""` | URL prefix prepended to the protocol link. |
| `user.committees.mode` | `filter`\|`all`\|`raw` | (no default) | How `User::getCommittees()` resolves committees: `filter` = intersect auth committees with `data`; `all` = use `data` verbatim; `raw` = use auth committees only. Set to `filter` by the migration. |
| `user.committees.data` | string[] | (no default) | The configured committee list used by `user.committees.mode`. Populated from the legacy `gremien` config. |
| `tax.active` | bool | `false` | Enables the Umsatzsteuer (VAT) feature: the "Umsatzsteuer-Titel hinzufügen" button in the budget plan (`HHPHandler`) and the `add-tax-budgets` endpoint (`RestHandler::saveTaxBudgets`). |
| `datev` | bool | `false` | Shows the DATEV-Export button in the budget plan view (`HHPHandler`). |

## Related structured config

The settings migration also seeds the `legal_bases` table (from the legacy `rechtsgrundlagen`
config), managed via the `App\Models\LegalBasis` model rather than the `Setting` model.
