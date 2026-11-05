<?php
$current_mailing_list = AN_MC_Form::lists( isset( $current_mailing_list ) ? $current_mailing_list : array() );
if ( is_array( $lists ) && ! empty( $lists ) && ! isset( $lists['error'] ) ) {
	
	foreach ( $lists as $key => $list ) {
		if ( ! is_array( $list ) || ! isset( $list['id'], $list['title'] ) || ! is_string( $list['id'] ) || ! is_string( $list['title'] ) ) {
			continue;
		} ?>
		<option class="mpan-list-item" <?php selected( in_array( $list[ 'id' ], $current_mailing_list, true ), 1 ) ?> value="<?php echo esc_attr( $list[ 'id' ] ); ?>"><?php echo esc_html( $list[ 'title' ] ); ?></option>
		<?php if ( ! empty( $list[ 'categories' ] ) && is_array( $list['categories'] ) ) {
			
			if ( isset( $list[ 'categories' ] ) ) {
				
				foreach ( $list[ 'categories' ] as $category ):
					
					if ( is_array( $category ) && ! empty( $category[ 'interests' ] ) && is_array( $category['interests'] ) ) {
						
						foreach ( $category[ 'interests' ] as $interest ):
							if ( ! is_array( $interest ) || ! isset( $interest['id'], $interest['title'] ) || ! is_string( $interest['id'] ) || ! is_string( $interest['title'] ) ) { continue; }
							$interest_string = $list[ 'id' ] . '/' . $interest[ 'id' ]; ?>
							<option class="mpam-select-list-child" <?php selected( in_array( $interest_string, $current_mailing_list, true ), 1 ) ?> value="<?php echo esc_attr( $interest_string ); ?>"><?php echo esc_html( $interest[ 'title' ] ); ?></option>
						<?php endforeach;
					}
				
				endforeach;
			}
		}
	}
	
}