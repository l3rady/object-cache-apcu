# Contributing

Thanks for helping. Everything runs in Docker, so the only things you need are
`docker` and `docker compose`.

- Set up, tests and coding standards: see **Development** in the
  [README](README.md). `make lint`, `make stan`, `make rector` and `make test`
  must pass; CI runs them on every pull request.
- Commits use [Conventional Commits](https://www.conventionalcommits.org/)
  (`fix(cache): …`, `feat!: …` for breaking changes). They decide the next
  version number, so write them with care. `make commits` checks the commits on
  your branch and CI runs it on every pull request: allowed types, a lower-case
  summary of 72 characters or fewer, and a `!` together with a `BREAKING CHANGE:`
  footer for breaking changes. It cannot check that the summary is in the
  imperative mood or that the body explains why; reviewers do.
- Branch from `main`, name the branch `<type>/<issue>-<short-name>` (for example
  `fix/42-null-check`), and reference the issue in the pull request with
  `Closes #<number>`.
- A bug fix needs a test that fails without the fix.
- New code needs tests: `make coverage` (and CI) fails if line coverage of
  `object-cache.php` falls below 98%.
