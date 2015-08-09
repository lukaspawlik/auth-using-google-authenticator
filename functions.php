<?php

/**
 * Class Xlt_TOTP_Auth_Functions handles core code methods.
 */
class Xlt_TOTP_Auth_Functions {

	/**
	 * Register setting.
	 *
	 * @return void
	 */
	public function register_my_setting() {
		register_setting( 'xlttotpauth', 'xlttotpauth_enabled' );
	}

	/**
	 * Add options link.
	 *
	 * @param array $links Current links.
	 *
	 * @return array Modified list of links.
	 */
	public function filter_plugin_actions( array $links ) {
		$settings_link = '<a href="options-general.php?page=xlttotpauth">Settings</a>';
		array_unshift( $links, $settings_link ); // before other links
		return $links;
	}

	/**
	 * Add or update option during plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		update_option( 'xlttotpauth_enabled', 'true' );
	}

	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_options_page(
			'Google Token Authentication',
			'Google Token Authentication',
			'manage_options',
			'xlttotpauth',
			array( $this, 'main' )
		);
	}

	/**
	 * Main plugin entry.
	 *
	 * @return void
	 */
	public function main() {
		$act = ( isset( $_GET['act'] ) ? $_GET['act'] : null );
		switch ( $act ) {
			case null:
			default:
				$this->main_overview();
				break;
			case 'generatenew':
				$this->generate_new_token();
				break;
		}
	}

	/**
	 * Add new input to login form.
	 *
	 * @return void
	 */
	public function login_form() {
		print '<p>
		<label for="user_token">Google Authenticator token<br/>
			<input type="text" name="token" id="token" class="input" value=""
			       size="20" autocomplete="off"/></label>
		</p>';
	}

	/**
	 * Intercept login process.
	 *
	 * @param WP_User|null $user User object.
	 *
	 * @return WP_Error|WP_User
	 */
	public function auth( $user ) {
		$enabled = (bool) get_option( 'xlttotpauth_enabled' );
		if (
			is_a( $user, 'WP_Error' ) ||
			! is_a( $user, 'WP_User' ) ||
			! $enabled
		) {
			return $user;
		}

		$auth_class = new Xlt_TOTP_Auth_Class();

		return $auth_class->authorize( $user, $_POST['token'] );
	}

	/**
	 * Add settings to profile page.
	 *
	 * @param WP_User $user User.
	 *
	 * @return void
	 */
	public function profile( WP_User $user ) {
		$enabled = get_user_meta( $user->data->ID, 'xlttotpauth_enabled' );
		if ( isset( $enabled[0] ) ) {
			$enabled = (bool) $enabled[0];
		}
		$token = get_user_meta( $user->data->ID, 'xlttotpauth_seckey' );
		if ( isset( $token[0] ) ) {
			$token = $token[0];
		} else {
			$token = '';
		}
		echo "<h3>Google Authenticator</h3>";
		echo "<table class=\"form-table\"><tbody>";
		echo "<tr><th><label for=\"token_auth\">Token authentication enabled</label></th>";
		echo "<td><input type=\"checkbox\" name=\"token_auth\" id=\"token_auth\" value=\"true\" " . ( $enabled ? "checked" : "" ) . "></td>";
		echo "</tr>";
		$hide = "";
		if ( ! $enabled ) {
			$hide = " style=\"display: none;\"";
		}
		echo "<tr{$hide} class=\"secrow\"><th><label for=\"token_auth_code\">Secret code
				</label></th>";
		echo "<td><input type=\"text\" name=\"token_auth_code\" id=\"token_auth_code\" value=\"{$token}\" class=\"regular-text\" maxlength=\"10\">
<button class=\"button button-primary\" id=\"newtoken\">Generate new</button><span id=\"waito\" style=\"display: none;\">Please wait...</span>
</td>";
		echo "</tr>";
		if ( $enabled && ( 10 === strlen( $token ) ) ) {
			echo "<tr class=\"secrow\"><th><label for=\"manual_token_auth_code\">
			Secret code for Google Authenticator</label></th>";
			echo "<td>" . Base32::encode( $token ) . "</td>";
			echo "</tr>";
			echo "<tr class=\"secrow\"><th>QRCode</th>";
			$login  = sprintf( "%s %s",
				get_option( 'blogname' ),
				$user->data->user_login );
			$secret = Base32::encode( $token );
			echo "<td><img src=\"https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=otpauth://totp/{$login}?secret={$secret}&choe=UTF-8\" /></td>";
			echo "</tr>";
		}
		echo "</tbody></table>";
	}

	/**
	 * Update user profile.
	 *
	 * @param $uid
	 */
	public function user_update( $uid ) {
		$token   = isset( $_POST['token_auth_code'] )
			? $_POST['token_auth_code']
			: null;
		$checked = ( null !== $token ) && isset( $_POST['token_auth'] );
		update_user_meta( $uid, 'xlttotpauth_enabled', $checked );
		update_user_meta( $uid, 'xlttotpauth_seckey', $token );
	}

	/**
	 * Generate new token and return it as JSON response.
	 *
	 * @return void
	 */
	public function generate_new_token() {
		srand();
		$string = 'qwertyuiopasdfghjklzxcvbnm0987654321QWERTYUIOPASDFGHJKLZXCVBNM';
		$code   = '';
		for ( $i = 0; $i < 10; $i ++ ) {
			$code .= substr( str_shuffle( $string ), 0, 1 );
		}

		wp_send_json( array( 'res' => 0, 'code' => $code ) );
	}

	/**
	 * Register and enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_register_script(
			'totp-auth-profile',
			TOTP_AUTH_URL . '/js/profile.js',
			array( 'jquery' )
		);
		wp_enqueue_script( 'totp-auth-profile' );
	}

	/**
	 * Display main settings.
	 *
	 * @return void
	 */
	private function main_overview() {
		print "<div class='wrap'>";
		screen_icon();
		print "<h2>Google Token Authentication Settings</h2>";
		if (
			isset( $_GET['settings-updated'] ) &&
			'true' === $_GET['settings-updated']
		) {
			print "<h3>Changes has been saved.</h3>";
		}
		print "<form method=\"post\" action=\"options.php\"> ";
		@settings_fields( 'xlttotpauth' );
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Token authentication enabled</th>
				<td><input type="checkbox"
				           name="xlttotpauth_enabled" <?php echo ( get_option( 'xlttotpauth_enabled' ) ) ? "checked" : ""; ?> />
				</td>
			</tr>
		</table>
		<?php
		submit_button( "Save changes" );
		print "</form>";
		print "</div>";
	}
}