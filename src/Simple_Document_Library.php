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

	use Document_Query;

	public $args         = [];
	public $post_args    = [];
	private $total_posts = null;
	private $table_id    = null;
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

	public function __construct( $args, $table_id = null ) {
		$this->args = $this->validate_options( $args );
		$this->table_id = $table_id ? $table_id : 'document-library-' . self::$table_count;
		$this->set_post_args();
	}

	/**
	 * Retrieves a data table containing a list of posts based on the specified arguments.
	 *
	 * @return string The posts table HTML output
	 */
	public function get_table( $output_type = 'html' ) {
		$columns = $this->get_columns();

		// Parse DataTables parameters from the AJAX request
		$draw   = isset( $_POST['draw'] ) ? intval( $_POST['draw'] ) : 1;
		$this->args['offset']  = isset( $_POST['start'] ) ? intval( $_POST['start'] ) : 0;
		$this->args['rows_per_page'] = isset( $_POST['length'] ) && intval( $_POST['length'] ) !== -1 ? intval( $_POST['length'] ) : $this->args['rows_per_page'];
		$this->args['sort_by'] = isset( $_POST['order'] ) ? $columns[$_POST['order'][0]['column']] : $this->get_orderby();
    	$this->args['sort_order'] = isset( $_POST['order'] ) ? sanitize_key( $_POST['order'][0]['dir'] ) : $this->args['sort_order'];
    	$this->args['search_value'] = isset( $_POST['search']['value'] ) ? sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) : '';
		$this->args['rows_per_page'] = filter_var( $this->args['rows_per_page'], FILTER_VALIDATE_INT );

		if ( $this->args['rows_per_page'] < 1 || ! $this->args['rows_per_page'] ) {
			$this->args['rows_per_page'] = false;
		}
		if( isset( $_POST['category'] ) ) {
			$this->args['doc_category'] = sanitize_text_field( wp_unslash( $_POST['category'] ) );
		}

		// Validate sort_by against allowed WordPress orderby values
		$allowed_orderby = [ 'title', 'id', 'date', 'modified', 'menu_order', 'author', 'rand' ];
		if ( ! in_array( $this->args['sort_by'], $allowed_orderby, true ) ) {
			$this->args['sort_by'] = Options::get_default_settings()['sort_by'];
		}

		if ( ! in_array( $this->args['sort_order'], [ 'asc', 'desc' ], true ) ) {
			$this->args['sort_order'] = Options::get_default_settings()['sort_order'];
		}

		// Set default sort direction
		if ( ! $this->args['sort_order'] ) {
			if ( $this->args['sort_by'] === 'date' ) {
				$this->args['sort_order'] = 'desc';
			} else {
				$this->args['sort_order'] = 'asc';
			}
		}

		$this->args['content_length']  = filter_var( $this->args['content_length'], FILTER_VALIDATE_INT );
		$this->args['scroll_offset']   = filter_var( $this->args['scroll_offset'], FILTER_VALIDATE_INT );

		if ( empty( $this->args['date_format'] ) ) {
			$this->args['date_format'] = Options::get_default_settings()['date_format'];
		}

		$output       = '';
		$table_body   = '';
		$body_row_fmt = '';

		// After an AJAX request, the paramaters should be set again
		$this->set_post_args();
		// Get all published posts in the current language
		$all_posts = $this->run_table_query( $this->build_table_query( $this->post_args ) );

		// Bail early if no posts found
		if ( ! $all_posts || ! is_array( $all_posts ) ) {
			return $output;
		}
		
		// Add placeholder to table body format string so that content for this column is included in table output
		$cell_fmt     = '<td>{%s}</td>';
		$array_output = [];
		foreach ( $columns as $column ) {
			$body_row_fmt .= sprintf( $cell_fmt, $column );
		}
		if ( $output_type === 'html' ) {
			// Build table body
			$body_row_fmt = '<tr>' . $body_row_fmt . '</tr>';

			// Loop through posts and add a row for each
			foreach ( (array) $all_posts as $_post ) {
				setup_postdata( $_post );

				$post_data_trans = apply_filters(
					'document_library_table_row_data_format',
					$this->get_row_content( $_post )
				);

				$table_body .= strtr( $body_row_fmt, $post_data_trans );
			} // foreach post

			wp_reset_postdata();
			return $table_body;
		} else {
			// Loop through posts and add a row for each
			foreach ( (array) $all_posts as $_post ) {
				setup_postdata( $_post );
				$array_output[] = $this->get_row_content( $_post );
			}
		}

		// Increment the table count
		++self::$table_count;
		if ( $output_type === 'html' ) {
			return apply_filters( 'document_library_table_html_output', $output, $this->args );
		} else {
			$total_posts = $this->get_total_posts();

			// Prepare the response
			$response = [
				'draw'            => $draw,
				'recordsTotal'    => $total_posts,
				'recordsFiltered' => $total_posts, // You can filter further if you add search functionality
				'data'            => $array_output,
			];

			wp_send_json( $response );

			// return "";
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
				'class'                   => '',
			];

			// Caption fallback
			$atts['data-caption'] = empty( $atts['data-caption'] ) ? trim( esc_attr( wp_strip_all_tags( $post->post_title ) ) ) : $atts['data-caption'];

			// Alt fallbacks
			$atts['alt'] = empty( $atts['alt'] ) ? $atts['data-caption'] : $atts['alt'];
			$atts['alt'] = empty( $atts['alt'] ) ? $atts['title'] : $atts['alt'];
			$atts['alt'] = empty( $atts['alt'] ) ? trim( esc_attr( wp_strip_all_tags( $post->post_title ) ) ) : $atts['alt'];

			// Get the image to display. Use the configured WxH image size, falling back to 'thumbnail'.
			$image_size = $this->parse_image_size( isset( $args['image_size'] ) ? $args['image_size'] : '' );
			$image = wp_get_attachment_image( $attachment_id, apply_filters( 'document_library_image_table_size', $image_size ), false, $atts );
		}

		// Wrap image with lightbox markup or post link - lightbox takes priority over the 'links' option.
		if ( $args['lightbox'] && $attachment_id ) {
			$image = sprintf( '<a class="dlw-lightbox" href="%1$s">%2$s</a>', esc_url( $full_src[0] ), $image );
		}

		return apply_filters( 'document_library_table_image', $image, $post );
	}

	/**
	 * Parse a "WxH" image size string into a [ width, height ] array for wp_get_attachment_image().
	 *
	 * Falls back to the 'thumbnail' size keyword when the value is empty or malformed.
	 *
	 * @param string $image_size The image size string, e.g. '80x80'.
	 * @return array|string A [ width, height ] array, or the 'thumbnail' size keyword.
	 */
	private function parse_image_size( $image_size ) {
		if ( is_string( $image_size ) && preg_match( '/^\s*(\d+)\s*[xX]\s*(\d+)\s*$/', $image_size, $matches ) ) {
			return [ (int) $matches[1], (int) $matches[2] ];
		}

		return 'thumbnail';
	}

	/**
	 * Retrieve the post content, truncated to the number of words specified by $num_words.
	 *
	 * Must be called with the Loop or a secondary loop after a call to setup_postdata().
	 *
	 * @param int $num_words The number of words to trim the content to
	 * @return string The (truncated) post content
	 */
	private function get_post_content( $post_id, $num_words = 15 ) {
		$text = get_the_content( '', false, $post_id );
		$text = strip_shortcodes( $text );
		$text = apply_filters( 'the_content', $text );

		if ( $num_words > 0 ) {
			$text = wp_trim_words( $text, $num_words, ' &hellip;' );
		}
		return $text;
	}

	public function get_orderby() {
		if ( ! isset( $this->args['sort_by'] ) || strlen( $this->args['sort_by'] ) < 1 ) {
			return Options::get_defaults()['sort_by'];
		} else {
			return $this->args['sort_by'];
		}
	}

	public function get_attributes() {
		$paging_attr = 'false';

		if ( $this->args['rows_per_page'] && $this->args['rows_per_page'] < $this->get_total_posts( $this->post_args ) ) {
			$paging_attr = 'true';
		}

		$offset_attr = ( $this->args['scroll_offset'] === false ) ? 'false' : $this->args['scroll_offset'];
		$table_class = 'document-library-table';

		if ( ! $this->args['wrap'] ) {
			$table_class .= ' nowrap';
		}
		$table_attributes = sprintf(
			'id="%1$s" class="%2$s" data-page-length="%3$u" data-paging="%4$s" data-click-filter="%5$s" data-scroll-offset="%6$s" cellspacing="0" width="100%%"',
			esc_attr( $this->table_id ),
			esc_attr( $table_class ),
			esc_attr( $this->args['rows_per_page'] ),
			esc_attr( $paging_attr ),
			esc_attr( $this->args['search_on_click'] ? 'true' : 'false' ),
			esc_attr( $offset_attr )
		);

		return $table_attributes;
	}

	public function get_headers() {
		$columns = $this->get_columns();
		$column_defaults = apply_filters( 'document_library_table_column_defaults_' . self::$table_count, apply_filters( 'document_library_table_column_defaults', Options::get_column_defaults() ) );
		// Build table header
		$heading_fmt  = '<th data-name="%1$s" data-priority="%2$u" data-width="%3$s"%5$s data-orderable="%6$s">%4$s</th>';
		$table_head   = '';
		foreach ( $columns as $column ) {
			// Double-check column name is valid
			if ( ! in_array( $column, Options::get_allowed_columns(), true ) ) {
				continue;
			}

			// Do we need to use custom data for ordering this column?
			$order_data = '';

			// Add heading to table
			$table_head .= sprintf( $heading_fmt, $column, $column_defaults[ $column ]['priority'], $column_defaults[ $column ]['width'], $column_defaults[ $column ]['heading'], $order_data, $column_defaults[ $column ]['orderable'] );

		}

		$table_head = sprintf( '<thead><tr>%s</tr></thead>', $table_head );

		return $table_head;
	}

	public function get_columns() {
		if ( empty( $this->args['columns'] ) ) {
			$this->args['columns'] = Options::get_default_settings()['columns'];
		}
		// Get the columns to be used in this table
		$columns = array_filter( array_map( 'trim', explode( ',', strtolower( $this->args['columns'] ) ) ) );
		$columns = array_intersect( $columns, Options::get_allowed_columns() );

		if ( empty( $columns ) ) {
			$columns = explode( ',', Options::get_default_settings()['columns'] );
		}
		return $columns;
	}

	public function get_row_content( $_post ) {
		
		$columns = $this->get_columns();

		$row_content = [];

		if( ! isset( $_post ) ) {
			return $row_content;
		}
		$document = new Document( $_post->ID );

		foreach ( $columns as $column ) {
			switch ( $column ) {
				case 'id':
					$row_content['id'] = $_post->ID;
					break;
				case 'image':
					$row_content['image'] = $this->get_image( $_post, $this->args );
					break;
				case 'title':
					$row_content['title'] = get_the_title( $_post );
					break;
				case 'doc_categories':
					$row_content['doc_categories'] = $this->get_doc_categories( $_post );
					break;
				case 'date':
					$row_content['date'] = get_the_date( $this->args['date_format'], $_post );
					break;
				case 'content':
					$row_content['content'] = $this->get_post_content( $_post->ID, $this->args['content_length'] );
					break;
				case 'link':
					$link_icon = isset( $this->args['link_icon'] ) ? $this->args['link_icon'] : false;
					$row_content['link'] = $document->get_download_button( $this->args['link_text'], $this->args['link_style'], $link_icon );
					break;
				default:
					break;
			}
		}

		if( ! $this->args[ 'lazy_load' ] ) {
			foreach( $row_content as $key => $value ) {
				unset( $row_content[ $key ] );
				$row_content[ '{'. $key .'}' ] = $value;
			}
		}

		return $row_content;
	}

	public function get_doc_categories( $post ) {
		if( get_the_term_list( $post->ID, Taxonomies::CATEGORY_SLUG, '', ', ' ) ) {
			return get_the_term_list( $post->ID, Taxonomies::CATEGORY_SLUG, '', ', ' );
		}
		else {
			return '';
		}
	}

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

		if( isset( $this->args['search_value'] ) && strlen( $this->args['search_value'] ) > 0 ) {
			$this->post_args['s'] = $this->args['search_value'];
		}
	}

	public function validate_options( $args ) {
		// Validate all the boolean options in the database
		$boolean_options = [ 'lazy_load', 'lightbox', 'wrap', 'search_on_click', 'link_icon' ];

		foreach( $boolean_options as $option ) {
			if ( isset( $args[ $option ] ) ) {
				// Handle various truthy values: '1', 1, 'true', true
				$args[ $option ] = filter_var( $args[ $option ], FILTER_VALIDATE_BOOLEAN );
			} else {
				$args[ $option ] = false;
			}
		}

		// The post status can only have these values
		$valid_post_statuses = [ 'publish', 'pending', 'draft', 'future', 'any' ];
		$args[ 'status' ] = in_array( $args['status'], $valid_post_statuses ) ? $args[ 'status' ] : 'publish';
			
		return $args;
	}

	public function get_id() {
		return $this->table_id;
	}
}
