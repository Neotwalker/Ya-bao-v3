<?php
/**
 * Stage 66 — safe WooCommerce product importer / sync.
 *
 * Input is the canonical JSON contract from Stage 50/65. The importer is
 * source-neutral: Google Sheets/CSV/API adapters can later emit the same
 * contract without changing the WooCommerce sync layer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YABAO_IMPORT_LOG_OPTION             = 'yabao_product_import_logs';
const YABAO_META_IMPORT_HASH              = '_yabao_import_hash';
const YABAO_META_SOURCE_IMAGE             = '_yabao_source_image';
const YABAO_META_VARIANT_MISSING          = '_yabao_variant_missing_from_source';
const YABAO_IMPORT_MAX_LOGS                = 20;
const YABAO_IMPORT_MAX_FILE_BYTES          = 5242880; // 5 MB.

/**
 * Known category labels already used by the approved static catalogue.
 * Unknown slugs are rejected rather than silently inventing a display name.
 */
function yabao_import_category_labels(): array {
	return apply_filters(
		'yabao_import_category_labels',
		array(
			'sheng-puer'    => 'Шэн пуэр',
			'shu-puer'      => 'Шу пуэр',
			'white-tea'     => 'Белый чай',
			'pressed-tea'   => 'Прессованный чай',
			'brewing-ware'  => 'Посуда для заваривания',
			'serving-ware'  => 'Посуда для подачи',
			'tea-tools'     => 'Чайные аксессуары',
			'packaging'     => 'Упаковка',
		)
	);
}

function yabao_import_report( bool $dry_run, string $source = '' ): array {
	return array(
		'run_id'      => wp_generate_uuid4(),
		'started_at'  => current_time( 'mysql' ),
		'dry_run'     => $dry_run,
		'source'      => $source,
		'dataset'     => '',
		'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		'summary'     => array(
			'create'   => 0,
			'update'   => 0,
			'noop'     => 0,
			'skipped'  => 0,
			'warnings' => 0,
			'errors'   => 0,
		),
		'messages'    => array(),
	);
}

function yabao_import_message( array &$report, string $level, string $message, array $context = array() ): void {
	$allowed = array( 'info', 'warning', 'error' );
	if ( ! in_array( $level, $allowed, true ) ) {
		$level = 'info';
	}

	$report['messages'][] = array(
		'level'   => $level,
		'message' => $message,
		'context' => $context,
	);

	if ( 'warning' === $level ) {
		$report['summary']['warnings']++;
	} elseif ( 'error' === $level ) {
		$report['summary']['errors']++;
	}
}

function yabao_import_required_string( array $data, string $key, string $path, array &$errors ): string {
	$value = isset( $data[ $key ] ) && is_string( $data[ $key ] ) ? trim( $data[ $key ] ) : '';
	if ( '' === $value ) {
		$errors[] = $path . '.' . $key . ' is required.';
	}
	return $value;
}

/**
 * Validate the canonical dataset before any catalogue write occurs.
 */
