<?php
/** Stage 66 importer admin UI. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function yabao_import_admin_menu(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	add_submenu_page(
		'woocommerce',
		'Импорт товаров — Я Бао Завари',
		'Импорт товаров',
		'manage_woocommerce',
		'yabao-product-import',
		'yabao_import_admin_page'
	);
}
add_action( 'admin_menu', 'yabao_import_admin_menu', 40 );

function yabao_import_report_transient_key(): string {
	return 'yabao_import_report_' . get_current_user_id();
}

function yabao_import_admin_page(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Недостаточно прав.', 'ya-bao' ) );
	}

	$report = get_transient( yabao_import_report_transient_key() );
	if ( $report ) {
		delete_transient( yabao_import_report_transient_key() );
	}
	$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
	?>
	<div class="wrap">
		<h1>Импорт товаров — Я Бао Завари</h1>
		<p>Stage 66 принимает канонический JSON-контракт 1.0.0. Сначала запускай dry-run. Никаких товаров, вариаций или изображений dry-run не меняет.</p>
		<p><strong>WP environment:</strong> <code><?php echo esc_html( $environment ); ?></code><?php if ( 'production' === $environment ) : ?> — demo-товары при реальном импорте будут принудительно draft.<?php endif; ?></p>

		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
			<input type="hidden" name="action" value="yabao_product_import" />
			<?php wp_nonce_field( 'yabao_product_import', 'yabao_import_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Источник</th>
					<td>
						<label><input type="radio" name="source_mode" value="bundled" checked /> Встроенный <code>data/products.json</code> (demo QA)</label><br />
						<label><input type="radio" name="source_mode" value="upload" /> Загрузить JSON-файл</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yabao-import-file">JSON-файл</label></th>
					<td><input id="yabao-import-file" type="file" name="import_file" accept="application/json,.json" /><p class="description">До 5 МБ. Используется только при выборе «Загрузить JSON-файл».</p></td>
				</tr>
				<tr>
					<th scope="row">Подтверждение записи</th>
					<td><label><input type="checkbox" name="confirm_apply" value="1" /> Разрешаю изменить товары WooCommerce при нажатии «Импортировать».</label></td>
				</tr>
			</table>
			<p class="submit">
				<button class="button button-secondary" type="submit" name="import_mode" value="dry-run">Проверить (dry-run)</button>
				<button class="button button-primary" type="submit" name="import_mode" value="apply">Импортировать</button>
			</p>
		</form>

		<?php if ( is_array( $report ) ) : ?>
			<hr />
			<h2>Последний запуск</h2>
			<p><strong><?php echo $report['dry_run'] ? 'Dry-run' : 'Import'; ?></strong> · run <code><?php echo esc_html( $report['run_id'] ); ?></code> · source <code><?php echo esc_html( $report['source'] ); ?></code></p>
			<table class="widefat striped" style="max-width:960px">
				<thead><tr><th>Create</th><th>Update</th><th>No-op</th><th>Skipped</th><th>Warnings</th><th>Errors</th></tr></thead>
				<tbody><tr>
					<td><?php echo esc_html( (string) $report['summary']['create'] ); ?></td>
					<td><?php echo esc_html( (string) $report['summary']['update'] ); ?></td>
					<td><?php echo esc_html( (string) $report['summary']['noop'] ); ?></td>
					<td><?php echo esc_html( (string) $report['summary']['skipped'] ); ?></td>
					<td><?php echo esc_html( (string) $report['summary']['warnings'] ); ?></td>
					<td><?php echo esc_html( (string) $report['summary']['errors'] ); ?></td>
				</tr></tbody>
			</table>
			<h3>Лог</h3>
			<table class="widefat striped" style="max-width:1200px">
				<thead><tr><th style="width:90px">Уровень</th><th>Сообщение</th><th>Контекст</th></tr></thead>
				<tbody>
				<?php foreach ( $report['messages'] as $message ) : ?>
					<tr><td><?php echo esc_html( $message['level'] ); ?></td><td><?php echo esc_html( $message['message'] ); ?></td><td><code><?php echo esc_html( $message['context'] ? wp_json_encode( $message['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '' ); ?></code></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

function yabao_import_read_admin_source( string $mode ) {
	if ( 'bundled' === $mode ) {
		$path = get_template_directory() . '/data/products.json';
		if ( ! is_readable( $path ) ) {
			return new WP_Error( 'yabao_import_source_missing', 'Не найден data/products.json внутри темы.' );
		}
		return array( 'json' => file_get_contents( $path ), 'label' => 'theme:data/products.json' );
	}

	if ( 'upload' !== $mode ) {
		return new WP_Error( 'yabao_import_source_mode', 'Неизвестный режим источника.' );
	}
	if ( empty( $_FILES['import_file'] ) || ! is_array( $_FILES['import_file'] ) ) {
		return new WP_Error( 'yabao_import_file_missing', 'Выбери JSON-файл.' );
	}
	$file = $_FILES['import_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
		return new WP_Error( 'yabao_import_upload_error', 'Ошибка загрузки JSON-файла.' );
	}
	if ( (int) $file['size'] > YABAO_IMPORT_MAX_FILE_BYTES ) {
		return new WP_Error( 'yabao_import_file_size', 'JSON-файл больше 5 МБ.' );
	}
	$tmp = (string) $file['tmp_name'];
	if ( ! is_uploaded_file( $tmp ) || ! is_readable( $tmp ) ) {
		return new WP_Error( 'yabao_import_upload_invalid', 'Временный файл загрузки недоступен.' );
	}
	return array( 'json' => file_get_contents( $tmp ), 'label' => 'upload:' . sanitize_file_name( (string) $file['name'] ) );
}

function yabao_import_admin_error_report( string $message, bool $dry_run, string $source = 'admin' ): array {
	$report = yabao_import_report( $dry_run, $source );
	yabao_import_message( $report, 'error', $message );
	return yabao_import_finish_report( $report );
}

function yabao_import_admin_handle(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Недостаточно прав.', 'ya-bao' ) );
	}
	check_admin_referer( 'yabao_product_import', 'yabao_import_nonce' );

	$mode       = isset( $_POST['import_mode'] ) ? sanitize_key( wp_unslash( $_POST['import_mode'] ) ) : 'dry-run';
	$dry_run    = 'apply' !== $mode;
	$source_mode = isset( $_POST['source_mode'] ) ? sanitize_key( wp_unslash( $_POST['source_mode'] ) ) : 'bundled';

	if ( ! $dry_run && empty( $_POST['confirm_apply'] ) ) {
		$report = yabao_import_admin_error_report( 'Для реального импорта отметь подтверждение записи в WooCommerce.', false );
	} else {
		$source = yabao_import_read_admin_source( $source_mode );
		if ( is_wp_error( $source ) ) {
			$report = yabao_import_admin_error_report( $source->get_error_message(), $dry_run );
		} else {
			try {
				$dataset = json_decode( (string) $source['json'], true, 512, JSON_THROW_ON_ERROR );
				if ( ! is_array( $dataset ) ) {
					throw new RuntimeException( 'JSON root должен быть объектом.' );
				}
				$report = yabao_import_run( $dataset, $dry_run, (string) $source['label'] );
			} catch ( Throwable $error ) {
				$report = yabao_import_admin_error_report( 'Не удалось разобрать JSON: ' . $error->getMessage(), $dry_run, (string) $source['label'] );
			}
		}
	}

	set_transient( yabao_import_report_transient_key(), $report, 10 * MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'admin.php?page=yabao-product-import' ) );
	exit;
}
add_action( 'admin_post_yabao_product_import', 'yabao_import_admin_handle' );
