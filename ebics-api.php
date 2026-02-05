<?php
/**
 * Plugin Name:       EBICS API
 * Description:       Provides a Drupal-friendly interface to an EBICS API microservice for secure bank communication.
 * Version:           1.0.0
 * Author:            Andrii Svirin
 * License:           GPL-2.0+
 * Text Domain:       ebics-api
 */

// Exit if accessed directly to prevent security leaks
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check for Composer dependencies and handle missing ones gracefully.
 */
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    /**
     * Display a warning notice if dependencies are missing.
     */
    function ebics_api_missing_dependencies_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'EBICS API plugin requires Composer dependencies. Please run "composer install" in the plugin directory.', 'ebics-api' ); ?></p>
        </div>
        <?php
    }
    add_action( 'admin_notices', 'ebics_api_missing_dependencies_notice' );

    /**
     * Renders a placeholder settings page with a warning.
     */
    function ebics_api_placeholder_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'EBICS API', 'ebics-api' ); ?></h1>
            <div class="notice notice-error">
                <p><?php esc_html_e( 'EBICS API plugin is not fully functional because required dependencies are missing. Please run "composer install" in the plugin directory.', 'ebics-api' ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Adds a submenu page that shows the placeholder content.
     */
    function ebics_api_add_placeholder_admin_menu() {
        add_submenu_page(
            'options-general.php',
            'EBICS API',
            'EBICS API',
            'manage_options',
            'ebics-api',
            'ebics_api_placeholder_settings_page'
        );
    }
    add_action( 'admin_menu', 'ebics_api_add_placeholder_admin_menu' );

    // Stop loading the rest of the plugin to prevent fatal errors.
    return;
}

// If we get here, dependencies are loaded. Proceed with the full plugin.
require_once __DIR__ . '/vendor/autoload.php';

// Include the class files.
require_once __DIR__ . '/includes/class-ebics-api-client-service.php';
require_once __DIR__ . '/includes/class-ebics-api-transaction-page.php';

// Initialize the transaction page handler.
$ebics_api_transaction_page = new Ebics_API_Transaction_Page();

/**
 * Adds a submenu page under the Settings menu.
 */
function ebics_api_add_admin_menu() {
    add_submenu_page(
        'options-general.php',
        'EBICS API',
        'EBICS API',
        'manage_options',
        'ebics-api',
        'ebics_api_settings_page'
    );
}
add_action( 'admin_menu', 'ebics_api_add_admin_menu' );

/**
 * Register settings and fields.
 */
function ebics_api_settings_init() {
    register_setting(
        'ebics_api_settings_group',
        'ebics_api_settings',
        'ebics_api_settings_sanitize'
    );

    add_settings_section(
        'ebics_api_general_section',
        __( 'General Settings', 'ebics-api' ),
        'ebics_api_general_section_callback',
        'ebics-api'
    );

    add_settings_field(
        'api_host',
        __( 'API Host', 'ebics-api' ),
        'ebics_api_api_host_callback',
        'ebics-api',
        'ebics_api_general_section'
    );

    add_settings_field(
        'api_key',
        __( 'API Key', 'ebics-api' ),
        'ebics_api_api_key_callback',
        'ebics-api',
        'ebics_api_general_section'
    );
}
add_action( 'admin_init', 'ebics_api_settings_init' );

/**
 * Sanitize callback for EBICS API settings.
 */
function ebics_api_settings_sanitize( $input ) {
    $new_input = [];
    if ( isset( $input['api_host'] ) ) {
        $new_input['api_host'] = esc_url_raw( rtrim( $input['api_host'], '/' ) );
    }
    if ( isset( $input['api_key'] ) ) {
        $new_input['api_key'] = sanitize_text_field( $input['api_key'] );
    }
    return $new_input;
}

/**
 * Section callback for general settings.
 */
function ebics_api_general_section_callback() {
    echo '<p>' . esc_html__( 'Base URL of EBICS API service and authentication key.', 'ebics-api' ) . '</p>';
}

/**
 * Render callback for API Host field.
 */
function ebics_api_api_host_callback() {
    $options = get_option( 'ebics_api_settings' );
    $api_host = $options['api_host'] ?? '';
    echo '<input type="url" id="api_host" name="ebics_api_settings[api_host]" value="' . esc_attr( $api_host ) . '" class="regular-text" required />';
    echo '<p class="description">' . esc_html__( 'Base URL of EBICS API service.', 'ebics-api' ) . '</p>';
}

/**
 * Render callback for API Key field.
 */
