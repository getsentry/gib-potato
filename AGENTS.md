# Commit Messages

Format: `type(scope): description`

Types: `feat`, `ref`, `chore`, `fix`

Scope is the part of the project that was updated: `frontend`, `backend`, `potal`, `shopato`, `docker`, `ci`, `deps`, etc. If multiple scopes are touched, join them with `|`.

Examples:

```
feat(frontend): Add dark mode toggle
fix(backend|potal): Handle empty response from events API
chore(deps): Update project dependencies
ref(frontend|backend): Migrate from Vite to Vite+
```

Keep commit messages concise. The body (if needed) is objective statements of what changed — no prose, no test plans, no verification steps.

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
vpx npm-check-updates -u --target minor
vp install
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
