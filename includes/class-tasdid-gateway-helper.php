<?php
/**
 *
 * A list of helpers methods for tasdid gateway
 *
 * @package    Tasdid_Gateway
 * @subpackage Tasdid_Gateway/includes
 * @author     Mahmood Abbas <contact@mahmoodshakir.com>
 */
defined('ABSPATH') || exit;

class Tasdid_Gateway_Helper
{
    /**
     * Request helper method to make http requests
     *
     * @param string $url
     * @param string $method
     * @param array $body
     * @param array $headers
     * @return array|WP_Error
     * @since 1.0.0
     */
    static function request(string $url, string $method, array $body, array $headers = [])
    {
        $headers = array_merge(array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
        ), $headers);

        $args = array(
            'method' => $method,
            'headers' => $headers,
        );

        if ($method !== 'GET') $args['body'] = json_encode($body);
        // login
        return wp_remote_request($url, $args);
    }

    /**
     * Login request helper to get access token
     * @param string $username
     * @param string $password
     * @return array
     * @since 1.0.0
     */
    static function login(string $username, string $password)
    {
        $base_url = $payment_gateway->settings->is_test === "yes" ? TESTING_TASDID_API_DOMAIN : TASDID_API_DOMAIN;
        $url = $base_url . '/auth/token';
        $data = array(
            'username' => $username,
            'password' => $password
        );
        $request = Tasdid_Gateway_Helper::request($url, "POST", $data);
        $request_body = json_decode(wp_remote_retrieve_body($request), true);
        $statusCode = wp_remote_retrieve_response_code($request);
        if ($statusCode === 401) return "";
        return $request_body["token"];
    }

    /**
     * Get payload from JWT token
     * @param string $token
     * @return array
     * @since 1.0.0
     */
    static function jwt_decode(string $token): array
    {
        return json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $token)[1]))), true);
    }

    /**
     * check if token is expired
     * @param string $token
     * @return bool
     * @since 1.0.0
     */
    static function is_token_expired(string $token): bool
    {
        $payload = Tasdid_Gateway_Helper::jwt_decode($token);
        $date = new \DateTime();
        return $date->getTimestamp() < $payload['exp'];
    }

    /**
     * show error message in admin panel
     *
     * @param string $text
     * @return void
     * @since 1.0.0
     */
    static function show_error(string $text)
    {
        add_action('admin_notices', function () use ($text) {
            ?>
            <div class="error notice">
                <p><?php echo $text ?></p>
            </div>
            <?php
        });
    }

}