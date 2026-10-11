// Commit message rules, run by `make commits` and in CI. release-please builds the
// changelog and the next version from these messages, so they must parse.
// What a tool cannot check: that the summary is in the imperative mood, and that the
// body explains why. Reviewers do that.

const breakingMarker = /^\w+(\([^)]*\))?!:/;

module.exports = {
	// For its parser, which understands "feat!:". The rules below replace its own.
	extends: ['@commitlint/config-conventional'],
	rules: {
		'type-empty': [2, 'never'],
		'type-case': [2, 'always', 'lower-case'],
		'type-enum': [2, 'always', ['feat', 'fix', 'docs', 'style', 'refactor', 'perf', 'test', 'build', 'ci', 'chore', 'revert']],
		'scope-case': [2, 'always', 'lower-case'],
		'subject-empty': [2, 'never'],
		'subject-full-stop': [2, 'never', '.'],
		'subject-case': [2, 'never', ['sentence-case', 'start-case', 'pascal-case', 'upper-case']],
		'header-max-length': [2, 'always', 72],
		'body-leading-blank': [2, 'always'],
		// Off because the parser takes a body line like "segment: text" for a footer. The
		// footer that matters, BREAKING CHANGE, gets the same check in the rule below.
		'footer-leading-blank': [0],
		// Long lines are fine: trailers carry URLs, and bots write their own bodies.
		'body-max-line-length': [0],
		'footer-max-line-length': [0],
		'breaking-change-marked-twice': [2, 'always'],
	},
	plugins: [
		{
			rules: {
				// "!" in the header and a "BREAKING CHANGE:" footer go together, so the
				// change is visible in the log and in the release notes.
				'breaking-change-marked-twice': (parsed) => {
					const marked = breakingMarker.test(parsed.header || '');
					// The parser also reports a "!" as a breaking note, so look at the text itself.
					const raw = parsed.raw || '';
					const footer = /^BREAKING[ -]CHANGE:/m.test(raw);

					if (marked && !footer) {
						return [false, 'a "!" in the header needs a "BREAKING CHANGE:" footer that says what breaks'];
					}
					if (footer && !/\n\nBREAKING[ -]CHANGE:/.test(raw)) {
						return [false, 'the "BREAKING CHANGE:" footer must come after a blank line'];
					}
					if (footer && !marked) {
						return [false, 'a "BREAKING CHANGE:" footer needs a "!" before the colon in the header'];
					}
					return [true];
				},
			},
		},
	],
};
