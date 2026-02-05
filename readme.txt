=== EBICS API ===
Contributors: asvirin
Tags: ebics, banking, api, finance
Requires at least: 5.2
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Provides a Drupal-friendly interface to an EBICS API microservice for secure bank communication.

== Description ==

The EBICS API plugin allows WordPress sites to communicate with banks using the EBICS protocol via an external microservice. It provides a user-friendly interface for managing connections, executing transactions (upload, download, info), and viewing access logs.

**Features:**

*   **Connection Management:** View your configured bank connections.
*   **Transaction Handling:** Perform various EBICS transactions like file uploads (BTU), downloads (BTD, HAC), and information retrieval (HAA, HKD, HTD).
*   **Logs:** View detailed access logs for all API interactions.
*   **Secure:** Uses WordPress nonces and capability checks.

**Note:** This plugin requires an external EBICS API microservice to function. You must configure the API Host and API Key in the settings.

**Useful Links:**

*   [EBICS API Client homepage](https://sites.google.com/view/ebics-api-client)
*   [Demo EBICS API Client instance](https://tinyurl.com/safe-ebics)
*   [Video guide for setting up EBICS API Client instance](https://youtu.be/S14Qkt5m0NI)
*   [GitHub Repository](https://github.com/ebics-api/ebics-api-client-wordpress)

== Installation ==

1.  Upload the `ebics-api` folder to the `/wp-content/plugins/` directory.
2.  Run `composer install` inside the plugin directory to install dependencies.
3.  Activate the plugin through the 'Plugins' menu in WordPress.
4.  Go to **Settings > EBICS API** and configure your API Host and API Key.

== Frequently Asked Questions ==

= What is the API Host? =

The API Host is the base URL of the EBICS API microservice you are connecting to.

= Where do I get the API Key? =

The API Key should be provided by the administrator of the EBICS API microservice.

== Screenshots ==

1.  **Settings Page:** Configure the API connection details.
2.  **Connections Tab:** View a list of available bank connections.
3.  **Transaction Tab:** Execute EBICS orders.
4.  **Logs Tab:** View API access logs.

== Changelog ==

= 1.0.0 =
*   Initial release.
