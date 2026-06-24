<?php

namespace Barn2\Plugin\Document_Library;

use Barn2\Plugin\Document_Library\Util\Options;

/**
 * Renders a list of documents as a responsive grid of cards.
 *
 * Mirrors the architecture of Simple_Document_Library (Lite's bespoke table renderer) and shares its
 * query logic via the Document_Query trait. Grid pages are loaded one at a time over AJAX.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Simple_Document_Grid {

	use Document_Query;

	public $args         = [];
	public $post_args    = [];
	private $total_posts = null;
	private $grid_id     = null;

	/**
	 * The configured documents-per-page, captured before get_page_posts() mutates $args['rows_per_page'].
	 *
	 * @var int
	 */
	private $base_per_page = self::DEFAULT_PAGE_LENGTH;

	/**
	 * The fields that may be displayed on a grid card. 'id' and 'date' are intentionally excluded.
	 */
	const ALLOWED_FIELDS         = [ 'title', 'content', 'image', 'doc_categories', 'link' ];
	const ALLOWED_FIELDS_DEFAULT = 'image,title,content,link';

	/**
	 * The selectable page-length options, and the default when the configured value isn't one of them.
	 */
	const PAGE_LENGTHS        = [ 10, 25, 50, 100 ];
	const DEFAULT_PAGE_LENGTH = 10;

	/**
	 * Constructor.
	 *
	 * @param array  $args    The shortcode/settings arguments.
	 * @param string $grid_id A unique ID for this grid (the stored config ID).
	 */
	public function __construct( $args, $grid_id = null ) {
		$this->args    = $this->validate_options( $args );
		$this->grid_id = $grid_id ? $grid_id : 'document-library-grid';

		// Capture the configured page length now: get_page_posts() later overwrites $args['rows_per_page'].
		$this->base_per_page = isset( $this->args['rows_per_page'] ) ? (int) $this->args['rows_per_page'] : self::DEFAULT_PAGE_LENGTH;

		$this->set_post_args();
	}

	/**
	 * Validate the incoming options.
	 *
	 * @param array $args
	 * @return array
	 */
	public function validate_options( $args ) {
		$boolean_options = [ 'lightbox', 'link_icon', 'link_target', 'text_links_new_tab' ];

		foreach ( $boolean_options as $option ) {
			$args[ $option ] = isset( $args[ $option ] ) ? filter_var( $args[ $option ], FILTER_VALIDATE_BOOLEAN ) : false;
		}

		$valid_post_statuses = [ 'publish', 'pending', 'draft', 'future', 'any' ];
		$args['status']      = isset( $args['status'] ) && in_array( $args['status'], $valid_post_statuses, true ) ? $args['status'] : 'publish';

		$args['grid_columns'] = isset( $args['grid_columns'] ) ? max( 1, min( 6, (int) $args['grid_columns'] ) ) : 4;

		// Defaults to true (it is a general shortcode default) rather than the generic "missing => false".
		$args['search_on_click'] = isset( $args['search_on_click'] ) ? filter_var( $args['search_on_click'], FILTER_VALIDATE_BOOLEAN ) : true;

		if ( empty( $args['grid_content'] ) ) {
			$args['grid_content'] = self::ALLOWED_FIELDS_DEFAULT;
		}

		return $args;
	}

	/**
	 * Parse the grid_content setting into a validated list of fields to render on each card.
	 *
	 * Mirrors Simple_Document_Library::get_columns(): comma-separated, trimmed, lowercased, then
	 * intersected with the allowed grid fields. Falls back to the default set when empty/invalid.
	 *
	 * @return string[]
	 */
	public function get_grid_fields() {
		$raw = isset( $this->args['grid_content'] ) ? $this->args['grid_content'] : self::ALLOWED_FIELDS_DEFAULT;

		$fields = array_filter( array_map( 'trim', explode( ',', strtolower( $raw ) ) ) );
		$fields = array_values( array_intersect( $fields, self::ALLOWED_FIELDS ) );

		if ( empty( $fields ) ) {
			$fields = explode( ',', self::ALLOWED_FIELDS_DEFAULT );
		}

		return $fields;
	}

	/**
	 * Number of documents shown per grid page.
	 *
	 * @return int
	 */
	public function get_per_page() {
		$lengths   = $this->get_page_lengths();
		$requested = isset( $this->args['requested_length'] ) ? (int) $this->args['requested_length'] : $this->base_per_page;

		// A valid numeric selection always wins (lets a rows_per_page = -1 site still pick 10/25/50/100).
		if ( $requested > 0 && in_array( $requested, $lengths, true ) ) {
			return $requested;
		}

		// "All" — show every document on one page (only when the site is configured with rows_per_page = -1;
		// the AJAX handler never sets a negative requested_length).
		if ( -1 === $requested || -1 === $this->base_per_page ) {
			return -1;
		}

		return ( $this->base_per_page > 0 && in_array( $this->base_per_page, $lengths, true ) )
			? $this->base_per_page
			: self::DEFAULT_PAGE_LENGTH;
	}

	/**
	 * The selectable page-length options: the defaults plus the configured value when it isn't one of them,
	 * inserted in ascending order (e.g. rows_per_page=8 => [8,10,25,50,100]).
	 *
	 * @return int[]
	 */
	public function get_page_lengths() {
		$lengths = self::PAGE_LENGTHS;

		if ( $this->base_per_page > 0 && ! in_array( $this->base_per_page, $lengths, true ) ) {
			$lengths[] = $this->base_per_page;
			sort( $lengths, SORT_NUMERIC );
		}

		return $lengths;
	}

	/**
	 * Query the documents for a given page.
	 *
	 * @param int $page The 1-based page number.
	 * @return \WP_Post[]
	 */
	public function get_page_posts( $page = 1 ) {
		$page     = max( 1, (int) $page );
		$per_page = $this->get_per_page();

		// Force offset-based paging for the grid regardless of the table lazy-load setting.
		$this->args['lazy_load'] = true;

		if ( $per_page < 1 ) {
			// "All" — fetch every document on a single page.
			$this->args['rows_per_page'] = -1;
			$this->args['offset']        = 0;
		} else {
			$this->args['rows_per_page'] = $per_page;
			$this->args['offset']        = ( $page - 1 ) * $per_page;
		}

		$this->set_post_args();

		return $this->run_table_query( $this->build_table_query( $this->post_args ) );
	}

	/**
	 * Render the cards for a given page (the inner '.dlp-grid-documents' markup).
	 *
	 * @param int $page
	 * @return string
	 */
	public function get_grid_html( $page = 1 ) {
		$posts      = $this->get_page_posts( $page );
		$grid_class = sprintf( 'grid-columns columns-%d', (int) $this->args['grid_columns'] );
		$fields     = $this->get_grid_fields();

		$cards = '';

		if ( ! empty( $posts ) && is_array( $posts ) ) {
			foreach ( $posts as $_post ) {
				setup_postdata( $_post );
				$cards .= $this->get_card( $_post, $fields );
			}
			wp_reset_postdata();
		} else {
			$cards = sprintf( '<p class="dlp-grid-empty">%s</p>', esc_html__( 'No matching documents', 'document-library-lite' ) );
		}

		return sprintf( '<div class="dlp-grid-documents %s">%s</div>', esc_attr( $grid_class ), $cards );
	}

	/**
	 * Render a single grid card.
	 *
	 * @param \WP_Post $_post
	 * @return string
	 */
	public function get_card( $_post, $fields = null ) {
		if ( null === $fields ) {
			$fields = $this->get_grid_fields();
		}

		$document = new Document( $_post->ID );

		// Featured image (always rendered at the top of the card when enabled).
		$image  = in_array( 'image', $fields, true ) ? $this->get_card_image( $_post ) : '';
		$inner  = $image;
		$inner .= sprintf( '<div class="dlp-grid-card-content%s">', $image ? '' : ' no-image' );

		if ( in_array( 'doc_categories', $fields, true ) ) {
			$categories = $this->get_card_categories( $_post );
			if ( $categories ) {
				$inner .= sprintf( '<div class="dlp-grid-card-info"><div class="dlp-grid-card-categories">%s</div></div>', $categories );
			}
		}

		if ( in_array( 'title', $fields, true ) ) {
			$inner .= sprintf( '<div class="dlp-grid-card-title">%s</div>', $this->get_card_title( $_post ) );
		}

		if ( in_array( 'content', $fields, true ) ) {
			$excerpt = $this->get_card_excerpt( $_post );
			if ( '' !== $excerpt ) {
				$inner .= sprintf( '<div class="dlp-grid-card-excerpt">%s</div>', $excerpt );
			}
		}

		if ( in_array( 'link', $fields, true ) ) {
			$link = $this->get_card_download( $document );
			if ( $link ) {
				$inner .= sprintf( '<div class="dlp-grid-card-link">%s</div>', $link );
			}
		}

		$inner .= '</div>';

		$card = sprintf(
			'<div class="dlp-grid-card dlp-document-%1$s"><div class="dlp-grid-card-inner">%2$s</div></div>',
			esc_attr( $_post->post_name ),
			$inner
		);

		return apply_filters( 'document_library_grid_card', $card, $_post, $this->args );
	}

	/**
	 * Get the featured image (or file-type icon fallback) for a card.
	 *
	 * @param \WP_Post $post
	 * @return string
	 */
	private function get_card_image( $post ) {
		$attachment_id = get_post_thumbnail_id( $post->ID );

		if ( ! $attachment_id ) {
			$document = new Document( $post->ID );
			return sprintf(
				'<div class="dlp-grid-card-featured-img dlp-grid-card-featured-icon">%s</div>',
				$document->get_file_icon()
			);
		}

		$full_src = wp_get_attachment_image_src( $attachment_id, apply_filters( 'document_library_image_full_size', 'full' ) );
		$atts     = [
			'title'                   => get_post_field( 'post_title', $attachment_id ),
			'alt'                     => trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ),
			'data-caption'            => get_post_field( 'post_excerpt', $attachment_id ),
			'data-src'                => $full_src[0],
			'data-large_image'        => $full_src[0],
			'data-large_image_width'  => $full_src[1],
			'data-large_image_height' => $full_src[2],
		];

		$atts['data-caption'] = empty( $atts['data-caption'] ) ? trim( esc_attr( wp_strip_all_tags( $post->post_title ) ) ) : $atts['data-caption'];
		$atts['alt']          = empty( $atts['alt'] ) ? $atts['data-caption'] : $atts['alt'];

		$image = wp_get_attachment_image( $attachment_id, apply_filters( 'document_library_image_grid_size', 'large' ), false, $atts );

		// Lightbox wrapping mirrors the table renderer.
		if ( ! empty( $this->args['lightbox'] ) ) {
			$image = sprintf( '<a class="dlw-lightbox" href="%1$s">%2$s</a>', esc_url( $full_src[0] ), $image );
		}

		return sprintf( '<div class="dlp-grid-card-featured-img">%s</div>', $image );
	}

	/**
	 * Get the category list for a card, honouring the text-links new-tab option.
	 *
	 * @param \WP_Post $post
	 * @return string
	 */
	private function get_card_categories( $post ) {
		$terms = get_the_terms( $post->ID, Taxonomies::CATEGORY_SLUG );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return '';
		}

		// When search-on-click is enabled the link filters the grid (JS intercepts); otherwise it is a
		// plain link to the category archive page.
		$click_filter = ! empty( $this->args['search_on_click'] );
		$target       = ! empty( $this->args['text_links_new_tab'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$links        = [];

		foreach ( $terms as $term ) {
			$term_link = get_term_link( $term );

			if ( is_wp_error( $term_link ) ) {
				continue;
			}

			if ( $click_filter ) {
				// The link carries the slug so the frontend JS can filter the grid by this category.
				$links[] = sprintf(
					'<a href="%1$s" class="dlp-grid-category-link" data-slug="%2$s"%3$s>%4$s</a>',
					esc_url( $term_link ),
					esc_attr( $term->slug ),
					$target,
					esc_html( $term->name )
				);
			} else {
				$links[] = sprintf(
					'<a href="%1$s"%2$s>%3$s</a>',
					esc_url( $term_link ),
					$target,
					esc_html( $term->name )
				);
			}
		}

		return implode( ', ', $links );
	}

	/**
	 * Get the linked title for a card.
	 *
	 * @param \WP_Post $post
	 * @return string
	 */
	private function get_card_title( $post ) {
		return esc_html( get_the_title( $post ) );
	}

	/**
	 * Get the truncated excerpt/content for a card.
	 *
	 * @param \WP_Post $post
	 * @return string
	 */
	private function get_card_excerpt( $post ) {
		$num_words = isset( $this->args['content_length'] ) ? (int) $this->args['content_length'] : 15;

		$text = get_the_content( '', false, $post->ID );
		$text = strip_shortcodes( $text );
		$text = apply_filters( 'the_content', $text );

		if ( $num_words > 0 ) {
			$text = wp_trim_words( $text, $num_words, ' &hellip;' );
		}

		return $text;
	}

	/**
	 * Get the download button for a card.
	 *
	 * @param Document $document
	 * @return string
	 */
	private function get_card_download( $document ) {
		$link_text   = isset( $this->args['link_text'] ) ? $this->args['link_text'] : __( 'Download', 'document-library-lite' );
		$link_style  = isset( $this->args['link_style'] ) ? $this->args['link_style'] : 'button';
		$link_icon   = isset( $this->args['link_icon'] ) ? $this->args['link_icon'] : false;
		$link_target = isset( $this->args['link_target'] ) ? $this->args['link_target'] : false;

		return $document->get_download_button( $link_text, $link_style, $link_icon, $link_target );
	}

	/**
	 * Get the info markup, e.g. "Showing 1 to 10 of 12 entries" (matches the table layout).
	 *
	 * @param int $page The current 1-based page number.
	 * @return string
	 */
	public function get_totals_html( $page = 1 ) {
		$total    = (int) $this->get_total_posts();
		$per_page = $this->get_per_page();

		if ( $total < 1 ) {
			$start = 0;
			$end   = 0;
		} elseif ( $per_page < 1 ) {
			// "All" — single page showing every document.
			$start = 1;
			$end   = $total;
		} else {
			$total_pages = (int) ceil( $total / max( 1, $per_page ) );
			$page        = max( 1, min( (int) $page, max( 1, $total_pages ) ) );
			$start       = ( $page - 1 ) * $per_page + 1;
			$end         = min( $page * $per_page, $total );
		}

		/* translators: 1: first row number, 2: last row number, 3: total number of documents. */
		$text = sprintf(
			__( 'Showing %1$s to %2$s of %3$s entries', 'document-library-lite' ),
			number_format_i18n( $start ),
			number_format_i18n( $end ),
			number_format_i18n( $total )
		);

		return sprintf( '<div class="dlp-grid-totals">%s</div>', esc_html( $text ) );
	}

	/**
	 * Get the pagination markup for a given page.
	 *
	 * @param int $page The current 1-based page number.
	 * @return string
	 */
	public function get_pagination_html( $page = 1 ) {
		$total       = (int) $this->get_total_posts();
		$per_page    = $this->get_per_page();
		$total_pages = $per_page < 1 ? 1 : max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
		$page        = max( 1, min( (int) $page, $total_pages ) );

		// Always render the control (single page = disabled Prev/Next), matching the table layout.
		$buttons = $this->paginate_button( $page - 1, __( 'Previous', 'document-library-lite' ), 'previous', $page <= 1 );

		for ( $i = 1; $i <= $total_pages; $i++ ) {
			$buttons .= $this->paginate_button( $i, (string) $i, $i === $page ? 'current' : '', false );
		}

		$buttons .= $this->paginate_button( $page + 1, __( 'Next', 'document-library-lite' ), 'next', $page >= $total_pages );

		return sprintf( '<div class="dlp-grid-pagination">%s</div>', $buttons );
	}

	/**
	 * Build a single pagination button.
	 *
	 * @param int    $page_number
	 * @param string $label
	 * @param string $modifier    Extra class, e.g. 'current', 'prev', 'next'.
	 * @param bool   $disabled
	 * @return string
	 */
	private function paginate_button( $page_number, $label, $modifier = '', $disabled = false ) {
		$classes = 'dlp-grid-paginate-button';

		if ( $modifier ) {
			$classes .= ' ' . $modifier;
		}

		if ( $disabled ) {
			$classes .= ' disabled';
		}

		return sprintf(
			'<a class="%1$s" data-page-number="%2$d" role="button" tabindex="0">%3$s</a>',
			esc_attr( $classes ),
			(int) $page_number,
			esc_html( $label )
		);
	}

	/**
	 * Render the full grid container (used on initial page load).
	 *
	 * @return string
	 */
	public function get_container() {
		$grid       = $this->get_grid_html( 1 );
		$totals     = $this->get_totals_html( 1 );
		$pagination = $this->get_pagination_html( 1 );
		$active_cat = isset( $this->args['doc_category'] ) ? $this->args['doc_category'] : '';

		return sprintf(
			'<div id="%1$s" class="dlp-grid-container" data-scroll-offset="%2$s" data-active-category="%3$s">%4$s%5$s<footer class="dlp-grid-footer dlp-grid-controls">%6$s%7$s</footer></div>',
			esc_attr( $this->grid_id ),
			esc_attr( isset( $this->args['scroll_offset'] ) ? $this->args['scroll_offset'] : 15 ),
			esc_attr( $active_cat ),
			$this->get_header_html(),
			$grid,
			$totals,
			$pagination
		);
	}

	/**
	 * Render the grid header (search box). Not re-rendered on AJAX so the input keeps focus/value.
	 *
	 * @return string
	 */
	private function get_header_html() {
		// Page-length select on the left: "Show [select] entries".
		$selected = $this->get_per_page();
		$options  = '';
		foreach ( $this->get_page_lengths() as $length ) {
			$options .= sprintf( '<option value="%1$d"%2$s>%1$d</option>', $length, selected( $length, $selected, false ) );
		}

		// "All" option at the very end, only when the site is configured to show all documents (-1).
		if ( -1 === $this->base_per_page ) {
			$options .= sprintf(
				'<option value="-1"%1$s>%2$s</option>',
				selected( $selected < 1, true, false ),
				esc_html__( 'All', 'document-library-lite' )
			);
		}

		$length_html = sprintf(
			'<div class="dlp-grid-length"><label>%1$s <select class="dlp-grid-length-select">%2$s</select> %3$s</label></div>',
			esc_html__( 'Show', 'document-library-lite' ),
			$options,
			esc_html__( 'entries', 'document-library-lite' )
		);

		// Search box on the right.
		$search_html = sprintf(
			'<div class="dlp-grid-search"><label>%1$s<input type="search" class="dlp-grid-search-input" value=""></label></div>',
			esc_html__( 'Search:', 'document-library-lite' )
		);

		return sprintf( '<header class="dlp-grid-header dlp-grid-controls">%1$s%2$s</header>', $length_html, $search_html );
	}

	/**
	 * Build the AJAX response parts for a given page.
	 *
	 * @param int $page
	 * @return array
	 */
	public function get_ajax_parts( $page = 1 ) {
		return [
			'grid'       => $this->get_grid_html( $page ),
			'totals'     => $this->get_totals_html( $page ),
			'pagination' => $this->get_pagination_html( $page ),
		];
	}

	/**
	 * Get the grid ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->grid_id;
	}
}
