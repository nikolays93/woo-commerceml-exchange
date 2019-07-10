<?php

/*
 * Plugin Name: 1c4wp
 * Plugin URI: https://github.com/nikolays93
 * Description: 1c exchange prototype
 * Version: 0.2
 * Author: NikolayS93
 * Author URI: https://vk.com/nikolays_93
 * Author EMAIL: NikolayS93@ya.ru
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 1c4wp
 * Domain Path: /languages/
 */

namespace NikolayS93\Exchange;

use NikolayS93\Exchange\Model\ExchangeProduct;
use NikolayS93\Exchange\Model\ExchangeOffer;

// for debug
// $_SERVER['PHP_AUTH_USER'] = 'root';
// $_SERVER['PHP_AUTH_PW'] = 'q1w2';

// define('EX_DEBUG_ONLY', TRUE);

if ( !defined( 'ABSPATH' ) ) exit('You shall not pass');
if (version_compare(PHP_VERSION, '5.4') < 0) {
    throw new \Exception('Plugin requires PHP 5.4 or above');
}

if( defined(__NAMESPACE__ . '\PLUGIN_DIR') || defined(__NAMESPACE__ . '\PLUGIN_FILE') ) return;

if( !defined(__NAMESPACE__ . '\PLUGIN_DIR') ) define(__NAMESPACE__ . '\PLUGIN_DIR', __DIR__);
if( !defined(__NAMESPACE__ . '\PLUGIN_FILE') ) define(__NAMESPACE__ . '\PLUGIN_FILE', __FILE__);

require_once ABSPATH . "wp-admin/includes/plugin.php";
require_once PLUGIN_DIR . '/vendor/autoload.php';

define(__NAMESPACE__ . '\DEFAULT_DEVELOPER_TAX_SLUG', 'developer');
define(__NAMESPACE__ . '\DEFAULT_WAREHOUSE_TAX_SLUG', 'warehouse');

/**
 * Uniq prefix
 */
define(__NAMESPACE__ . '\DOMAIN', Plugin::get_plugin_data('TextDomain'));

/**
 * Server can get max size
 * @todo set to filter
 */
if(!defined(__NAMESPACE__ . '\FILE_LIMIT')) define(__NAMESPACE__ . '\FILE_LIMIT', null);

/**
 * Work in charset
 */
if(!defined(__NAMESPACE__ . '\XML_CHARSET') ) define(__NAMESPACE__ . '\XML_CHARSET', 'UTF-8');

/**
 * Notice type
 * @todo check this
 */
if (!defined(__NAMESPACE__ . '\SUPPRESS_NOTICES')) define(__NAMESPACE__ . '\SUPPRESS_NOTICES', false);

/**
 * Simple products only
 * @todo check this
 */
if (!defined(__NAMESPACE__ . '\DISABLE_VARIATIONS')) define(__NAMESPACE__ . '\DISABLE_VARIATIONS', false);

/**
 * Current timestamp
 */
if (!defined(__NAMESPACE__ . '\TIMESTAMP')) define(__NAMESPACE__ . '\TIMESTAMP', time());

/**
 * Auth cookie name
 */
if (!defined(__NAMESPACE__ . '\COOKIENAME')) define(__NAMESPACE__ . '\COOKIENAME', 'ex-auth');

/**
 * Woocommerce currency for single price type
 * @todo move to function
 */
if (!defined(__NAMESPACE__ . '\CURRENCY')) define(__NAMESPACE__ . '\CURRENCY', null);

if(!defined('NikolayS93\Exchange\Model\EXT_ID')) define('NikolayS93\Exchange\Model\EXT_ID', 'EXT_ID');
if (!defined('EX_EXT_METAFIELD')) define('EX_EXT_METAFIELD', 'EXT_ID');

register_activation_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'activate' ) );
// register_uninstall_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'uninstall' ) );
// register_deactivation_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'deactivate' ) );

require_once PLUGIN_DIR . '/include/utils.php';
require_once PLUGIN_DIR . '/include/statistic.php';

require_once PLUGIN_DIR . '/include/post-types.php';
require_once PLUGIN_DIR . '/include/admin-page.php';
require_once PLUGIN_DIR . '/include/exchange.php';
require_once PLUGIN_DIR . '/include/register-query.php';
require_once PLUGIN_DIR . '/include/additional-properties.php';