function yabao_import_validate_dataset( array $dataset ): array {
	$errors   = array();
	$warnings = array();

	if ( '1.0.0' !== ( $dataset['schema_version'] ?? null ) ) {
		$errors[] = 'schema_version must be 1.0.0.';
	}
	if ( 'RUB' !== ( $dataset['currency'] ?? null ) ) {
		$errors[] = 'currency must be RUB.';
	}
	if ( ! in_array( $dataset['dataset'] ?? '', array( 'demo', 'production' ), true ) ) {
		$errors[] = 'dataset must be demo or production.';
	}
	if ( empty( $dataset['products'] ) || ! is_array( $dataset['products'] ) ) {
		$errors[] = 'products must be a non-empty array.';
		return compact( 'errors', 'warnings' );
	}

	$statuses       = array( 'active', 'out_of_stock', 'inactive', 'missing_from_feed', 'archived' );
	$types          = array( 'tea', 'ware', 'accessory' );
	$sale_modes     = array( 'weight', 'unit' );
	$stock_statuses = array( 'in_stock', 'out_of_stock' );
	$external_ids   = array();
	$skus           = array();
	$variant_ids    = array();
	$category_labels = yabao_import_category_labels();

	foreach ( $dataset['products'] as $index => $product ) {
		$path = 'products[' . $index . ']';
		if ( ! is_array( $product ) ) {
			$errors[] = $path . ' must be an object.';
			continue;
		}

		$external_id = yabao_import_required_string( $product, 'external_id', $path, $errors );
		$sku         = yabao_import_required_string( $product, 'sku', $path, $errors );
		$slug        = yabao_import_required_string( $product, 'slug', $path, $errors );
		$name        = yabao_import_required_string( $product, 'name', $path, $errors );
		$category    = yabao_import_required_string( $product, 'category', $path, $errors );
		$status      = isset( $product['status'] ) ? (string) $product['status'] : '';
		$type        = isset( $product['type'] ) ? (string) $product['type'] : '';
		$sale_mode   = isset( $product['sale_mode'] ) ? (string) $product['sale_mode'] : '';
		$stock       = isset( $product['stock_status'] ) ? (string) $product['stock_status'] : '';
		$variants    = isset( $product['variants'] ) && is_array( $product['variants'] ) ? $product['variants'] : array();

		unset( $name );

		if ( $external_id ) {
			if ( isset( $external_ids[ $external_id ] ) ) {
				$errors[] = $path . '.external_id duplicates ' . $external_ids[ $external_id ] . '.';
			} else {
				$external_ids[ $external_id ] = $path;
			}
		}
		if ( $sku ) {
			if ( isset( $skus[ $sku ] ) ) {
				$errors[] = $path . '.sku duplicates ' . $skus[ $sku ] . '.';
			} else {
				$skus[ $sku ] = $path;
			}
		}
		if ( $slug && ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug ) ) {
			$errors[] = $path . '.slug has an invalid format.';
		}
		if ( ! in_array( $status, $statuses, true ) ) {
			$errors[] = $path . '.status is unknown.';
		}
		if ( ! in_array( $type, $types, true ) ) {
			$errors[] = $path . '.type is unknown.';
		}
		if ( ! in_array( $sale_mode, $sale_modes, true ) ) {
			$errors[] = $path . '.sale_mode is unknown.';
		}
		if ( ! in_array( $stock, $stock_statuses, true ) ) {
			$errors[] = $path . '.stock_status is unknown.';
		}
		if ( $category && ! isset( $category_labels[ $category ] ) && ! term_exists( $category, 'product_cat' ) ) {
			$errors[] = $path . '.category "' . $category . '" has no approved label and does not exist in WooCommerce.';
		}

		if ( 'tea' === $type && 'weight' !== $sale_mode ) {
			$errors[] = $path . ': tea must use sale_mode=weight.';
		}
		if ( in_array( $type, array( 'ware', 'accessory' ), true ) && 'unit' !== $sale_mode ) {
			$errors[] = $path . ': ware/accessory must use sale_mode=unit.';
		}
		if ( 'out_of_stock' === $status && 'out_of_stock' !== $stock ) {
			$errors[] = $path . ': status=out_of_stock requires stock_status=out_of_stock.';
		}

		if ( 'weight' === $sale_mode ) {
			if ( 50 !== (int) ( $product['min_weight_g'] ?? 0 ) ) {
				$errors[] = $path . '.min_weight_g must be 50.';
			}
			if ( 'in_stock' === $stock && empty( $variants ) ) {
				$errors[] = $path . ': in-stock weight product needs at least one variant.';
			}
			if ( 'out_of_stock' === $stock && ! empty( $variants ) ) {
				$errors[] = $path . ': out-of-stock weight product must not expose variants.';
			}
		}

		if ( 'unit' === $sale_mode && empty( $variants ) ) {
			$quantity = isset( $product['quantity'] ) ? (int) $product['quantity'] : -1;
			if ( 'out_of_stock' === $stock && 0 !== $quantity ) {
				$errors[] = $path . ': out-of-stock unit product must have quantity=0.';
			}
			if ( 'in_stock' === $stock && $quantity < 1 ) {
				$errors[] = $path . ': in-stock unit product must have quantity>=1.';
			}
		}

		$unit_option_keys = null;
		foreach ( $variants as $variant_index => $variant ) {
			$variant_path = $path . '.variants[' . $variant_index . ']';
			if ( ! is_array( $variant ) ) {
				$errors[] = $variant_path . ' must be an object.';
				continue;
			}
			$variant_id = yabao_import_required_string( $variant, 'variant_id', $variant_path, $errors );
			if ( $variant_id ) {
				if ( isset( $variant_ids[ $variant_id ] ) ) {
					$errors[] = $variant_path . '.variant_id duplicates ' . $variant_ids[ $variant_id ] . '.';
				} else {
					$variant_ids[ $variant_id ] = $variant_path;
				}
			}

			$variant_stock = isset( $variant['stock_status'] ) ? (string) $variant['stock_status'] : '';
			if ( ! in_array( $variant_stock, $stock_statuses, true ) ) {
				$errors[] = $variant_path . '.stock_status is unknown.';
			}
			if ( ! isset( $variant['price'] ) || ! is_numeric( $variant['price'] ) || (float) $variant['price'] < 0 ) {
				$errors[] = $variant_path . '.price must be >= 0.';
			}

			if ( 'weight' === $sale_mode ) {
				$weight = isset( $variant['weight_g'] ) ? (int) $variant['weight_g'] : 0;
				if ( $weight < 50 || 0 !== $weight % 50 ) {
					$errors[] = $variant_path . '.weight_g must be >=50 and divisible by 50.';
				}
				if ( 'in_stock' !== $variant_stock ) {
					$errors[] = $variant_path . ': exposed weight variants must be in_stock.';
				}
			} else {
				$quantity = isset( $variant['quantity'] ) ? (int) $variant['quantity'] : -1;
				if ( 'out_of_stock' === $variant_stock && 0 !== $quantity ) {
					$errors[] = $variant_path . ': out-of-stock unit variant must have quantity=0.';
				}
				if ( 'in_stock' === $variant_stock && $quantity < 1 ) {
					$errors[] = $variant_path . ': in-stock unit variant must have quantity>=1.';
				}
				$option_values = isset( $variant['option_values'] ) && is_array( $variant['option_values'] ) ? $variant['option_values'] : array();
				if ( empty( $option_values ) ) {
					$errors[] = $variant_path . ': unit variants require non-empty option_values for unambiguous WooCommerce variations.';
				} else {
					$keys = array_keys( $option_values );
					sort( $keys );
					if ( null === $unit_option_keys ) {
						$unit_option_keys = $keys;
					} elseif ( $unit_option_keys !== $keys ) {
						$errors[] = $variant_path . ': all unit variants must use the same option_values keys.';
					}
				}
			}
		}

		if ( isset( $product['images'] ) && ! is_array( $product['images'] ) ) {
			$errors[] = $path . '.images must be an array.';
		} elseif ( ! empty( $product['images'] ) ) {
			foreach ( $product['images'] as $image_index => $image ) {
				$image_path = $path . '.images[' . $image_index . ']';
				if ( ! is_array( $image ) || empty( $image['src'] ) || ! is_string( $image['src'] ) ) {
					$errors[] = $image_path . '.src is required.';
					continue;
				}
				$src = trim( $image['src'] );
				if ( ! preg_match( '#^https?://#i', $src ) ) {
					$theme_root = realpath( get_template_directory() );
					$file       = realpath( trailingslashit( get_template_directory() ) . ltrim( $src, '/' ) );
					if ( ! $theme_root || ! $file || ! str_starts_with( $file, $theme_root . DIRECTORY_SEPARATOR ) || ! is_file( $file ) ) {
						$errors[] = $image_path . '.src does not resolve to a safe theme file: ' . $src;
					}
				}
			}
		}
	}

	return compact( 'errors', 'warnings' );
}

