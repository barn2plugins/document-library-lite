/**
 * Document Library Lite – grid layout.
 */
(function ($) {
	$(document).ready(function () {
		const params = window.document_library_grid_params || {};

		/**
		 * Tiny debounce helper.
		 */
		function debounce(fn, wait) {
			let timer;
			return function () {
				const context = this;
				const args = arguments;
				clearTimeout(timer);
				timer = setTimeout(function () {
					fn.apply(context, args);
				}, wait);
			};
		}

		/**
		 * Build the request params for a container, honouring the active filter.
		 *
		 * The active category filter and the free-text search are mutually exclusive: when a category is
		 * active (set by clicking a category link), the search box only displays its name and is not sent
		 * as a keyword.
		 *
		 * @param {jQuery} $container
		 * @param {number} page
		 * @return {Object}
		 */
		function requestParams($container, page) {
			const category = $container.attr("data-active-category") || "";
			const search = category
				? ""
				: $container.find(".dlp-grid-search-input").val() || "";
			const length = $container.find(".dlp-grid-length-select").val() || "";

			return {
				category: category,
				search_query: search,
				length: length,
				page_number: page,
			};
		}

		/**
		 * Load a page of grid cards over AJAX and swap the relevant parts into the container.
		 *
		 * @param {jQuery} $container The .dlp-grid-container element.
		 * @param {number} page       The 1-based page number to load.
		 */
		function loadPage($container, page) {
			if (!params.ajax_url) {
				return;
			}

			$container.addClass("dlp-grid-loading");

			$.ajax({
				url: params.ajax_url,
				type: "POST",
				data: $.extend(
					{
						action: params.ajax_action,
						grid_id: $container.attr("id"),
						_ajax_nonce: params.ajax_nonce,
					},
					requestParams($container, page)
				),
			})
				.done(function (response) {
					if (!response) {
						return;
					}

					if (response.grid) {
						$container
							.find(".dlp-grid-documents")
							.replaceWith(response.grid);
					}

					if (response.totals) {
						$container.find(".dlp-grid-totals").replaceWith(response.totals);
					}

					if (response.pagination) {
						$container
							.find(".dlp-grid-pagination")
							.replaceWith(response.pagination);
					}

					// Scroll back to the top of the grid on page change.
					const scrollOffset = $container.data("scroll-offset");
					if (scrollOffset !== false && typeof scrollOffset !== "undefined") {
						const adminBar = $("#wpadminbar");
						let top =
							$container.offset().top - (parseInt(scrollOffset, 10) || 0);
						if (adminBar.length) {
							top -= adminBar.outerHeight() || 32;
						}
						$("html, body").animate({ scrollTop: top }, 300);
					}

					$container.trigger("grid-loaded");
				})
				.always(function () {
					$container.removeClass("dlp-grid-loading");
				});
		}

		// Pagination clicks.
		$(document).on("click", ".dlp-grid-paginate-button", function () {
			const $button = $(this);

			if ($button.hasClass("disabled") || $button.hasClass("current")) {
				return;
			}

			const $container = $button.closest(".dlp-grid-container");
			loadPage($container, $button.data("page-number"));
		});

		// Free-text search (debounced). Typing clears any active category filter.
		$(document).on(
			"input",
			".dlp-grid-search-input",
			debounce(function () {
				const $container = $(this).closest(".dlp-grid-container");
				$container.attr("data-active-category", "");
				loadPage($container, 1);
			}, 300)
		);

		// Page-length change → reload from page 1 with the new documents-per-page.
		$(document).on("change", ".dlp-grid-length-select", function () {
			const $container = $(this).closest(".dlp-grid-container");
			loadPage($container, 1);
		});

		// Category link click → filter the grid by that category (only when search_on_click is enabled, in
		// which case the link carries the .dlp-grid-category-link class). The search box is left untouched.
		$(document).on("click", ".dlp-grid-category-link", function (event) {
			event.preventDefault();

			const $link = $(this);
			const $container = $link.closest(".dlp-grid-container");

			$container.attr("data-active-category", $link.data("slug"));
			loadPage($container, 1);
		});

		// Lightbox (PhotoSwipe) on the featured image.
		$(document).on(
			"click",
			".dlp-grid-card-featured-img a.dlw-lightbox",
			function (event) {
				event.preventDefault();
				event.stopPropagation();

				const pswpElement = $(".pswp")[0];
				const $img = $(this).find("img");

				if (!pswpElement || $img.length < 1) {
					return;
				}

				const items = [
					{
						src: $img.attr("data-large_image"),
						w: $img.attr("data-large_image_width"),
						h: $img.attr("data-large_image_height"),
						title:
							$img.attr("data-caption") && $img.attr("data-caption").length
								? $img.attr("data-caption")
								: $img.attr("title"),
					},
				];

				const photoswipe = new PhotoSwipe(
					pswpElement,
					PhotoSwipeUI_Default,
					items,
					{
						index: 0,
						shareEl: false,
						closeOnScroll: false,
						history: false,
						hideAnimationDuration: 0,
						showAnimationDuration: 0,
					}
				);
				photoswipe.init();

				return false;
			}
		);
	});
})(jQuery);
