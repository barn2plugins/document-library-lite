<?php

namespace Barn2\Plugin\Document_Library;

/**
 * Shared document query logic used by the table and grid renderers.
 *
 * Consuming classes must declare the `$args`, `$post_args` and `$total_posts` properties.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
trait Document_Query {

	/**
	 * Generate an inner array for the 'tax_query' arg in WP_Query.
	 *
	 * @param string $terms    The list of terms as a string
	 * @param string $taxonomy The taxonomy name
	 * @param string $operator The SQL operator: IN, NOT IN, AND, etc
	 * @param string $field    Add tax query by `term_id` or `slug`. Leave empty to auto-detect correct type
	 * @return array A tax query sub-array
	 */
	private function tax_query_item( $terms, $taxonomy, $operator = 'IN', $field = '' ) {
		$and_relation = 'AND' === $operator;

		// comma-delimited list = OR, plus-delimited = AND
		if ( ! is_array( $terms ) ) {
			if ( false !== strpos( $terms, '+' ) ) {
				$terms        = explode( '+', $terms );
				$and_relation = true;
			} else {
				$terms = explode( ',', $terms );
			}
		}

		// Do we have slugs or IDs?
		if ( ! $field ) {
			$using_term_ids = count( $terms ) === count( array_filter( $terms, 'is_numeric' ) );
			$field          = $using_term_ids && ( ! isset( $this->args['numeric_terms'] ) || ! $this->args['numeric_terms'] ) ? 'term_id' : 'slug';
		}

		// Strange bug when using operator => 'AND' in individual tax queries -
		// We need to separate these out into separate 'IN' arrays joined by and outer relation => 'AND'
		if ( $and_relation && count( $terms ) > 1 ) {
			$result = [ 'relation' => 'AND' ];

			foreach ( $terms as $term ) {
				$result[] = [
					'taxonomy' => $taxonomy,
					'terms'    => $term,
					'operator' => 'IN',
					'field'    => $field,
				];
			}

			return $result;
		} else {
			return [
				'taxonomy' => $taxonomy,
				'terms'    => $terms,
				'operator' => $operator,
				'field'    => $field,
			];
		}
	}

	/**
	 * Run the posts query.
	 *
	 * @param array $query_args
	 * @return \WP_Post[]
	 */
	public function run_table_query( $query_args ) {
		do_action( 'document_library_before_posts_query', $this );

		$query = get_posts( $query_args );

		do_action( 'document_library_after_posts_query', $this );

		return $query;
	}

	/**
	 * Build the posts query args, applying lazy-load / post-limit constraints.
	 *
	 * @param array $query_args
	 * @return array
	 */
	public function build_table_query( $query_args ) {
		if ( $this->args['lazy_load'] ) {
			// Ensure rows per page doesn't exceed post limit
			$query_args['posts_per_page'] = $this->check_within_post_limit( $this->args['rows_per_page'] );
			$query_args['offset']         = $this->args['offset'];
		} else {
			$query_args['posts_per_page'] = $this->args['post_limit'];
		}
		return apply_filters( 'document_library_table_query_args', $query_args, $this );
	}

	/**
	 * Constrain a count to the configured post limit.
	 *
	 * @param int $count
	 * @return int
	 */
	private function check_within_post_limit( $count ) {
		return is_int( $this->args['post_limit'] ) && $this->args['post_limit'] > 0 ? min( $this->args['post_limit'], $count ) : $count;
	}

	/**
	 * Get the total number of posts matching the query.
	 *
	 * @return int
	 */
	public function get_total_posts() {
		if ( is_numeric( $this->total_posts ) ) {
			return $this->total_posts;
		}

		$total = 0;

		$total_query = new \WP_Query( $this->build_post_totals_query( $this->post_args ) );
		$total       = $total_query->post_count;

		$this->total_posts = $this->check_within_post_limit( $total );

		return $this->total_posts;
	}

	/**
	 * Build the query args used to count the total posts.
	 *
	 * @param array $args
	 * @return array
	 */
	private function build_post_totals_query( $args ) {
		$query_args                   = $this->build_table_query( $args );
		$query_args['offset']         = 0;
		$query_args['posts_per_page'] = -1;
		$query_args['fields']         = 'ids';

		return apply_filters( 'document_library_query_args', $query_args, $this );
	}

	/**
	 * Build the base post query args from the current settings.
	 */
	public function set_post_args() {
		// Start building the args needed for our posts query
		$this->post_args = [
			'post_type'        => Post_Type::POST_TYPE_SLUG,
			// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'posts_per_page'   => apply_filters( 'document_library_table_post_limit', 1000 ),
			'post_status'      => $this->args['status'],
			'order'            => strtoupper( $this->args['sort_order'] ),
			'orderby'          => $this->args['sort_by'],
			'suppress_filters' => false, // Ensure WPML filters run on this query
		];

		// Add our doc_category if we have one.
		if ( isset( $this->args['doc_category'] ) && strlen( $this->args['doc_category'] ) > 0 ) {
			$this->post_args = array_merge(
				$this->post_args,
				[ 'tax_query' => [ $this->tax_query_item( $this->args['doc_category'], 'doc_categories' ) ] ]
			);
		}

		if ( isset( $this->args['search_value'] ) && strlen( $this->args['search_value'] ) > 0 ) {
			$this->post_args['s'] = $this->args['search_value'];
		}
	}
}
