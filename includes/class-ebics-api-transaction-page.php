<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ebics_API_Transaction_Page {

    public function __construct() {
        add_action( 'wp_ajax_ebics_api_load_order_types', [ $this, 'ajax_load_order_types' ] );
        add_action( 'wp_ajax_ebics_api_load_transaction_fields', [ $this, 'ajax_load_transaction_fields' ] );
        add_action( 'wp_ajax_ebics_api_submit_transaction', [ $this, 'ajax_submit_transaction' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    public function enqueue_scripts( $hook ) {
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'ebics-api' && isset( $_GET['tab'] ) && $_GET['tab'] === 'transaction' ) {
            wp_enqueue_script( 'ebics-api-transaction', plugin_dir_url( __DIR__ ) . 'assets/js/transaction.js', [ 'jquery' ], '1.0.0', true );
            wp_localize_script( 'ebics-api-transaction', 'ebics_api_ajax', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'ebics_api_transaction_nonce' ),
                'error_loading_order_types' => __( 'Error loading order types: ', 'ebics-api' ),
                'error_loading_fields' => __( 'Error loading transaction fields: ', 'ebics-api' ),
                'error_generic' => __( 'An error occurred.', 'ebics-api' ),
            ] );
        }
    }

    public function render() {
        try {
            $client = Ebics_API_Client_Service::get_client();
            $connections = $client->connectionList()->body;
        } catch ( \Exception $e ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $e->getMessage() ) . '</p></div>';
            return;
        }

        ?>
        <div class="wrap">
            <h3><?php esc_html_e( 'Transaction', 'ebics-api' ); ?></h3>
            <form id="ebics-api-transaction-form" enctype="multipart/form-data">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="connection_id"><?php esc_html_e( 'Connection', 'ebics-api' ); ?></label></th>
                        <td>
                            <select name="connection_id" id="connection_id" required>
                                <option value=""><?php esc_html_e( '- Select connection -', 'ebics-api' ); ?></option>
                                <?php foreach ( $connections as $connection ) : ?>
                                    <option value="<?php echo esc_attr( $connection['id'] ); ?>"><?php echo esc_html( $connection['name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr id="order-type-row" style="display:none;">
                        <th scope="row"><label for="order_type"><?php esc_html_e( 'Order type', 'ebics-api' ); ?></label></th>
                        <td>
                            <select name="order_type" id="order_type" required disabled>
                                <option value=""><?php esc_html_e( '- Select order type -', 'ebics-api' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <div id="transaction-wrapper"></div>

                <div id="result-message-wrapper" style="margin-top: 20px;"></div>

                <p class="submit">
                    <button type="submit" class="button button-primary" id="submit-transaction"><?php esc_html_e( 'Submit', 'ebics-api' ); ?></button>
                    <span class="spinner" style="float: none; margin-top: 0;"></span>
                </p>
            </form>
        </div>
        <?php
    }

    public function ajax_load_order_types() {
        check_ajax_referer( 'ebics_api_transaction_nonce', 'nonce' );

        $connection_id = sanitize_text_field( $_POST['connection_id'] );
        if ( empty( $connection_id ) ) {
            wp_send_json_error( __( 'Missing connection ID', 'ebics-api' ) );
        }

        try {
            $client = Ebics_API_Client_Service::get_client();
            $order_types = $client->keyringOrderTypes( [ 'connection_id' => $connection_id ] );
            
            $options = [];
            foreach ( $order_types as $order_type ) {
                $key = $this->build_order_type_key( $order_type );
                $options[] = [
                    'value' => $key,
                    'label' => $order_type['name'] . ' - ' . $order_type['description'],
                ];
            }
            
            wp_send_json_success( $options );

        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
    }

    public function ajax_load_transaction_fields() {
        check_ajax_referer( 'ebics_api_transaction_nonce', 'nonce' );

        $order_type_key = sanitize_text_field( $_POST['order_type'] );
        $details = $this->parse_order_type_key( $order_type_key );
        
        $html = '';

        if ( $details['op_type'] === 'UPLOAD' ) {
            $html .= '<table class="form-table">';
            $html .= '<tr><th scope="row"><label for="es_flag">' . esc_html__( 'ES Flag', 'ebics-api' ) . '</label></th><td><input type="checkbox" name="es_flag" id="es_flag" value="1"> <span class="description">' . esc_html__( 'Process upload with EDS', 'ebics-api' ) . '</span></td></tr>';
            $html .= '<tr><th scope="row"><label for="file_upload">' . esc_html__( 'Upload file', 'ebics-api' ) . '</label></th><td><input type="file" name="file_upload" id="file_upload" required></td></tr>';
            $html .= '</table>';
        } elseif ( $details['op_type'] === 'DOWNLOAD' ) {
            $html .= '<table class="form-table">';
            $html .= '<tr><th scope="row"><label for="start_date">' . esc_html__( 'Start date', 'ebics-api' ) . '</label></th><td><input type="date" name="start_date" id="start_date" value="' . date( 'Y-m-01' ) . '" required></td></tr>';
            $html .= '<tr><th scope="row"><label for="end_date">' . esc_html__( 'End date', 'ebics-api' ) . '</label></th><td><input type="date" name="end_date" id="end_date" value="' . date( 'Y-m-d' ) . '" required></td></tr>';
            $html .= '</table>';
        }

        wp_send_json_success( $html );
    }

    public function ajax_submit_transaction() {
        check_ajax_referer( 'ebics_api_transaction_nonce', 'nonce' );

        $connection_id = sanitize_text_field( $_POST['connection_id'] );
        $order_type_key = sanitize_text_field( $_POST['order_type'] );
        $details = $this->parse_order_type_key( $order_type_key );
        
        try {
            $client = Ebics_API_Client_Service::get_client();
            $message = '';

            if ( $details['op_type'] === 'DOWNLOAD' ) {
                $start_date = sanitize_text_field( $_POST['start_date'] );
                $end_date = sanitize_text_field( $_POST['end_date'] );

                if ( $details['name'] === 'BTD' ) {
                    $result = $client->orderTypeBtd( array_filter( [
                        'connection_id' => $connection_id,
                        'service_name' => $details['service']['service_name'],
                        'service_option' => $details['service']['service_option'],
                        'scope' => $details['service']['scope'],
                        'msg_name' => $details['service']['msg_name'],
                        'msg_name_version' => $details['service']['msg_name_version'],
                        'msg_name_format' => $details['service']['msg_name_format'],
                        'container' => $details['service']['container'],
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                    ] ) );
                    $message = $result['txt'];
                } elseif ( $details['name'] === 'HAC' ) {
                    $result = $client->orderTypeHac( [
                        'connection_id' => $connection_id,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                    ] );
                    $message = $result['xml'];
                } else {
                    throw new \Exception( sprintf( __( 'Order type %s not supported for DOWNLOAD.', 'ebics-api' ), $details['name'] ) );
                }

            } elseif ( $details['op_type'] === 'INFO' ) {
                if ( $details['name'] === 'HAA' ) {
                    $result = $client->orderTypeHaa( [ 'connection_id' => $connection_id ] );
                    $message = $result['xml'];
                } elseif ( $details['name'] === 'HKD' ) {
                    $result = $client->orderTypeHkd( [ 'connection_id' => $connection_id ] );
                    $message = $result['xml'];
                } elseif ( $details['name'] === 'HTD' ) {
                    $result = $client->orderTypeHtd( [ 'connection_id' => $connection_id ] );
                    $message = $result['xml'];
                } else {
                    throw new \Exception( sprintf( __( 'Order type %s not supported for INFO.', 'ebics-api' ), $details['name'] ) );
                }

            } elseif ( $details['op_type'] === 'UPLOAD' ) {
                if ( ! isset( $_FILES['file_upload'] ) ) {
                    throw new \Exception( __( 'No file uploaded.', 'ebics-api' ) );
                }

                $file = $_FILES['file_upload'];
                $file_data = fopen( $file['tmp_name'], 'r' );
                $es_flag = isset( $_POST['es_flag'] ) ? 1 : 0;

                if ( $details['name'] === 'BTU' ) {
                    $result = $client->orderTypeBtu( array_filter( [
                        'connection_id' => $connection_id,
                        'service_name' => $details['service']['service_name'],
                        'service_option' => $details['service']['service_option'],
                        'scope' => $details['service']['scope'],
                        'msg_name' => $details['service']['msg_name'],
                        'msg_name_version' => $details['service']['msg_name_version'],
                        'file_data' => $file_data,
                        'with_es' => $es_flag,
                    ] ) );
                    $message = __( 'File Transaction: ', 'ebics-api' ) . $result['txt'];
                } else {
                    throw new \Exception( sprintf( __( 'Order type %s not supported for UPLOAD.', 'ebics-api' ), $details['name'] ) );
                }
            } else {
                 $message = sprintf( __( 'Connection: %s, Order type: %s', 'ebics-api' ), $connection_id, $order_type_key );
            }

            wp_send_json_success( '<pre><code>' . htmlspecialchars( $message ) . '</code></pre>' );

        } catch ( \Exception $e ) {
            wp_send_json_error( '<pre><code>' . sprintf( __( 'Response: %s', 'ebics-api' ), htmlspecialchars( $e->getMessage() ) ) . '</code></pre>' );
        }
    }

    private function build_order_type_key( $order_type ) {
        $key = $order_type['op_type'] . '|' . $order_type['name'];

        if ( ! empty( $order_type['service'] ) ) {
            $s = $order_type['service'];
            $svc = [
                $s['service_name'] ?? '',
                $s['service_option'] ?? '',
                $s['scope'] ?? '',
                $s['container'] ?? '',
                $s['msg_name'] ?? '',
                $s['msg_name_version'] ?? '',
                $s['msg_name_format'] ?? '',
            ];
            $key .= '|' . implode( ':', $svc );
        }

        return $key;
    }

    private function parse_order_type_key( $key ) {
        $parts = explode( '|', $key, 3 );
        $op_type = $parts[0] ?? null;
        $name = $parts[1] ?? null;
        $svc_part = $parts[2] ?? null;

        $order_type_details = [
            'op_type' => $op_type,
            'name' => $name,
            'service' => null,
        ];

        if ( $svc_part !== null ) {
            $svc_parts = explode( ':', $svc_part );
            $order_type_details['service'] = [
                'service_name' => $svc_parts[0] ?? null,
                'service_option' => $svc_parts[1] ?? null,
                'scope' => $svc_parts[2] ?? null,
                'container' => $svc_parts[3] ?? null,
                'msg_name' => $svc_parts[4] ?? null,
                'msg_name_version' => $svc_parts[5] ?? null,
                'msg_name_format' => $svc_parts[6] ?? null,
            ];
        }

        return $order_type_details;
    }
}
