<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Util\Options;

/**
 * This class is responsible for generating a HTML table from a list of supplied attributes.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Simple_Document_Library {

	/**
	 * Stores the number of tables on this page. Used to generate the table ID.
	 *
	 * @var int
	 */
	private static $table_count = 1;

	/**
	 * An array of all possible columns and their default heading, priority, and column width.
	 *
	 * @var array
	 */
	private static $column_defaults = [];

	/**
	 * An array of all allowed column keys.
	 *
	 * @var array
	 */
	private static $allowed_columns = [];

	/**
	 * Retrieves the column defaults for DataTables.
	 *
	 * @return array
	 */
	public static function get_column_defaults() {
		if ( empty( self::$column_defaults ) ) {
			/**
			 * Priority values are used to determine visiblity at small screen sizes (1 = highest priority, 6 = lowest priority).
			 * Column widths are automatically calculated by DataTables, but can be overridden by using filter 'rydocument_libra_table_column_defaults'.
			 */
			self::$column_defaults = [
				'id'             => [
					'heading'  => __( 'ID', 'document-library-lite' ),
					'priority' => 3,
					'width'    => '',
					'orderable'=> "true"
				],
				'image'          => [
					'heading'  => __( 'Image', 'document-library-lite' ),
					'priority' => 6,
					'width'    => '',
					'orderable'=> "false"
				],
				'title'          => [
					'heading'  => __( 'Title', 'document-library-lite' ),
					'priority' => 1,
					'width'    => '',
					'orderable'=> "true"
				],
				'doc_categories' => [
					'heading'  => __( 'Categories', 'document-library-lite' ),
					'priority' => 7,
					'width'    => '',
					'orderable'=> "true"
				],
				'date'           => [
					'heading'  => __( 'Date', 'document-library-lite' ),
					'priority' => 2,
					'width'    => '',
					'orderable'=> "true"
				],
				'content'        => [
					'heading'  => __( 'Description', 'document-library-lite' ),
					'priority' => 5,
					'width'    => '',
					'orderable'=> "true"
				],
				'link'           => [
					'heading'  => __( 'Link', 'document-library-lite' ),
					'priority' => 4,
					'width'    => '',
					'orderable'=> "false"
				]
			];
		}

		return self::$column_defaults;
	}

	/**
	 * Get the allowed columns for DataTables.
	 *
	 * @return array
	 */
	public static function get_allowed_columns() {
		if ( empty( self::$allowed_columns ) ) {
			self::$allowed_columns = array_keys( self::get_column_defaults() );
		}

		return self::$allowed_columns;
	}

	/**
	 * Retrieves a data table containing a list of posts based on the specified arguments.
	 *
	 * @param array $args An array of options used to display the posts table
	 * @return string The posts table HTML output
	 */
	public function get_table( $args ) {
		// Load the scripts and styles.
		if ( apply_filters( 'document_library_table_load_scripts', true ) ) {
			wp_enqueue_style( 'document-library' );
			wp_enqueue_script( 'document-library' );
		}

		$args = wp_parse_args( $args, Options::get_defaults() );

		Frontend_Scripts::load_photoswipe_resources( $args['lightbox'] );

		if ( empty( $args['columns'] ) ) {
			$args['columns'] = Options::get_default_settings()['columns'];
		}

		// Get the columns to be used in this table
		$columns = array_filter( array_map( 'trim', explode( ',', strtolower( $args['columns'] ) ) ) );
		$columns = array_intersect( $columns, self::get_allowed_columns() );

		if ( empty( $columns ) ) {
			$columns = explode( ',', Options::get_default_settings()['columns'] );
		}

		$args['rows_per_page'] = filter_var( $args['rows_per_page'], FILTER_VALIDATE_INT );

		if ( $args['rows_per_page'] < 1 || ! $args['rows_per_page'] ) {
			$args['rows_per_page'] = false;
		}

		if ( ! in_array( $args['sort_by'], self::get_allowed_columns(), true ) ) {
			$args['sort_by'] = Options::get_default_settings()['sort_by'];
		}

		if ( ! in_array( $args['sort_order'], [ 'asc', 'desc' ], true ) ) {
			$args['sort_order'] = Options::get_default_settings()['sort_order'];
		}

		// Set default sort direction
		if ( ! $args['sort_order'] ) {
			if ( $args['sort_by'] === 'date' ) {
				$args['sort_order'] = 'desc';
			} else {
				$args['sort_order'] = 'asc';
			}
		}

		$args['search_on_click'] = filter_var( $args['search_on_click'], FILTER_VALIDATE_BOOLEAN );
		$args['wrap']            = filter_var( $args['wrap'], FILTER_VALIDATE_BOOLEAN );
		$args['content_length']  = filter_var( $args['content_length'], FILTER_VALIDATE_INT );
		$args['scroll_offset']   = filter_var( $args['scroll_offset'], FILTER_VALIDATE_INT );

		if ( empty( $args['date_format'] ) ) {
			$args['date_format'] = Options::get_default_settings()['date_format'];
		}

		$output       = '';
		$table_head   = '';
		$table_body   = '';
		$body_row_fmt = '';

		// Start building the args needed for our posts query
		$post_args = [
			'post_type'        => Post_Type::POST_TYPE_SLUG,
			// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'posts_per_page'   => apply_filters( 'document_library_table_post_limit', 1000 ),
			'post_status'      => 'publish',
			'order'            => strtoupper( $args['sort_order'] ),
			'orderby'          => $this->get_orderby(),
			'suppress_filters' => false // Ensure WPML filters run on this query
		];

		// Add our doc_category if we have one.
		if ( isset( $args['doc_category'] ) && strlen( $args['doc_category'] ) > 0 ) {
			$post_args = array_merge(
				$post_args,
				[ 'tax_query' => [ $this->tax_query_item( $args['doc_category'], 'doc_categories' ) ] ]
			);
		}

		// Get all published posts in the current language
		$all_posts = get_posts( apply_filters( 'document_library_table_query_args', $post_args, $args ) );

		// Bail early if no posts found
		if ( ! $all_posts || ! is_array( $all_posts ) ) {
			return $output;
		}

		// Allow theme/plugins to override defaults
		$column_defaults = apply_filters( 'document_library_table_column_defaults_' . self::$table_count, apply_filters( 'document_library_table_column_defaults', self::get_column_defaults() ) );

		// Build table header
		$heading_fmt = '<th data-name="%1$s" data-priority="%2$u" data-width="%3$s"%5$s data-orderable="%6$s">%4$s</th>';
		$cell_fmt    = '<td>{%s}</td>';

		foreach ( $columns as $column ) {
			// Double-check column name is valid
			if ( ! in_array( $column, self::get_allowed_columns(), true ) ) {
				continue;
			}

			// Do we need to use custom data for ordering this column?
			$order_data = '';

			// Add heading to table
			$table_head .= sprintf( $heading_fmt, $column, $column_defaults[ $column ]['priority'], $column_defaults[ $column ]['width'], $column_defaults[ $column ]['heading'], $order_data, $column_defaults[ $column ]['orderable'] );

			// Add placeholder to table body format string so that content for this column is included in table output
			$body_row_fmt .= sprintf( $cell_fmt, $column );
		}

		$table_head = sprintf( '<thead><tr>%s</tr></thead>', $table_head );

		// Build table body
		$body_row_fmt = '<tr>' . $body_row_fmt . '</tr>';

		// Loop through posts and add a row for each
		foreach ( (array) $all_posts as $_post ) {
			setup_postdata( $_post );

			$document = new Document( $_post->ID );

			$post_data_trans = apply_filters(
				'document_library_table_row_data_format',
				[
					'{id}'             => $_post->ID,
					'{image}'          => $this->get_image( $_post, $args ),
					'{title}'          => get_the_title( $_post ),
					'{doc_categories}' => get_the_term_list( $_post->ID, Taxonomies::CATEGORY_SLUG, '', ', ' ),
					'{date}'           => get_the_date( $args['date_format'], $_post ),
					'{content}'        => $this->get_post_content( $args['content_length'] ),
					'{link}'           => $document->get_download_button( $args['link_text'] ),
				]
			);

			$table_body .= strtr( $body_row_fmt, $post_data_trans );
		} // foreach post

		wp_reset_postdata();

		$table_body = sprintf( '<tbody>%s</tbody>', $table_body );

		$paging_attr = 'false';

		if ( $args['rows_per_page'] && $args['rows_per_page'] < count( $all_posts ) ) {
			$paging_attr = 'true';
		}

		$offset_attr = ( $args['scroll_offset'] === false ) ? 'false' : $args['scroll_offset'];
		$table_class = 'document-library-table';

		if ( ! $args['wrap'] ) {
			$table_class .= ' nowrap';
		}

		$table_attributes = sprintf(
			'id="document-library-%1$u" class="%2$s" data-page-length="%3$u" data-paging="%4$s" data-click-filter="%5$s" data-scroll-offset="%6$s" data-order="[]" cellspacing="0" width="100%%"',
			self::$table_count,
			esc_attr( $table_class ),
			esc_attr( $args['rows_per_page'] ),
			esc_attr( $paging_attr ),
			esc_attr( $args['search_on_click'] ? 'true' : 'false' ),
			esc_attr( $offset_attr )
		);

		$output = sprintf( '<table %1$s>%2$s%3$s</table>', $table_attributes, $table_head, $table_body );

		// Increment the table count
		self::$table_count++;

		return apply_filters( 'document_library_table_html_output', $output, $args );
	}

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
			$field          = $using_term_ids && ! $this->args->numeric_terms ? 'term_id' : 'slug';
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
					'field'    => $field
				];
			}

			return $result;
		} else {
			return [
				'taxonomy' => $taxonomy,
				'terms'    => $terms,
				'operator' => $operator,
				'field'    => $field
			];
		}
	}

	/**
	 * Get the document featured image.
	 *
	 * @param WP_Post $post
	 * @param array $args
	 * @return string
	 */
	private function get_image( $post, $args ) {
		$attachment_id = get_post_thumbnail_id( $post->ID );

		$image = '';

		if ( $attachment_id ) {
			// Create $atts for PhotoSwipe
			$full_src = wp_get_attachment_image_src( $attachment_id, apply_filters( 'document_library_image_full_size', 'full' ) );
			$atts     = [
				'title'                   => get_post_field( 'post_title', $attachment_id ),
				'alt'                     => trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ),
				'data-caption'            => get_post_field( 'post_excerpt', $attachment_id ),
				'data-src'                => $full_src[0],
				'data-large_image'        => $full_src[0],
				'data-large_image_width'  => $full_src[1],
				'data-large_image_height' => $full_src[2],
				'class'                   => ''
			];

			// Caption fallback
			$atts['data-caption'] = empty( $atts['data-caption'] ) ? trim( esc_attr( wp_strip_all_tags( $post->post_title ) ) ) : $atts['data-caption'];

			// Alt fallbacks
			$atts['alt'] = empty( $atts['alt'] ) ? $atts['data-caption'] : $atts['alt'];
			$atts['alt'] = empty( $atts['alt'] ) ? $atts['title'] : $atts['alt'];
			$atts['alt'] = empty( $atts['alt'] ) ? trim( esc_attr( wp_strip_all_tags( $post->post_title ) ) ) : $atts['alt'];

			// Get the image to display
			$image = wp_get_attachment_image( $attachment_id, apply_filters( 'document_library_image_table_size', 'thumbnail' ), false, $atts );
		}

		// Wrap image with lightbox markup or post link - lightbox takes priority over the 'links' option.
		if ( $args['lightbox'] && $attachment_id ) {
			$image = sprintf( '<a class="dlw-lightbox" href="%1$s">%2$s</a>', esc_url( $full_src[0] ), $image );
		}

		return apply_filters( 'document_library_table_image', $image, $post );
	}

	/**
	 * Retrieve the post content, truncated to the number of words specified by $num_words.
	 *
	 * Must be called with the Loop or a secondary loop after a call to setup_postdata().
	 *
	 * @param int $num_words The number of words to trim the content to
	 * @return string The (truncated) post content
	 */
	private function get_post_content( $num_words = 15 ) {
		$text = get_the_content( '' );
		$text = strip_shortcodes( $text );
		$text = apply_filters( 'the_content', $text );

		if ( $num_words > 0 ) {
			$text = wp_trim_words( $text, $num_words, ' &hellip;' );
		}

		return $text;
	}

	public function get_orderby() {
		if( ! isset($args[ 'sort_by' ]) || strlen( $args[ 'sort_by' ] ) < 1 ) {
			return Options::get_defaults()['sort_by'];
		} else {
			return $args[ 'sort_by' ];
		}
	}

}