function ebics_api_api_key_callback() {
    $options = get_option( 'ebics_api_settings' );
    $api_key = $options['api_key'] ?? '';
    echo '<input type="text" id="api_key" name="ebics_api_settings[api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text" required />';
    echo '<p class="description">' . esc_html__( 'Authentication key for EBICS API.', 'ebics-api' ) . '</p>';
}

/**
 * Renders the settings page with tabs.
 */
function ebics_api_settings_page() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'EBICS API', 'ebics-api' ); ?></h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=ebics-api&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'ebics-api' ); ?></a>
            <a href="?page=ebics-api&tab=connections" class="nav-tab <?php echo $active_tab === 'connections' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Connections', 'ebics-api' ); ?></a>
            <a href="?page=ebics-api&tab=transaction" class="nav-tab <?php echo $active_tab === 'transaction' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Transaction', 'ebics-api' ); ?></a>
            <a href="?page=ebics-api&tab=logs" class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Logs', 'ebics-api' ); ?></a>
        </h2>

        <div class="tab-content">
            <?php
            switch ( $active_tab ) {
                case 'connections':
                    ebics_api_render_connections_tab();
                    break;
                case 'transaction':
                    ebics_api_render_transaction_tab();
                    break;
                case 'logs':
                    ebics_api_render_logs_tab();
                    break;
                case 'settings':
                default:
                    ebics_api_render_settings_tab();
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Renders the content for the Settings tab.
 */
function ebics_api_render_settings_tab() {
    ?>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'ebics_api_settings_group' );
        do_settings_sections( 'ebics-api' );
        submit_button();
        ?>
    </form>
    <?php
}

/**
 * Renders the content for the Connections tab.
 */
function ebics_api_render_connections_tab() {
    echo '<h3>' . esc_html__( 'Connections', 'ebics-api' ) . '</h3>';

    try {
        $client = Ebics_API_Client_Service::get_client();
        $connections = $client->connectionList()->body;

        if ( empty( $connections ) ) {
            echo '<p>' . esc_html__( 'No connections found.', 'ebics-api' ) . '</p>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'ID', 'ebics-api' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Name', 'ebics-api' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'User ID', 'ebics-api' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'ebics-api' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Created At', 'ebics-api' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $connections as $connection ) : ?>
                    <tr>
                        <td><?php echo esc_html( $connection['id'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $connection['name'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $connection['user_id'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $connection['keyring_status'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $connection['created_at'] ?? '' ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    } catch ( \Exception $e ) {
        /* translators: %s: Error message */
        echo '<div class="notice notice-error"><p>' . sprintf( esc_html__( 'Unexpected error: %s', 'ebics-api' ), esc_html( $e->getMessage() ) ) . '</p></div>';
    }
}

/**
 * Renders the content for the Transaction tab.
 */
function ebics_api_render_transaction_tab() {
    global $ebics_api_transaction_page;
    $ebics_api_transaction_page->render();
}

/**
 * Renders the content for the Logs tab.
 */
function ebics_api_render_logs_tab() {
    echo '<h3>' . esc_html__( 'Logs', 'ebics-api' ) . '</h3>';

    try {
        $client = Ebics_API_Client_Service::get_client();

        $limit = 10;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $current_page = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1;
        $current_page = max( 1, intval( $current_page ) );

        $logs_response = $client->accessLogList( [
            'page' => $current_page,
            'limit' => $limit,
        ] );

        $logs = $logs_response->body;

        $x_total = $logs_response->headers['x-total'] ?? 0;
        $total_items = is_array( $x_total ) ? (int) ( $x_total[0] ?? 0 ) : (int) $x_total;
        $total_pages = ceil( $total_items / $limit );

        if ( empty( $logs ) ) {
            echo '<p>' . esc_html__( 'No logs found.', 'ebics-api' ) . '</p>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'ID', 'ebics-api' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'C#', 'ebics-api' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Request', 'ebics-api' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Response', 'ebics-api' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Created At', 'ebics-api' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $logs as $log ) : ?>
                    <tr>
                        <td><?php echo esc_html( $log['id'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $log['ebics_connection_id'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $log['request_message'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $log['response_message'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $log['created_at'] ?? '' ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo wp_kses_post( paginate_links( [
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => __( '&laquo;', 'ebics-api' ),
                    'next_text' => __( '&raquo;', 'ebics-api' ),
                    'total' => $total_pages,
                    'current' => $current_page,
                ] ) );
                ?>
            </div>
        </div>
        <?php
    } catch ( \Exception $e ) {
        /* translators: %s: Error message */
        echo '<div class="notice notice-error"><p>' . sprintf( esc_html__( 'Unexpected error: %s', 'ebics-api' ), esc_html( $e->getMessage() ) ) . '</p></div>';
    }
}