function yabao_import_find_product_ids_by_external_id( string $external_id ): array {
	return get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => YABAO_META_EXTERNAL_ID,
					'value' => $external_id,
				),
			),
		)
	);
}

function yabao_import_find_variation_ids_by_external_id( string $variant_id ): array {
	return get_posts(
		array(
			'post_type'      => 'product_variation',
			'post_status'    => array( 'publish', 'private', 'draft', 'trash' ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => YABAO_META_VARIANT_ID,
					'value' => $variant_id,
				),
			),
		)
	);
}

function yabao_import_source_hash( array $product ): string {
	$owned = $product;
	unset( $owned['editorial'] );
	return hash( 'sha256', wp_json_encode( $owned, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

/**
 * Database identity/type preflight. Returns product IDs keyed by external_id.
 */
function yabao_import_preflight_database( array $dataset, array &$report ): array {
	$resolved = array();

	foreach ( $dataset['products'] as $product ) {
		$external_id = (string) $product['external_id'];
		$sku         = (string) $product['sku'];
		$matches     = yabao_import_find_product_ids_by_external_id( $external_id );
		$sku_id      = wc_get_product_id_by_sku( $sku );

		if ( count( $matches ) > 1 ) {
			yabao_import_message( $report, 'error', 'external_id найден у нескольких WooCommerce-товаров.', array( 'external_id' => $external_id, 'ids' => $matches ) );
			continue;
		}

		$product_id = $matches ? (int) $matches[0] : 0;
		if ( $product_id && $sku_id && $product_id !== (int) $sku_id ) {
			yabao_import_message( $report, 'error', 'Конфликт external_id и SKU: они указывают на разные товары.', array( 'external_id' => $external_id, 'sku' => $sku, 'external_product_id' => $product_id, 'sku_product_id' => $sku_id ) );
			continue;
		}
		if ( ! $product_id && $sku_id ) {
			$sku_external = (string) get_post_meta( $sku_id, YABAO_META_EXTERNAL_ID, true );
			yabao_import_message( $report, 'error', 'SKU уже занят товаром без совпадающего external_id; автоматическое усыновление запрещено.', array( 'external_id' => $external_id, 'sku' => $sku, 'sku_product_id' => $sku_id, 'existing_external_id' => $sku_external ) );
			continue;
		}

		if ( $product_id ) {
			$existing = wc_get_product( $product_id );
			$expected = yabao_mapping_product_type( $product );
			if ( ! $existing || $existing->get_type() !== $expected ) {
				yabao_import_message( $report, 'error', 'Изменение WooCommerce product type автоматически запрещено.', array( 'external_id' => $external_id, 'expected' => $expected, 'existing' => $existing ? $existing->get_type() : 'unknown' ) );
				continue;
			}
		}

		foreach ( (array) ( $product['variants'] ?? array() ) as $variant ) {
			$variant_id = (string) $variant['variant_id'];
			$found      = yabao_import_find_variation_ids_by_external_id( $variant_id );
			if ( count( $found ) > 1 ) {
				yabao_import_message( $report, 'error', 'variant_id найден у нескольких вариаций.', array( 'variant_id' => $variant_id, 'ids' => $found ) );
				continue;
			}
			if ( $found ) {
				$parent_id = (int) wp_get_post_parent_id( (int) $found[0] );
				if ( $product_id && $parent_id !== $product_id ) {
					yabao_import_message( $report, 'error', 'variant_id уже принадлежит другому товару.', array( 'variant_id' => $variant_id, 'variation_id' => (int) $found[0], 'parent_id' => $parent_id, 'expected_parent_id' => $product_id ) );
				}
				if ( ! $product_id ) {
					yabao_import_message( $report, 'error', 'variant_id уже существует, но родительский external_id отсутствует; автоматический перенос запрещён.', array( 'variant_id' => $variant_id, 'variation_id' => (int) $found[0] ) );
				}
			}
		}

		$resolved[ $external_id ] = $product_id;
	}

	return $resolved;
}

function yabao_import_effective_post_status( array $product, string $environment, array &$report ): string {
	$status = yabao_mapping_post_status( (string) $product['status'] );
	if ( ! empty( $product['is_demo'] ) && 'production' === $environment && 'publish' === $status ) {
		yabao_import_message( $report, 'warning', 'Demo-товар принудительно останется draft в production environment.', array( 'external_id' => $product['external_id'] ) );
		return 'draft';
	}
	return $status;
}

function yabao_import_ensure_category( string $slug ) {
	$exists = term_exists( $slug, 'product_cat' );
	if ( $exists ) {
		return is_array( $exists ) ? (int) $exists['term_id'] : (int) $exists;
	}

	$labels = yabao_import_category_labels();
	if ( ! isset( $labels[ $slug ] ) ) {
		return new WP_Error( 'yabao_unknown_category', 'Нет утверждённого названия категории для slug ' . $slug . '.' );
	}

	$created = wp_insert_term( $labels[ $slug ], 'product_cat', array( 'slug' => $slug ) );
	if ( is_wp_error( $created ) ) {
		return $created;
	}
	return (int) $created['term_id'];
}

function yabao_import_register_weight_taxonomy_for_request( int $attribute_id ): string {
	$taxonomy = wc_attribute_taxonomy_name( YABAO_WEIGHT_ATTRIBUTE_SLUG );
	if ( taxonomy_exists( $taxonomy ) ) {
		return $taxonomy;
	}

	register_taxonomy(
		$taxonomy,
		array( 'product' ),
		array(
			'hierarchical'      => true,
			'show_ui'           => false,
			'query_var'         => true,
			'rewrite'           => false,
			'public'            => false,
			'show_in_nav_menus' => false,
			'capabilities'      => array(
				'manage_terms' => 'manage_product_terms',
				'edit_terms'   => 'edit_product_terms',
				'delete_terms' => 'delete_product_terms',
				'assign_terms' => 'assign_product_terms',
			),
			'label'             => YABAO_WEIGHT_ATTRIBUTE_LABEL,
		)
	);

	return $taxonomy;
}

function yabao_import_ensure_weight_attribute() {
	$ids          = wc_get_attribute_taxonomy_ids();
	$attribute_id = isset( $ids[ YABAO_WEIGHT_ATTRIBUTE_SLUG ] ) ? (int) $ids[ YABAO_WEIGHT_ATTRIBUTE_SLUG ] : 0;

	if ( ! $attribute_id ) {
		$attribute_id = wc_create_attribute(
			array(
				'name'         => YABAO_WEIGHT_ATTRIBUTE_LABEL,
				'slug'         => YABAO_WEIGHT_ATTRIBUTE_SLUG,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		if ( is_wp_error( $attribute_id ) ) {
			return $attribute_id;
		}
		delete_transient( 'wc_attribute_taxonomies' );
		if ( class_exists( 'WC_Cache_Helper' ) ) {
			WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
		}
	}

	$taxonomy = yabao_import_register_weight_taxonomy_for_request( (int) $attribute_id );
	return array( 'id' => (int) $attribute_id, 'taxonomy' => $taxonomy );
}

function yabao_import_ensure_weight_term( string $taxonomy, int $weight_g ) {
	$slug   = yabao_mapping_weight_term_slug( $weight_g );
	$exists = term_exists( $slug, $taxonomy );
	if ( $exists ) {
		return is_array( $exists ) ? (int) $exists['term_id'] : (int) $exists;
	}

	$created = wp_insert_term( yabao_mapping_weight_label( $weight_g ), $taxonomy, array( 'slug' => $slug ) );
	if ( is_wp_error( $created ) ) {
		return $created;
	}
	return (int) $created['term_id'];
}

function yabao_import_find_attachment_by_source( string $src ): int {
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => YABAO_META_SOURCE_IMAGE,
					'value' => $src,
				),
			),
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

function yabao_import_media( array $image, int $product_id ) {
	$src = trim( (string) $image['src'] );
	$alt = isset( $image['alt'] ) ? (string) $image['alt'] : '';

	$existing = yabao_import_find_attachment_by_source( $src );
	if ( $existing ) {
		if ( '' !== $alt ) {
			update_post_meta( $existing, '_wp_attachment_image_alt', $alt );
		}
		return $existing;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp  = '';
	$name = basename( wp_parse_url( $src, PHP_URL_PATH ) ?: $src );
	if ( preg_match( '#^https?://#i', $src ) ) {
		$tmp = download_url( $src, 30 );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}
	} else {
		$theme_root = realpath( get_template_directory() );
		$file       = realpath( trailingslashit( get_template_directory() ) . ltrim( $src, '/' ) );
		if ( ! $theme_root || ! $file || ! str_starts_with( $file, $theme_root . DIRECTORY_SEPARATOR ) || ! is_file( $file ) ) {
			return new WP_Error( 'yabao_image_missing', 'Не найден безопасный локальный файл изображения: ' . $src );
		}
		$tmp = wp_tempnam( $name );
		if ( ! $tmp || ! copy( $file, $tmp ) ) {
			return new WP_Error( 'yabao_image_copy', 'Не удалось подготовить изображение: ' . $src );
		}
	}

	$file_array = array( 'name' => $name, 'tmp_name' => $tmp );
	$attachment_id = media_handle_sideload( $file_array, $product_id );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		return $attachment_id;
	}

	update_post_meta( $attachment_id, YABAO_META_SOURCE_IMAGE, $src );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	return (int) $attachment_id;
}

function yabao_import_apply_images( WC_Product $wc_product, array $images ): void {
	if ( empty( $images ) ) {
		$wc_product->set_image_id( 0 );
		$wc_product->set_gallery_image_ids( array() );
		return;
	}

	usort(
		$images,
		static fn( array $a, array $b ): int => (int) ( $a['sort_order'] ?? 0 ) <=> (int) ( $b['sort_order'] ?? 0 )
	);

	$ids = array();
	foreach ( $images as $image ) {
		$id = yabao_import_media( $image, $wc_product->get_id() );
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( $id->get_error_message() );
		}
		$ids[] = (int) $id;
	}

	$wc_product->set_image_id( array_shift( $ids ) ?: 0 );
	$wc_product->set_gallery_image_ids( $ids );
}

function yabao_import_apply_source_meta( WC_Product $wc_product, array $product ): void {
	$wc_product->update_meta_data( YABAO_META_EXTERNAL_ID, (string) $product['external_id'] );
	$wc_product->update_meta_data( YABAO_META_SOURCE_TYPE, (string) $product['type'] );
	$wc_product->update_meta_data( YABAO_META_SOURCE_STATUS, (string) $product['status'] );
	$wc_product->update_meta_data( YABAO_META_SALE_MODE, (string) $product['sale_mode'] );
	$wc_product->update_meta_data( YABAO_META_SOURCE_PRICE, (string) $product['price'] );
	$wc_product->update_meta_data( YABAO_META_SOURCE_UPDATED_AT, (string) $product['updated_at'] );
	$wc_product->update_meta_data( YABAO_META_IS_DEMO, ! empty( $product['is_demo'] ) ? '1' : '0' );
	$wc_product->update_meta_data( YABAO_META_DEMO_NOTE, isset( $product['demo_note'] ) ? (string) $product['demo_note'] : '' );
	$wc_product->update_meta_data( YABAO_META_TEA_FORM, isset( $product['tea_form'] ) ? (string) $product['tea_form'] : '' );
	$wc_product->update_meta_data( YABAO_META_MIN_WEIGHT_G, isset( $product['min_weight_g'] ) ? (string) (int) $product['min_weight_g'] : '' );
}

function yabao_import_apply_editorial_on_create( int $product_id, array $product, array &$report ): void {
	if ( empty( $product['editorial'] ) || ! is_array( $product['editorial'] ) ) {
		return;
	}
	if ( ! function_exists( 'update_field' ) || ! function_exists( 'get_field_object' ) ) {
		yabao_import_message( $report, 'info', 'Editorial пропущен: ACF Pro не активирован.', array( 'external_id' => $product['external_id'] ) );
		return;
	}

	foreach ( yabao_mapping_editorial_fields() as $field_name ) {
		if ( ! array_key_exists( $field_name, $product['editorial'] ) ) {
			continue;
		}
		$field = get_field_object( $field_name, $product_id, false, false );
		if ( ! $field ) {
			continue;
		}
		$current = get_field( $field_name, $product_id, false );
		if ( null === $current || '' === $current || array() === $current ) {
			update_field( $field_name, $product['editorial'][ $field_name ], $product_id );
		}
	}
}

function yabao_import_weight_attributes( array $variants ) {
	$weight = yabao_import_ensure_weight_attribute();
	if ( is_wp_error( $weight ) ) {
		return $weight;
	}

	$term_ids = array();
	foreach ( $variants as $variant ) {
		$term_id = yabao_import_ensure_weight_term( $weight['taxonomy'], (int) $variant['weight_g'] );
		if ( is_wp_error( $term_id ) ) {
			return $term_id;
		}
		$term_ids[] = (int) $term_id;
	}

	$attribute = new WC_Product_Attribute();
	$attribute->set_id( (int) $weight['id'] );
	$attribute->set_name( (string) $weight['taxonomy'] );
	$attribute->set_options( array_values( array_unique( $term_ids ) ) );
	$attribute->set_position( 0 );
	$attribute->set_visible( true );
	$attribute->set_variation( true );

	return array( 'attribute' => $attribute, 'taxonomy' => $weight['taxonomy'] );
}

function yabao_import_unit_attributes( array $variants ): array {
	$values = array();
	foreach ( $variants as $variant ) {
		foreach ( (array) $variant['option_values'] as $key => $value ) {
			$key = (string) $key;
			if ( ! isset( $values[ $key ] ) ) {
				$values[ $key ] = array();
			}
			$values[ $key ][] = (string) $value;
		}
	}

	$attributes = array();
	$position   = 0;
	foreach ( $values as $name => $options ) {
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( $name );
		$attribute->set_options( array_values( array_unique( $options ) ) );
		$attribute->set_position( $position++ );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$attributes[] = $attribute;
	}
	return $attributes;
}

function yabao_import_sync_variations( WC_Product_Variable $parent, array $source_product ): void {
	$variants      = (array) ( $source_product['variants'] ?? array() );
	$sale_mode     = (string) $source_product['sale_mode'];
	$incoming_ids  = array();
	$weight_bundle = null;

	if ( 'weight' === $sale_mode ) {
		$weight_bundle = yabao_import_weight_attributes( $variants );
		if ( is_wp_error( $weight_bundle ) ) {
			throw new RuntimeException( $weight_bundle->get_error_message() );
		}
		$parent->set_attributes( array( $weight_bundle['attribute'] ) );
	} else {
		$parent->set_attributes( yabao_import_unit_attributes( $variants ) );
	}
	$parent->save();

	foreach ( $variants as $variant ) {
		$variant_id = (string) $variant['variant_id'];
		$incoming_ids[] = $variant_id;
		$found = yabao_import_find_variation_ids_by_external_id( $variant_id );
		$variation = $found ? new WC_Product_Variation( (int) $found[0] ) : new WC_Product_Variation();
		if ( ! $variation->get_id() ) {
			$variation->set_parent_id( $parent->get_id() );
		}
		$variation->set_status( 'publish' );
		$variation->set_regular_price( (string) $variant['price'] );
		$variation->set_stock_status( yabao_mapping_stock_status( (string) $variant['stock_status'] ) );

		if ( 'weight' === $sale_mode ) {
			$variation->set_manage_stock( false );
			$variation->set_attributes(
				array(
					$weight_bundle['taxonomy'] => yabao_mapping_weight_term_slug( (int) $variant['weight_g'] ),
				)
			);
		} else {
			$variation->set_manage_stock( true );
			$variation->set_stock_quantity( (int) $variant['quantity'] );
			$attributes = array();
			foreach ( (array) $variant['option_values'] as $key => $value ) {
				$attributes[ sanitize_title( (string) $key ) ] = (string) $value;
			}
			$variation->set_attributes( $attributes );
		}

		$variation->update_meta_data( YABAO_META_VARIANT_ID, $variant_id );
		$variation->delete_meta_data( YABAO_META_VARIANT_MISSING );
		$variation->save();
	}

	// Never delete a managed variation that disappeared from the source.
	$managed_children = get_posts(
		array(
			'post_type'      => 'product_variation',
			'post_parent'    => $parent->get_id(),
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array( 'key' => YABAO_META_VARIANT_ID, 'compare' => 'EXISTS' ),
			),
		)
	);
	foreach ( $managed_children as $child_id ) {
		$managed_id = (string) get_post_meta( $child_id, YABAO_META_VARIANT_ID, true );
		if ( $managed_id && ! in_array( $managed_id, $incoming_ids, true ) ) {
			$variation = new WC_Product_Variation( (int) $child_id );
			$variation->set_status( 'private' );
			$variation->set_stock_status( 'outofstock' );
			$variation->update_meta_data( YABAO_META_VARIANT_MISSING, '1' );
			$variation->save();
		}
	}

	WC_Product_Variable::sync( $parent->get_id() );
	wc_delete_product_transients( $parent->get_id() );
}

function yabao_import_is_running(): bool {
	return ! empty( $GLOBALS['yabao_import_in_progress'] );
}

/**
 * If a source-owned Woo product/variation is edited manually, invalidate the
 * last-applied source hash. The next sync will re-apply source-owned fields.
 */
function yabao_import_invalidate_product_hash( int $product_id ): void {
	if ( yabao_import_is_running() ) {
		return;
	}
	if ( get_post_meta( $product_id, YABAO_META_EXTERNAL_ID, true ) ) {
		delete_post_meta( $product_id, YABAO_META_IMPORT_HASH );
	}
}
add_action( 'woocommerce_update_product', 'yabao_import_invalidate_product_hash', 100 );

function yabao_import_invalidate_parent_hash_from_variation( int $variation_id ): void {
	if ( yabao_import_is_running() ) {
		return;
	}
	$parent_id = (int) wp_get_post_parent_id( $variation_id );
	if ( $parent_id ) {
		yabao_import_invalidate_product_hash( $parent_id );
	}
}
add_action( 'woocommerce_update_product_variation', 'yabao_import_invalidate_parent_hash_from_variation', 100 );

function yabao_import_sync_product( array $product, int $product_id, string $environment, array &$report ): int {
	$is_new   = ! $product_id;
	$type     = yabao_mapping_product_type( $product );
	$wc       = $is_new
		? ( 'variable' === $type ? new WC_Product_Variable() : new WC_Product_Simple() )
		: wc_get_product( $product_id );

	if ( ! $wc ) {
		throw new RuntimeException( 'Не удалось создать/загрузить WooCommerce product object.' );
	}

	$hash = yabao_import_source_hash( $product );
	if ( ! $is_new && hash_equals( (string) $wc->get_meta( YABAO_META_IMPORT_HASH, true ), $hash ) ) {
		$report['summary']['noop']++;
		yabao_import_message( $report, 'info', 'Без изменений.', array( 'external_id' => $product['external_id'], 'product_id' => $wc->get_id() ) );
		return $wc->get_id();
	}

	// Remove the previous success marker before any mutation. If this product
	// fails halfway through, the next run must retry instead of false no-op.
	if ( ! $is_new ) {
		$wc->delete_meta_data( YABAO_META_IMPORT_HASH );
		$wc->save_meta_data();
	}

	$wc->set_name( (string) $product['name'] );
	$wc->set_sku( (string) $product['sku'] );
	$wc->set_status( yabao_import_effective_post_status( $product, $environment, $report ) );
	if ( $is_new ) {
		$wc->set_slug( (string) $product['slug'] );
	}

	$category_id = yabao_import_ensure_category( (string) $product['category'] );
	if ( is_wp_error( $category_id ) ) {
		throw new RuntimeException( $category_id->get_error_message() );
	}
	$wc->set_category_ids( array( (int) $category_id ) );
	$wc->set_catalog_visibility( 'visible' );

	if ( 'simple' === $type ) {
		$wc->set_regular_price( (string) $product['price'] );
		$wc->set_manage_stock( true );
		$wc->set_stock_quantity( (int) $product['quantity'] );
		$wc->set_stock_status( yabao_mapping_stock_status( (string) $product['stock_status'] ) );
	} else {
		$wc->set_manage_stock( false );
		$wc->set_stock_status( yabao_mapping_stock_status( (string) $product['stock_status'] ) );
	}

	yabao_import_apply_source_meta( $wc, $product );
	$wc->save();

	// Images are source-owned, but are applied only after the product has a stable ID.
	yabao_import_apply_images( $wc, (array) ( $product['images'] ?? array() ) );
	$wc->save();

	if ( 'variable' === $type ) {
		yabao_import_sync_variations( $wc, $product );
	}

	if ( $is_new ) {
		yabao_import_apply_editorial_on_create( $wc->get_id(), $product, $report );
		$report['summary']['create']++;
	} else {
		$report['summary']['update']++;
	}

	// Commit the source hash only after images/variations/editorial completed.
	// A failed partial run therefore retries instead of becoming a false no-op.
	$wc->update_meta_data( YABAO_META_IMPORT_HASH, $hash );
	$wc->save_meta_data();

	yabao_import_message( $report, 'info', $is_new ? 'Создан товар.' : 'Обновлён товар.', array( 'external_id' => $product['external_id'], 'product_id' => $wc->get_id() ) );
	return $wc->get_id();
}

function yabao_import_plan_product( array $product, int $product_id, string $environment, array &$report ): void {
	$action = 'create';
	if ( $product_id ) {
		$current_hash = (string) get_post_meta( $product_id, YABAO_META_IMPORT_HASH, true );
		$action       = $current_hash && hash_equals( $current_hash, yabao_import_source_hash( $product ) ) ? 'noop' : 'update';
	}
	$report['summary'][ $action ]++;

	$status = yabao_mapping_post_status( (string) $product['status'] );
	if ( ! empty( $product['is_demo'] ) && 'production' === $environment && 'publish' === $status ) {
		yabao_import_message( $report, 'warning', 'Dry-run: demo-товар будет draft, потому что WP environment=production.', array( 'external_id' => $product['external_id'] ) );
	}

	yabao_import_message(
		$report,
		'info',
		'Dry-run: ' . $action . '.',
		array(
			'external_id' => $product['external_id'],
			'product_id'  => $product_id,
			'woo_type'    => yabao_mapping_product_type( $product ),
			'status'      => $status,
		)
	);
}

/**
 * Main entry point. Validation and DB conflict checks happen before writes.
 */
function yabao_import_run( array $dataset, bool $dry_run = true, string $source = '' ): array {
	$report = yabao_import_report( $dry_run, $source );
	$report['dataset'] = isset( $dataset['dataset'] ) ? (string) $dataset['dataset'] : '';

	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
		yabao_import_message( $report, 'error', 'WooCommerce не активирован.' );
		return yabao_import_finish_report( $report );
	}

	$validation = yabao_import_validate_dataset( $dataset );
	foreach ( $validation['warnings'] as $warning ) {
		yabao_import_message( $report, 'warning', $warning );
	}
	foreach ( $validation['errors'] as $error ) {
		yabao_import_message( $report, 'error', $error );
	}
	if ( $report['summary']['errors'] ) {
		yabao_import_message( $report, 'info', 'Импорт остановлен до записи данных: контракт не прошёл validation.' );
		return yabao_import_finish_report( $report );
	}

	$resolved = yabao_import_preflight_database( $dataset, $report );
	if ( $report['summary']['errors'] ) {
		yabao_import_message( $report, 'info', 'Импорт остановлен до записи данных: обнаружены конфликты identity/type.' );
		return yabao_import_finish_report( $report );
	}

	$environment = $report['environment'];
	if ( $dry_run ) {
		foreach ( $dataset['products'] as $product ) {
			yabao_import_plan_product( $product, (int) ( $resolved[ $product['external_id'] ] ?? 0 ), $environment, $report );
		}
		return yabao_import_finish_report( $report );
	}

	$GLOBALS['yabao_import_in_progress'] = true;
	try {
		foreach ( $dataset['products'] as $product ) {
			try {
				yabao_import_sync_product( $product, (int) ( $resolved[ $product['external_id'] ] ?? 0 ), $environment, $report );
			} catch ( Throwable $error ) {
				$report['summary']['skipped']++;
				yabao_import_message( $report, 'error', 'Ошибка синхронизации товара: ' . $error->getMessage(), array( 'external_id' => $product['external_id'] ?? '' ) );
			}
		}
	} finally {
		$GLOBALS['yabao_import_in_progress'] = false;
	}

	// Full-snapshot safety: managed products absent from the source are untouched.
	$incoming_external_ids = array_map( static fn( array $item ): string => (string) $item['external_id'], $dataset['products'] );
	$managed_ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'meta_query'     => array( array( 'key' => YABAO_META_EXTERNAL_ID, 'compare' => 'EXISTS' ) ),
		)
	);
	foreach ( $managed_ids as $managed_id ) {
		$external_id = (string) get_post_meta( $managed_id, YABAO_META_EXTERNAL_ID, true );
		if ( $external_id && ! in_array( $external_id, $incoming_external_ids, true ) ) {
			yabao_import_message( $report, 'warning', 'Товар отсутствует в наборе и оставлен без изменений (no-delete-on-missing).', array( 'external_id' => $external_id, 'product_id' => (int) $managed_id ) );
		}
	}

	return yabao_import_finish_report( $report );
}