add_filter( 'exchange_posts_import_offset', __NAMESPACE__ . '\metas_exchange_posts_import_offset', 10, 4 );
function metas_exchange_posts_import_offset($offset, $productsCount, $offersCount, $filename) {
    if( 0 === strpos($filename, 'rest') || 0 === strpos($filename, 'price') ) {
        $offset = 1000;
    }

    return $offset;
}

add_action( '1c4wp_exchange', __NAMESPACE__ . '\do_exchange', 10 );


/**
 * Register custom taxonomies
 */
add_action('init', __NAMESPACE__ . '\register_post_types');

/**
 * Add admin menu page
 */
add_action( 'plugins_loaded', __NAMESPACE__ . '\admin_page', 10 );

/**
 * Add last modified to products table
 */
// add_filter( 'manage_edit-product_columns', __NAMESPACE__ . '\true_add_post_columns', 10, 1 );
// function true_add_post_columns($my_columns) {
//     $my_columns['modified'] = 'Last modified';
//     return $my_columns;
// }

// add_action( 'manage_posts_custom_column', __NAMESPACE__ . '\true_fill_post_columns', 10, 1 );
// function true_fill_post_columns( $column ) {
//     global $post;
//     switch ( $column ) {
//         case 'modified':
//             echo $post->post_modified;
//             break;
//     }
// }

add_filter( 'post_date_column_status', function($status, $post, $strDate, $mode) {
    if( 'product' != $post->post_status ) return $status;

    if( $post->post_date < $post->post_modified && 'future' !== $post->post_status ) {

        if ( 'publish' === $post->post_status ) {
            $time   = get_post_modified_time( 'G', true, $post );
            $time_diff = time() - $time;

            if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
                $showTime = sprintf( __( '%s ago' ), human_time_diff( $time ) );
            } else {
                $showTime = mysql2date( __( 'Y/m/d' ), $time );
            }

            echo __( 'Last Modified' ) . '<br />';

            /** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
            echo '<abbr title="' . $post->post_modified . '">' . apply_filters( 'post_date_column_time', $showTime, $post, 'date', $mode ) . '</abbr><br />';
        }
    }

    return $status;
}, 10, 4 );

/** @var @todo Change hook */
add_action( 'restrict_manage_posts', function($post_type) {
    if( 'product' == $post_type ) {
        ?>
        <style>
            body table.wp-list-table td.column-thumb img {
                max-width: 75px;
                max-height: 75px;
            }
        </style>
        <?php
    }
}, 10, 1 );

add_action( 'woocommerce_attribute_deleted', function($id, $attribute_name, $taxonomy) {
    global $wpdb;

    $is_deleted = $wpdb->delete(
        $wpdb->prefix . 'woocommerce_attribute_taxonomymeta',
        array( 'tax_id' => $id ),
        array( '%d' )
    );
}, 10, 3 );

function strict_error_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
    if (0 === error_reporting()) return false;

    switch ($errno) {
        case E_NOTICE:
        case E_USER_NOTICE:
        $type = "Notice";
        break;
        case E_WARNING:
        case E_USER_WARNING:
        $type = "Warning";
        break;
        case E_ERROR:
        case E_USER_ERROR:
        $type = "Fatal Error";
        break;
        default:
        $type = "Unknown Error";
        break;
    }

    $message = sprintf("%s in %s on line %d", $errstr, $errfile, $errline);
    Utils::error($message, "PHP $type");
}

function strict_exception_handler($exception)
{
    $message = sprintf("%s in %s on line %d", $exception->getMessage(), $exception->getFile(), $exception->getLine());
    Utils::error($message, "Exception");
}

function output_callback($buffer)
{
    global $ex_is_error;

    if ( !headers_sent() ) {
        $is_xml = @$_GET['mode'] == 'query';
        $content_type = !$is_xml || $ex_is_error ? 'text/plain' : 'text/xml';
        header("Content-Type: $content_type; charset=" . XML_CHARSET);
    }

    $buffer = (XML_CHARSET == 'UTF-8') ? "\xEF\xBB\xBF$buffer" : mb_convert_encoding($buffer, XML_CHARSET, 'UTF-8');

    return $buffer;
}

function transaction_shutdown_function() {
    $error = error_get_last();

    $is_commit = !isset($error['type']) || $error['type'] > E_PARSE;

    Utils::wpdb_stop($is_commit);
}
