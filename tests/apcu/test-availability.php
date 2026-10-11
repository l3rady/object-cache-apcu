<?php
/**
 * Tests for deciding whether APCu can be used (issue #28).
 *
 * Each case runs the drop-in in a new PHP process, because the APCu settings
 * cannot be changed once PHP has started.
 *
 * @package object-cache-apcu
 */

/**
 * The drop-in must use APCu only if APCu really works, and keep working if not.
 */
class Test_Availability extends APCu_Test_Case {

	/**
	 * Runs the probe with the given php.ini settings.
	 *
	 * @param string[] $settings php.ini settings, like 'apc.enable_cli=0'.
	 * @return array What the probe reported.
	 */
	private function probe( array $settings ) {
		$command = escapeshellarg( PHP_BINARY );
		foreach ( $settings as $setting ) {
			$command .= ' -d ' . escapeshellarg( $setting );
		}
		$command .= ' ' . escapeshellarg( __DIR__ . '/fixtures/cli-probe.php' ) . ' 2>&1';

		$output = shell_exec( $command ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec -- Needed to start PHP with other settings.
		$result = json_decode( (string) $output, true );

		$this->assertIsArray( $result, 'The probe did not print JSON: ' . $output );

		return $result;
	}

	/**
	 * With APCu working, it is used.
	 */
	public function test_apcu_is_used_when_it_works() {
		$result = $this->probe( array( 'apc.enabled=1', 'apc.enable_cli=1' ) );

		$this->assertTrue( $result['available'] );
		$this->assertTrue( $result['set'] );
		$this->assertSame( 'value', $result['get'] );
		$this->assertFalse( $result['add_again'] );
	}

	/**
	 * In the CLI without apc.enable_cli, apc.enabled still reads 1 but APCu does nothing.
	 * The drop-in must notice and fall back to memory instead of failing every write.
	 */
	public function test_cli_without_enable_cli_falls_back_to_memory() {
		$result = $this->probe( array( 'apc.enabled=1', 'apc.enable_cli=0' ) );

		$this->assertFalse( $result['available'], 'APCu is switched off for the CLI.' );
		$this->assertTrue( $result['set'], 'Writes must still work.' );
		$this->assertSame( 'value', $result['get'], 'Reads must still work.' );
		$this->assertFalse( $result['add_again'] );
	}

	/**
	 * Turned off entirely.
	 */
	public function test_apc_enabled_off_falls_back_to_memory() {
		$result = $this->probe( array( 'apc.enabled=0', 'apc.enable_cli=1' ) );

		$this->assertFalse( $result['available'] );
		$this->assertTrue( $result['set'] );
		$this->assertSame( 'value', $result['get'] );
	}

	/**
	 * A write APCu refuses (for example because it is full) must not leave this request
	 * serving its own older copy: the next read asks APCu, which has the real state.
	 */
	public function test_a_refused_write_drops_the_local_copy() {
		$cache = $this->new_request();
		wp_cache_set( 'key', 'old', 'posts' );

		// Another process changes the value, then our write is refused.
		apcu_store( $this->call_private( $cache, 'build_key', array( 'key', 'posts' ) ), 'theirs' );

		// A value APCu cannot store makes the write fail; it also warns, which is expected here.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Hides the expected warning.
		set_error_handler(
			static fn() => true
		);
		$stored = wp_cache_set( 'key', fopen( 'php://memory', 'r' ), 'posts' );
		restore_error_handler();

		$this->assertFalse( $stored );
		$this->assertSame( 'theirs', wp_cache_get( 'key', 'posts' ) );
	}
}
