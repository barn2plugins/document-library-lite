<?php

namespace Barn2\Plugin\Document_Library\Admin\Wizard\Steps;

use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Interfaces\Deferrable;
use Barn2\Plugin\Document_Library\Dependencies\Setup_Wizard\Steps\Ready,
	Barn2\Plugin\Document_Library\Util\Options,
	Barn2\Plugin\Document_Library\Dependencies\Lib\Util as Lib_Util;

/**
 * Completed Step.
 *
 * @package   Barn2\document-library-lite
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Completed extends Ready implements Deferrable {

	/**
	 * {@inheritdoc}
	 */
	public function get_step_details() {
		return [
			'label'       => esc_html__( 'Ready', 'document-library-lite' ),
			'description' => $this->get_custom_description(),
			'heading'     => esc_html__( 'Complete Setup', 'document-library-lite' ),
		];
	}

	/**
	 * Retrieves the description.
	 *
	 * @return string
	 */
	private function get_custom_description() {
		$document_library_page = get_permalink( get_option( Options::DOCUMENT_PAGE_OPTION_KEY ) );
		$add_document_page     = admin_url( 'post-new.php?post_type=dlp_document' );

		return sprintf(
			/* translators: %1: Add Document link open %2: Add Document link close %3: Document Library page link open %4: Document library page link close */
			esc_html__( 'Congratulations, you have finished setting up the plugin! The next step is to start %1$sadding%2$s documents. Your documents will be listed on the %3$sdocument library page%4$s.', 'document-library-lite' ),
			Lib_Util::format_link_open( $add_document_page, true ),
			'</a>',
			Lib_Util::format_link_open( $document_library_page, true ),
			'</a>'
		);
	}

}
