# Contributing

Thanks for helping. Everything runs in Docker, so the only things you need are
`docker` and `docker compose`.

- Set up, tests and coding standards: see **Development** in the
  [README](README.md). `make lint`, `make stan`, `make rector` and `make test`
  must pass; CI runs them on every pull request.
- Commits use [Conventional Commits](https://www.conventionalcommits.org/)
  (`fix(cache): …`, `feat!: …` for breaking changes). They decide the next
  version number, so write them with care.
- Branch from `main`, name the branch `<type>/<issue>-<short-name>` (for example
  `fix/42-null-check`), and reference the issue in the pull request with
  `Closes #<number>`.
- A bug fix needs a test that fails without the fix.
