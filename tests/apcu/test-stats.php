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

	/**
	 * The APCu numbers are the ones APCu reports, worked out and formatted as documented.
	 */
	public function test_apcu_numbers_are_exact() {
		$cache = $this->new_request();
		wp_cache_set( 'a', 'value', 'posts' );
		// Read straight from APCu: one hit, then more misses than hits.
		wp_cache_get( 'a', 'posts', true );
		for ( $i = 0; $i < 5; $i++ ) {
			wp_cache_get( 'missing' . $i, 'posts', true );
		}

		$output = $this->stats( $cache );

		$info   = apcu_cache_info( true );
		$sma    = apcu_sma_info( true );
		$total  = $sma['seg_size'] * $sma['num_seg'];
		$used   = $total - $sma['avail_mem'];
		$hits   = $info['num_hits'];
		$misses = $info['num_misses'];
		$this->assertGreaterThan( 0, $hits );
		$this->assertGreaterThan( $hits, $misses );

		$this->assertStringContainsString( '<strong>APCu Entries:</strong> ' . $info['num_entries'] . '<br />', $output );
		$this->assertStringContainsString(
			'<strong>APCu Memory:</strong> ' . number_format( $used / MB_IN_BYTES, 1 ) . ' MB used of ' . number_format( $total / MB_IN_BYTES, 1 ) . ' MB<br />',
			$output
		);
		$this->assertStringContainsString(
			'<strong>APCu Hit Rate:</strong> ' . number_format( $hits / ( $hits + $misses ) * 100, 1 ) . '%<br />',
			$output
		);
		$this->assertStringContainsString( '<strong>APCu Expunges:</strong> ' . $info['expunges'] . '<br />', $output );
		$this->assertStringEndsWith( '<strong>APCu Expunges:</strong> ' . $info['expunges'] . '<br /></p>', $output );
	}

	/**
	 * With no reads at all the hit rate is 0, not a division by zero.
	 */
	public function test_hit_rate_without_any_reads() {
		$cache = $this->new_request();

		$output = $this->stats( $cache );

		$this->assertStringContainsString( '<strong>APCu Hit Rate:</strong> 0.0%<br />', $output );
	}

	/**
	 * Group sizes are shown in kilobytes with two decimals.
	 */
	public function test_group_sizes_have_two_decimals() {
		$cache = $this->new_request( false );
		wp_cache_set( 'a', str_repeat( 'x', 1500 ), 'posts' );

		$grouped = $this->call_private( $cache, 'get_grouped_cache' );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$size = strlen( serialize( $grouped['posts'] ) );

		$this->assertStringContainsString(
			'<li><strong>Group:</strong> posts - ( ' . number_format( $size / KB_IN_BYTES, 2 ) . 'k )</li>',
			$this->stats( $cache )
		);
	}

	/**
	 * A key belongs to the longest group name that matches, and to no other.
	 */
	public function test_a_key_is_filed_under_one_group_only() {
		$cache = $this->new_request();
		wp_cache_set( 'y', 'one', 'a' );
		wp_cache_set( 'x', 'two', 'a:b' );

		$this->assertSame(
			array(
				'a'   => array( 'y' => 'one' ),
				'a:b' => array( 'x' => 'two' ),
			),
			$this->call_private( $cache, 'get_grouped_cache' )
		);
	}

	/**
	 * The longest matching group wins whichever group was used first.
	 */
	public function test_the_longest_group_wins_whatever_the_order() {
		$cache = $this->new_request();
		wp_cache_set( 'x', 'two', 'a:b' );
		wp_cache_set( 'y', 'one', 'a' );
		wp_cache_set( 'z', 'three', 'a:b:c' );

		$this->assertSame(
			array(
				'a:b'   => array( 'x' => 'two' ),
				'a'     => array( 'y' => 'one' ),
				'a:b:c' => array( 'z' => 'three' ),
			),
			$this->call_private( $cache, 'get_grouped_cache' )
		);
	}

	/**
	 * A key salt or group name made of regular expression characters is matched literally.
	 */
	public function test_special_characters_in_the_salt_and_group_names() {
		$cache = $this->new_request();
		$this->set_private( $cache, 'key_salt', 'a.b+c(d)' );
		wp_cache_set( 'k', 'one', 'g(1)' );
		wp_cache_set( 'k', 'two', 'g.1' );
		wp_cache_set( 'k', 'three', 'gx1' );

		$this->assertSame(
			array(
				'g(1)' => array( 'k' => 'one' ),
				'g.1'  => array( 'k' => 'two' ),
				'gx1'  => array( 'k' => 'three' ),
			),
			$this->call_private( $cache, 'get_grouped_cache' )
		);
	}

	/**
	 * Entries of another install that share the cache's start are not counted as ours.
	 */
	public function test_another_installs_entries_are_not_listed() {
		$cache = $this->new_request();
		$this->set_private( $cache, 'key_salt', 'abbcd' );
		$cache->set( 'k', 'mine', 'posts' );
		$this->set_private( $cache, 'key_salt', 'a.b+c(d)' );

		$this->assertSame( array(), $this->call_private( $cache, 'get_grouped_cache' ) );
	}
}
