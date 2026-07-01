# Updating Dependencies

This repo has four dependency ecosystems. Update all of them together.

## PHP (root)

```sh
composer update
composer bump
```

`composer bump` raises the minimum version constraints in `composer.json` to match what's currently installed in the lockfile.

## JavaScript (root)

```sh
npx npm-check-updates -u --target minor
npm install
```

Use `--target minor` to avoid unplanned major version upgrades.

## Go (potal/)

```sh
cd potal
go get -u ./...
go mod tidy
```

## Ruby (shopato/)

Requires Ruby 3.4+ via mise. System Ruby will not work.

```sh
cd shopato
bundle update
```

To also bump Gemfile version constraints (not just the lockfile), manually update the version strings in `Gemfile` to match the resolved versions in `Gemfile.lock` after running `bundle update`.
