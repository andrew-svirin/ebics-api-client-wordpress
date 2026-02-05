<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Provides a wrapper service for the EBICS API client.
 */
class Ebics_API_Client_Service {

    /**
     * The EBICS API client instance.
     *
     * @var \EbicsApi\Client\EbicsApiClient|null
     */
    private static $client = null;

    /**
     * Gets or initializes the EBICS API client.
     *
     * @return \EbicsApi\Client\EbicsApiClient
     * @throws \Exception If API host or key are not configured.
     */
    public static function get_client() {
        if ( self::$client === null ) {
            $options = get_option( 'ebics_api_settings' );
            $api_host = $options['api_host'] ?? '';
            $api_key = $options['api_key'] ?? '';

            if ( empty( $api_host ) || empty( $api_key ) ) {
                throw new \Exception( 'API host and key are not configured.' );
            }

            self::$client = new \EbicsApi\Client\EbicsApiClient( $api_key, $api_host );
        }

        return self::$client;
    }
}
