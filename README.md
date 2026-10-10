# Tusk application

This is the official Composer project skeleton for applications built with the
Tusk Framework. Composer creates the project and installs its dependencies;
the Tusk Engine manages configuration and the RoadRunner process; the
Framework handles application bootstrap, routing, dependency injection, and
validation.

## Create a project

```sh
composer create-project tusk/app catalog
cd catalog
tusk doctor
tusk build
tusk start
```

The Engine executable must be installed and available as `tusk` on `PATH`.
See the [Tusk Engine installation and runtime guide](https://github.com/tusk-framework/tusk-engine/blob/main/docs/guides/project-runtime.md)
for supported platform setup. The project already includes `tusk.json`; the
Engine's `tusk init` is for adding Engine configuration to an existing project,
not for creating this application.

## Example endpoint

The skeleton includes a small typed product API at `/api/products`. Its
immutable `ProductInput` DTO demonstrates constructor binding and validation;
invalid input returns an RFC 9457 `application/problem+json` response. The
catalog stores data in memory for demonstration. RoadRunner workers do not
share that memory, and it resets when a worker restarts; use a database-backed
repository for persistent, shared data.

Send a request after `tusk start`:

```sh
curl -i -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"name":"Notebook","sku":"NOTE-1"}' \
  http://127.0.0.1:8080/api/products
```

In PowerShell, use this single-line command (PowerShell's `curl` may be an alias):

```powershell
curl.exe -i -H "Content-Type: application/json" -H "Accept: application/json" -d '{"name":"Notebook","sku":"NOTE-1"}' http://127.0.0.1:8080/api/products
```

## Development

```sh
composer install
composer test
```

The supported application entrypoint is `bootstrap/app.php`. Do not add a
parallel `worker.php` or legacy `config.php` bootstrap; the Engine generates
and supervises the RoadRunner worker runtime.
