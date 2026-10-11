<?php
/**
 * Tests for the stats() output.
 *
 * @package object-cache-apcu
 */

/**
 * One line per group with its size, like core, plus how APCu is doing (issue #32).
 */
class Test_Stats extends APCu_Test_Case {

	/**
	 * Captures the output of stats().
	 *
	 * @param WP_Object_Cache $cache Cache to report on.
	 * @return string
	 */
	private function stats( $cache ) {
		ob_start();
		$cache->stats();

		return ob_get_clean();
	}

	/**
	 * Extracts the names of the listed groups, in order.
	 *
	 * @param string $output Output of stats().
	 * @return string[]
	 */
	private function listed_groups( $output ) {
		preg_match_all( '/<li><strong>Group:<\/strong> (.*?) - \( [\d.]+k \)<\/li>/', $output, $matches );

		return $matches[1];
	}

	/**
	 * Each group is listed once with its size, not one line per key.
	 */
	public function test_lists_each_group_once() {
		$cache = $this->new_request();
		wp_cache_set( 'a', 'value', 'posts' );
		wp_cache_set( 'b', 'value', 'posts' );
		wp_cache_set( 'c', 'value', 'comments' );

		$output = $this->stats( $cache );

		$this->assertSame( array( 'comments', 'posts' ), $this->listed_groups( $output ) );
		$this->assertSame( 2, substr_count( $output, '<li>' ) );
	}

	/**
	 * Non persistent groups are listed too, and so is everything when APCu is unavailable.
	 */
	public function test_includes_non_persistent_groups() {
		$cache = $this->new_request();
		wp_cache_set( 'a', 'value', 'np' );
		wp_cache_set( 'b', 'value', 'posts' );

		$this->assertSame( array( 'np', 'posts' ), $this->listed_groups( $this->stats( $cache ) ) );
	}

	/**
	 * Without APCu everything is in memory and is listed.
	 */
	public function test_without_apcu() {
		$cache = $this->new_request( false );
		wp_cache_set( 'a', 'value', 'posts' );

		$output = $this->stats( $cache );

		$this->assertSame( array( 'posts' ), $this->listed_groups( $output ) );
		$this->assertStringNotContainsString( 'APCu Entries', $output );
	}

	/**
	 * The version counters stored beside the data are not shown as groups.
	 */
	public function test_hides_version_counters() {
		$cache = $this->new_request( false );
		wp_cache_set( 'a', 'value', 'posts' );
		wp_cache_flush_group( 'posts' );
		wp_cache_set( 'a', 'value', 'posts' );

		$this->assertSame( array( 'posts' ), $this->listed_groups( $this->stats( $cache ) ) );
	}

	/**
	 * Group names that start like another group's name, including ones with a colon.
	 */
	public function test_similar_and_colon_group_names() {
		$cache = $this->new_request();
		wp_cache_set( 'x', 'value', 'user' );
		wp_cache_set( 'x', 'value', 'user_meta' );
		wp_cache_set( 'x', 'value', 'a' );
		wp_cache_set( 'x', 'value', 'a:b' );

		$this->assertSame( array( 'a', 'a:b', 'user', 'user_meta' ), $this->listed_groups( $this->stats( $cache ) ) );
	}

	/**
	 * Group names are escaped.
	 */
	public function test_escapes_group_names() {
		$cache = $this->new_request();
		wp_cache_set( 'a', 'value', '<script>alert(1)</script>' );

		$output = $this->stats( $cache );

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( '&lt;script&gt;', $output );
	}

	/**
	 * Hits, misses and the group sizes reflect what happened.
	 */
	public function test_hits_misses_and_sizes() {
		$cache = $this->new_request();
		wp_cache_set( 'a', str_repeat( 'x', 2048 ), 'posts' );
		wp_cache_get( 'a', 'posts' );
		wp_cache_get( 'missing', 'posts' );

		$output = $this->stats( $cache );

		$this->assertStringContainsString( '<strong>Cache Hits:</strong> 1<br />', $output );
		$this->assertStringContainsString( '<strong>Cache Misses:</strong> 1<br />', $output );
		preg_match( '/ - \( ([\d.]+)k \)/', $output, $size );
		$this->assertGreaterThan( 2.0, (float) $size[1] );
	}

	/**
	 * With the local cache off nothing is kept in memory, so no groups are listed, but APCu is.
	 */
	public function test_without_a_local_cache_only_apcu_is_reported() {
		$cache = $this->new_request( true, false );
		wp_cache_set( 'a', 'value', 'posts' );

		$output = $this->stats( $cache );

		$this->assertSame( array(), $this->listed_groups( $output ) );
		$this->assertMatchesRegularExpression( '/APCu Entries:<\/strong> [1-9]/', $output );
	}

	/**
	 * The APCu numbers are shown.
	 */
	public function test_reports_apcu() {
		$cache = $this->new_request();
		wp_cache_set( 'a', 'value', 'posts' );

		$output = $this->stats( $cache );

		$this->assertStringContainsString( '<strong>APCu Entries:</strong>', $output );
		$this->assertMatchesRegularExpression( '/APCu Memory:<\/strong> [\d.]+ MB used of [\d.]+ MB/', $output );
		$this->assertMatchesRegularExpression( '/APCu Hit Rate:<\/strong> [\d.]+%/', $output );
		$this->assertStringContainsString( '<strong>APCu Expunges:</strong> ', $output );
	}
}
