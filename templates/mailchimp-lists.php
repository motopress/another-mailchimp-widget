<?php if ( is_array( $lists ) && ! empty( $lists ) ) {
	
	foreach ( $lists as $key => $list ) { ?>
		<option class="mpan-list-item" <?php selected( in_array( $list[ 'id' ], $current_mailing_list ), 1 ) ?> value="<?php echo $list[ 'id' ]; ?>"><?php _e( $list[ 'title' ], 'another-mailchimp-widget' ); ?></option>
		<?php if ( ! empty( $list[ 'categories' ] ) ) {
			
			if ( isset( $list[ 'categories' ] ) ) {
				
				foreach ( $list[ 'categories' ] as $category ):
					
					if ( ! empty( $category[ 'interests' ] ) ) {
						
						foreach ( $category[ 'interests' ] as $interest ):
							$interest_string = $list[ 'id' ] . '/' . $interest[ 'id' ]; ?>
							<option class="mpam-select-list-child" <?php selected( in_array( $interest_string, $current_mailing_list ), 1 ) ?> value="<?php echo $interest_string ?>"><?php echo $interest[ 'title' ] ?></option>
						<?php endforeach;
					}
				
				endforeach;
			}
		}
	}
	
}