function yabao_import_finish_report( array $report ): array {
	$report['finished_at'] = current_time( 'mysql' );

	$logs = get_option( YABAO_IMPORT_LOG_OPTION, array() );
	$logs = is_array( $logs ) ? $logs : array();
	array_unshift( $logs, $report );
	$logs = array_slice( $logs, 0, YABAO_IMPORT_MAX_LOGS );
	update_option( YABAO_IMPORT_LOG_OPTION, $logs, false );

	if ( function_exists( 'wc_get_logger' ) ) {
		$logger = wc_get_logger();
		$context = array( 'source' => 'yabao-importer' );
		$logger->info(
			sprintf(
				'Run %s dry_run=%s create=%d update=%d noop=%d skipped=%d warnings=%d errors=%d',
				$report['run_id'],
				$report['dry_run'] ? 'yes' : 'no',
				$report['summary']['create'],
				$report['summary']['update'],
				$report['summary']['noop'],
				$report['summary']['skipped'],
				$report['summary']['warnings'],
				$report['summary']['errors']
			),
			$context
		);
		foreach ( $report['messages'] as $message ) {
			$method = 'error' === $message['level'] ? 'error' : ( 'warning' === $message['level'] ? 'warning' : 'debug' );
			$logger->{$method}( $message['message'] . ( $message['context'] ? ' ' . wp_json_encode( $message['context'], JSON_UNESCAPED_UNICODE ) : '' ), $context );
		}
	}

	return $report;
}
