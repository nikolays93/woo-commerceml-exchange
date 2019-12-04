<?php

namespace NikolayS93\Exchange\Model;

use NikolayS93\Exchange\ORM\Collection;
use NikolayS93\Exchange\Register;

/**
 * Works with posts, postmeta
 */
class ExchangeOffer extends ExchangePost {
	// use ExchangeItemMeta;

	// private $post;

	// function __construct( Array $post, $ext = '', $meta = array() )
	// {
	//     $args = wp_parse_args( $post, array(
	//         'post_status'       => apply_filters('ExchangePost__post_status', 'publish'),
	//         'comment_status'    => apply_filters('ExchangePost__comment_status', 'open'),
	//         /**
	//          * @todo watch this!
	//          */
	//         'post_type'         => 'offer',
	//     ) );

	//     if( $ext ) $args['post_mime_type'] = $ext;
	//     if( 0 !== strpos($args['post_mime_type'], 'XML') ) $args['post_mime_type'] = 'XML/' . $args['post_mime_type'];

	//     if( !$args['post_name'] ) {
	//         $args['post_name'] = strtolower($args['post_name']);
	//     }

	//     /**
	//      * @todo generate guid
	//      */

	//     $this->post = new WP_Post( (object) $args );
	//     $this->setMeta($meta);
	// }
	function fill_relative_post() {
		global $wpdb;

		$table = Register::get_exchange_table_name();
		$ext   = $this->get_external();

		$res = $wpdb->get_row( "
			SELECT * FROM $table
			WHERE `xml` = '$ext'
			LIMIT 1
		" );

		if( !empty($res) ) {
			if ( ! empty( $res->name ) ) {
				$this->set_title( $res->name );
			}

//			if ( ! empty( $res->desc ) ) {
//				$this->set_title( $res->name );
//			}

			$meta = unserialize( $res->meta_list );
			$this->set_meta( $meta );
			// reset status
			$this->set_quantity( $this->get_quantity() );
		}
	}

	function get_quantity() {
		$stock = $this->get_meta( 'stock' );
		// $quantity = $this->getMeta('quantity');

		// if( is_numeric($stock) && is_numeric($quantity) ) {
		//     $qty = max($stock, $quantity);
		// }
		// elseif( is_numeric($stock) ) {
		//     $qty = $stock;
		// }
		// elseif( is_numeric($quantity) ) {
		//     $qty = $quantity;
		// }

		return $stock;
	}

	function set_quantity( $qty ) {
		if ( null !== $qty ) {
			$qty = floatval( $qty );

			$this->set_meta( '_manage_stock', 'yes' );
			$this->set_meta( '_stock_status', $qty ? 'instock' : 'outofstock' );
			$this->set_meta( '_stock', $qty );
		}

		return $this;
	}

	/**
	 * Only one price coast for simple
	 *
	 * @param \CommerceMLParser\ORM\Collection $prices
	 *
	 * @return int
	 */
	public function get_current_price( $prices ) {
		$price = 0;
		if ( ! $prices->isEmpty() ) {
			$price = $prices->current()->getPrice();
		}

		return $price;
	}

	function get_price() {
		return $this->get_meta( 'price' );
	}

	function set_price( $price ) {
		$this->set_meta( '_price', $price );
		$this->set_meta( '_regular_price', $price );

		return $this;
	}

	function merge( $args, $product ) {
	}
}