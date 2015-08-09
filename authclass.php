<?php

/**
 * Class Xlt_TOTP_Auth_Class handles token generation methods.
 */
class Xlt_TOTP_Auth_Class {

	/**
	 * Check user token.
	 *
	 * @param WP_User $user  User.
	 * @param string  $token Token.
	 *
	 * @return WP_Error|WP_User
	 */
	public function authorize( WP_User $user, $token ) {
		$token   = trim( $token );
		$uid     = $user->data->ID;
		$key     = get_user_meta( $uid, 'xlttotpauth_seckey' );
		$enabled = get_user_meta( $user->data->ID, 'xlttotpauth_enabled' );
		$enabled = (bool) $enabled[0];
		if ( ! $enabled ) {
			return $user;
		}
		if ( ! isset( $key[0] ) ) {
			$error = new WP_Error();
			$error->add( - 1, 'Login failed.' );

			return $error;
		}
		$generated_tokens = $this->get_token_by_time_range( $key[0] );
		if ( ! in_array( $token, $generated_tokens ) ) {
			$error = new WP_Error();
			$error->add( - 1, 'Login failed.' );

			return $error;
		}
		$last_used_token = get_user_meta(
			$uid,
			'xlttotpauth_lastseckey'
		);
		if (
			isset( $last_used_token[0] ) &&
			$last_used_token[0] === $token
		) {
			$error = new WP_Error();
			$error->add(
				-1,
				'You cannot use last token more than once. ' .
				'Wait for new token and try again.'
			);

			return $error;
		}
		update_user_meta(
			$uid,
			'xlttotpauth_lastseckey',
			$token
		);

		return $user;
	}

	/**
	 * Generate token.
	 *
	 * @param string $key     Key.
	 * @param int    $counter Token number.
	 *
	 * @return string
	 */
	private function get_token_by_counter( $key, $counter ) {
		$key = pack( "A*", $key );
		$cc  = array( 0, 0, 0, 0, 0, 0, 0, 0 );
		for ( $i = 7; $i >= 0; $i -- ) {
			$cc[ $i ] = pack( "C*", $counter );
			$counter  = $counter >> 8;
		}
		$binc        = implode( $cc );
		$binc        = str_pad( $binc, 8, chr( 0 ), STR_PAD_RIGHT );
		$hex         = hash_hmac( 'sha1', $binc, $key );
		$hmac_result = array();
		foreach ( str_split( $hex, 2 ) as $vv ) {
			$hmac_result[] = hexdec( $vv );
		}

		$offset = $hmac_result[19] & 0xf;

		$v = (
			     ( ( $hmac_result[ $offset + 0 ] & 0x7f ) << 24 ) |
			     ( ( $hmac_result[ $offset + 1 ] & 0xff ) << 16 ) |
			     ( ( $hmac_result[ $offset + 2 ] & 0xff ) << 8 ) |
			     ( $hmac_result[ $offset + 3 ] & 0xff )
		     ) % pow( 10, 6 );

		return str_pad( $v, 6, '0', STR_PAD_LEFT );
	}

	/**
	 * Get token based on time.
	 *
	 * @param string $key Key.
	 *
	 * @return string
	 */
	private function get_token_by_time( $key ) {
		$counter = floor( time() / 30 );

		return $this->get_token_by_counter( $key, $counter );
	}

	/**
	 * Generate pool of tokens. For better UX check current token, previous and
	 * future one.
	 *
	 * @param string $key Key.
	 *
	 * @return array
	 */
	private function get_token_by_time_range( $key ) {
		$counter = floor( time() / 30 );
		$pool    = array();
		$pool[]  = $this->get_token_by_counter( $key, $counter );
		$pool[]  = $this->get_token_by_counter( $key, $counter + 1 );
		if ( $counter > 1 ) {
			$pool[] = $this->get_token_by_counter( $key, $counter - 1 );
		}

		return $pool;
	}
}