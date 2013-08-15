<?php
/*
 * Plugin Name: EDD Recount Earnings
 * Description: Allows you to recalculate the earnings of products in EDD. Useful if product earnings get off somehow
 * Author: Pippin Williamson
 * Version: 0.1
 */

class EDD_Recount_Earnings {

	public function __construct() {
		add_action( 'edd_stats_meta_box', array( $this, 'stats_box_link' ) );
		add_action( 'edd_recount_earnings', array( $this, 'recount' ) );
	}

	public function stats_box_link() {
		global $post;

		$args = array(
			'edd_action' => 'recount_earnings',
			'action'     => 'edit',
			'post'       => $post->ID
		);

		$base_url = admin_url( 'post.php' );

		echo '<tr>';
			echo '<td colspan="2">';
				echo '<a href="' . add_query_arg( $args, $base_url ) . '">' . __( 'Recount Earnings', 'edd-recount-earnings' ) . '</a>';
			echo '</td>';
		echo '</tr>';
	}

	public function recount() {

		global $edd_logs, $wpdb;

		if( empty( $_GET['post'] ) )
			return;

		if( ! current_user_can( 'edit_products' ) )
			wp_die( 'Cheating' );

		$download_id = absint( $_GET['post'] );

		if( ! get_post( $download_id ) )
			return;

		$args = array(
			'post_parent' => $download_id,
			'log_type'    => 'sale',
			'nopaging'    => true,
			'fields'      => 'ids',
		);

		$log_ids  = $edd_logs->get_connected_logs( $args, 'sale' );
		$earnings = 0;

		if( $log_ids ) {
			$log_ids     = implode( ',', $log_ids );
			$payment_ids = $wpdb->get_col( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_edd_log_payment_id' AND post_id IN ($log_ids);" );

			foreach( $payment_ids as $payment_id ) {
				$items = edd_get_payment_meta_cart_details( $payment_id );
				foreach( $items as $item ) {
					if( $item['id'] != $download_id )
						continue;

					$earnings += $item['price'];
				}
			}
		}

		if( ! empty( $earnings ) ) {
			update_post_meta( $download_id, '_edd_download_earnings', $earnings );
		}

		$args = array(
			'action'     => 'edit',
			'post'       => $download_id
		);

		$base_url = admin_url( 'post.php' );

		wp_redirect( add_query_arg( $args, $base_url ) ); exit;

	}

}
new EDD_Recount_Earnings;