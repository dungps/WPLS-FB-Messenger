<?php
/**
 * Plugin Name: Facebook Message
 * Description: Chat with your customer using Facebook Messenger
 * Author: Oryc
 * Author URI: http://dungps.xyz
 * Version: 1.0.0
 * Text Domain: fb-chat
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

$defines = array(
	'FB_CHAT_DIR' => trailingslashit( plugin_dir_path( __FILE__ ) ),
	'FB_CHAT_URL' => trailingslashit( plugin_dir_url( __FILE__ ) )
);

foreach( $defines as $k => $v ) {
	if ( !defined( $k ) ) {
		define( $k, $v );
	}
}

add_action( 'plugins_loaded', 'fb_chat_load_textdomain' );
function fb_chat_load_textdomain() {
	$locale = get_locale();
	$mo = 'fb-chat-' . $locale . '.mo';

	load_textdomain( 'fb-chat', WP_LANG_DIR . $mo );
	load_textdomain( 'fb-chat', FB_CHAT_DIR . 'languages/' . $mo );
	load_plugin_textdomain( 'fb-chat' );
}

add_action( 'admin_menu', 'fb_chat_admin_page' );
function fb_chat_admin_page() {
	add_menu_page(
		__( 'FB Chat', 'fb-chat' ),
		__( 'FB Chat', 'fb-chat' ),
		'manage_options',
		basename( __FILE__, '.php' ),
		'fb_chat_admin_page_layout'
	);
}

add_action( 'admin_init', 'fb_chat_admin_page_register_settings' );
function fb_chat_admin_page_register_settings() {
	add_settings_section( 'fb_chat_settings', null, false, 'fb_chat_settings' );

	add_settings_field( 'fb_chat_settings[username]', __( 'Facebook Username', 'fb-chat' ), 'fb_chat_text_field', 'fb_chat_settings', 'fb_chat_settings', array( 'id' => 'username', 'name' => 'fb_chat_settings[username]', 'placeholder' => __( 'Your Facebook Username', 'fb-chat' ), 'value' => fb_chat_get_option( 'username' ) ) );

	add_settings_field( 'fb_chat_settings[position]', __( 'Widget Position', 'fb-chat' ), 'fb_chat_select', 'fb_chat_settings', 'fb_chat_settings', array( 'id' => 'position', 'name' => 'fb_chat_settings[position]', 'selected' => fb_chat_get_option( 'position', 'bottom_right' ), 'option_all' => false, 'option_none' => false, 'options' => array( 'top_left' => __( 'Top Left', 'fb-chat' ), 'top_right' => __( 'Top Right', 'fb-chat' ), 'bottom_left' => __( 'Bottom Left', 'fb-chat' ), 'bottom_right' => __( 'Bottom Right', 'fb-chat' ) ) ) );

	add_settings_field( 'db_chat_settings[online_time]', __( 'Online Time', 'fb-chat' ), 'fb_chat_online_time_display', 'fb_chat_settings', 'fb_chat_settings' );

	register_setting( 'fb_chat_settings', 'fb_chat_settings' );
}

function fb_chat_admin_page_layout() {
	?>
	<form method="post" action="options.php">
		<?php settings_fields( 'fb_chat_settings' ) ?>
		<?php do_settings_sections( 'fb_chat_settings' ); ?>
		<?php submit_button() ?>
	</form>
	<?php
}

function fb_chat_get_option( $key, $default = null ) {
	$options = get_option( 'fb_chat_settings', array() );
	return isset( $options[ $key ] ) ? $options[ $key ] : $default;
}

function fb_chat_get_day_in_week() {
	return array(
		'mon' => __( 'Monday', 'fb-chat' ),
		'tue' => __( 'Tuesday', 'fb-chat' ),
		'wed' => __( 'Wednesday', 'fb-chat' ),
		'thu' => __( 'Thurday', 'fb-chat'), 
		'fri' => __( 'Friday', 'fb-chat' ), 
		'sat' => __( 'Saturday', 'fb-chat'),
		'sun' => __( 'Sunday', 'fb-chat' )
	);
}

function fb_chat_text_field( $args = array() ) {
	$defaults = array(
		'name'						=> null,
		'name' 						=> null,
		'value' 					=> null,
		'placeholder' 		=> null,
		'class' 					=> 'regular-text',
		'disabled' 				=> false,
		'data' 						=> array(),
		'autocomplete' 		=> '',
		'desc'						=> '',
	);

	$args = wp_parse_args( $args, $defaults );

	$args['name'] = !is_null( $args['name'] ) ? $args['name'] : $args['id'];

	$disabled = $args['disabled'] ? ' disabled="disabled" ' : '';

	$data = '';
	if ( $args['data'] ) {
		foreach( $data as $k => $v ) {
			$data .= sprintf( ' data-%s="%s" ', $k, $v );
		}
	}

	$html = '<input type="text" id="'. esc_attr( $args['id'] ) .'" name="'. esc_attr( $args['name'] ) .'" value="'. esc_attr( $args['value'] ) .'" autocomplete="'. esc_attr( $args['autocomplete'] ) .'" class="'. esc_attr( $args['class'] ) .'" placeholder="'. esc_attr( $args['placeholder'] ) .'" '. $data . $disabled .' />';

	if ( $args['desc'] ) {
		$html .= '<span class="description">' . wp_kses_post( $args['desc'] ) . '</span>';
	}

	echo $html;
}

function fb_chat_select( $args = array() ) {
	$defaults = array(
			'name'					=> null,
			'id'						=> null,
			'class'					=> 'select',
			'options'				=> array(),
			'option_none' 	=> __( 'None', 'dw-mails' ),
			'option_all'		=> __( 'All', 'dw-mails' ),
			'data'					=> array(),
			'selected'			=> null,
			'multiple'			=> false,
			'placeholder' 	=> null,
			'desc'					=> ''
		);

		$args = wp_parse_args( $args, $defaults );

		$args['name'] = !is_null( $args['name'] ) ? $args['name'] : $args['id'];

		$multiple = '';
		if ( $args['multiple'] ) {
			$multiple = ' multiple';
		}

		$html = '<select id="'. esc_attr( $args['id'] ) .'" name="'. esc_attr( $args['name'] ) .'" data-placeholder="'. esc_attr( $args['placeholder'] ) .'" class="'. esc_attr( $args['class'] ) .'" >';

		if ( $args['option_all'] ) {
			if ( $args['multiple'] ) {
				$selected = selected( true, in_array( 0, $args['selected'] ), false );
			} else {
				$selected = selected( 0, $args['selected'], false );
			}

			$html .= '<option value="0" '.$selected.'>'. esc_html( $args['option_all'] ) .'</option>';
		}

		if ( $args['option_none'] ) {
			if ( $args['multiple'] ) {
				$selected = selected( true, in_array( -1, $args['selected'] ), false );
			} else {
				$selected = selected( -1, $args['selected'], false );
			}
			$html .= '<option value="-1" '. $selected .'>'. esc_html( $args['option_none'] ) .'</option>';
		}

		if ( !empty( $args['options'] ) ) {

			foreach( $args['options'] as $k => $v ) {
				if ( $args['multiple'] ) {
					$selected = selected( true, in_array( $k, $args['selected'] ), false );
				} else {
					$selected = selected( $k, $args['selected'], false );
				}

				$html .= '<option value="'.esc_attr( $k ).'" '. $selected .' >'. esc_html( $v ) .'</option>';
			}
		}

		$html .= '</select>';

		if ( $args['desc'] ) {
			$html .= '<span class="description">' . wp_kses_post( $args['desc'] ) . '</span>';
		}

		echo $html;
}

function fb_chat_online_time_display() {
	$date_option = fb_chat_get_option( 'online_time', array() );
	?>
	<table>
		<?php foreach( fb_chat_get_day_in_week() as $date => $date_title ) : ?>
			<tr>
				<th><?php echo esc_html( $date_title ) ?></th>
				<td>
					<label for="<?php echo $date . '_from' ?>"><?php _e( 'From', 'fb-chat' ) ?></label><br>
					<?php
					fb_chat_text_field( array( 
						'id' => $date . '_from',
						'name' => 'fb_chat_settings[online_time][' . $date . '][from]',
						'value' => isset( $date_option[ $date ]['from'] ) ? $date_option[ $date ]['from'] : null,
						'placeholder' => __( '24:00', 'fb-chat' ),
						'class' => 'text-small timepicker'
					) );
					?>
				</td>
				<td>
					<label for="<?php echo $date . '_to' ?>"><?php _e( 'To', 'fb-chat' ) ?></label><br>
					<?php
					fb_chat_text_field( array( 
						'id' => $date . '_to',
						'name' => 'fb_chat_settings[online_time][' . $date . '][to]',
						'value' => isset( $date_option[ $date ]['to'] ) ? $date_option[ $date ]['to'] : null,
						'placeholder' => __( '24:00', 'fb-chat' ),
						'class' => 'text-small timepicker'
					) );
					?>			
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

add_action( 'admin_enqueue_scripts', 'fb_chat_admin_enqueue_script' );
function fb_chat_admin_enqueue_script() {
	wp_enqueue_style( 'jquery-timepicker', FB_CHAT_URL . 'assets/css/jquery.timepicker.min.css' );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-timepicker', FB_CHAT_URL . 'assets/js/jquery.timepicker.min.js', array( 'jquery' ), '1.0.0' );
	wp_enqueue_script( 'fb-chat', FB_CHAT_URL . 'assets/js/admin.js', array( 'jquery' ), '1.0.0', true );
}

add_action( 'wp_enqueue_scripts', 'fb_chat_enqueue_script' );
function fb_chat_enqueue_script() {
	wp_enqueue_style( 'fb-chat', FB_CHAT_URL . 'assets/css/style.css', array(), '1.0.0' );
}

add_action( 'wp_footer', 'fb_chat_footer' );
function fb_chat_footer() {
	$username = fb_chat_get_option( 'username', false );
	$position = fb_chat_get_option( 'position', 'bottom_right' );
	if ( $username && fb_chat_date_valid() ) :
	?>
	<div id="fb-chat" class="fb-chat fb-chat-<?php echo esc_attr( $position ) ?>">
		<a id="fb-chat-link" class="" target="_blank" href="http://m.me/<?php echo esc_attr( $username ) ?>">
			<img width="50" src="<?php echo esc_url( FB_CHAT_URL . 'assets/img/msg.png' ) ?>">
			<span class="fbtooltip"><?php _e( 'Message Us', 'fb-chat' ) ?></span>
		</a>
	</div>
	<script type="text/javascript">
		window.onload = function() {
			var fb_link = document.getElementById( 'fb-chat-link' );
			if ( fb_link ) {
				fb_link.addEventListener('click',function(e){
					e.preventDefault();
					var screenwidth = screen.width-450;
					var screenheight = screen.height-100;
					window.open(this.href, '_blank',"width=500,height="+screenheight+",left="+screenwidth);
				})
			}
		}
	</script>
	<?php endif;
}

function fb_chat_date_valid() {
	$day_options = fb_chat_get_option( 'online_time', array() );
	$date = array( 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' );
	$day = date( 'w', current_time( 'timestamp' ) );
	$current_day = $date[ $day ];
	if ( isset( $day_options[ $current_day ] ) ) {
		$from = isset( $date_option[ $current_day ]['from'] ) ? $date_option[ $current_day ]['from'] : '00:00';
		$to = isset( $date_option[ $current_day ]['to'] ) ? $date_option[ $current_day ]['to'] : '23:45';

		if ( current_time( 'timestamp' ) >= strtotime( $from ) && current_time( 'timestamp' ) <= strtotime( $to ) ) {
			return true;
		}
	}

	return false;
}