<?php
/**
 * Plugin Name: WooCommerce Vapulus Gateway
 * Description: VAPULUS gateway is the easiest gateway to install, activate and start receiving payments. Login to your portal to get access to customers globally for your online, mobile app business.
 * Author: Vapulus
 * Author URI: https://www.vapulus.com/
 * Version: 1.1
 * Requires at least: 4.4
 * Tested up to: 5.1
 * WC requires at least: 2.6
 * WC tested up to: 4.7
 * Text Domain: woocommerce-gateway-vapulus
 * Domain Path: /languages
 *
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// add notice to config plugin
add_action('admin_notices', 'vapulus_gateway_notice');
function vapulus_gateway_notice()
{
    //check if woocommerce installed and activated
    if (!class_exists('WooCommerce')) {
        global $pagenow;
        echo
            '<div class="error notice-warning text-bold">
                <p>
                    <img src="' . esc_url(plugins_url('assets/images/logo.svg', __FILE__)) . '" alt="Vapulus" style="width:180px;">
                </p> 
                <p>
                    <strong>
                        Vapulus Gateway requires WooCommerce to be installed and active.
                    </strong>
                </p>';
        // remove link if inside plgugin page
        if ($pagenow !== 'plugins.php') {
            echo '<p>
            Install and Activated plugin : <a class="button button-primary" href="' . admin_url('plugins.php') . '">Plugins</a>
            </p>';
        }
        echo '</div>';
        return;
    }

    // check if I'm in setting page do nothing
    if (strpos($_SERVER['REQUEST_URI'], 'page=wc-settings&tab=checkout&section=vapulus') !== false) {
        return;
    }

    // check if plugin configured do nothing
    $settings = get_option('woocommerce_vapulus_settings');
    if ($settings['website']) {
        return;
    }

    //show msg to config plugin
    echo
        '<div class="notice notice-warning">
            <p>
                <img src="' . esc_url(plugins_url('assets/images/logo.svg', __FILE__)) . '" alt="Vapulus" style="width:180px;">
            </p> 
            <p>
                Configure Vapulus Gateway to start reciveing your Payments
            </p>
            <p>
                Click Here : <a class="button button-primary" href="' . menu_page_url('woocommerce-gateway-vapulus', false) . '">Vapulus Gateway Settings</a>
            </p>
        </div>';
}

add_action('admin_menu', 'vapulus_gateway_setup_menu');
function vapulus_gateway_setup_menu()
{
    //check if woocommerce is activated
    if (!class_exists('WooCommerce')) {
        return;
    }

    add_menu_page(
        'Test Plugin Page',
        'Vapulus Gateway',
        'manage_options',
        'woocommerce-gateway-vapulus',
        'vapulus_business',
        esc_url(plugins_url('assets/images/vapulus.png', __FILE__))
    );
    // link to plugin settings
    add_submenu_page(
        'woocommerce-gateway-vapulus',
        'Plugin Setting',
        'Plugin Setting',
        'manage_options',
        'woocommerce-gateway-vapulus',
        'vapulus_business'
    );
    // link to vapulus portal - report page
    add_submenu_page(
        'woocommerce-gateway-vapulus',
        'Website Setting',
        'Website Setting',
        'manage_options',
        'woocommerce-gateway-vapulus-website',
        'woocommerce_gateway_vapulus_website'
    );
    // link to vapulus portal - report page
    add_submenu_page(
        'woocommerce-gateway-vapulus',
        'Transaction Report',
        'Transaction Report',
        'manage_options',
        'woocommerce-gateway-vapulus-report',
        'woocommerce_gateway_vapulus_report'
    );
}

function vapulus_business()
{
    // update vapulus website if send in param
    // sanitize_key
    $siteId = sanitize_key($_GET['siteId']);
    //validate sideId as a uuidV4
    $UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i ';
    if (preg_match($UUIDv4, $siteId)) {
        $settings = array(
            'enabled' => 'yes',
            'title' => 'Vapulus Gateway',
            'website' => $siteId
        );
        update_option('woocommerce_vapulus_settings', $settings);
    }

    //redirect to vapulus tab in woocommerce payment settings
    $redirect_url =  admin_url('admin.php?') . 'page=wc-settings&tab=checkout&section=vapulus';
    wp_redirect($redirect_url);
}

function woocommerce_gateway_vapulus_report()
{
    //redirect to vapulus report website
    $redirect_url =  'https://app.vapulus.com/business/dashboard/report';
    wp_redirect($redirect_url);
}

function woocommerce_gateway_vapulus_website()
{
    // check if plugin configured do nothing
    $settings = get_option('woocommerce_vapulus_settings');
    if ($settings['website']) {
        $redirect_url =  'https://app.vapulus.com/business/dashboard/sales-channels/websites/details/' . $settings['website'];
    } else {
        $redirect_url =  'https://app.vapulus.com/business/dashboard/sales-channels/websites';
    }
    wp_redirect($redirect_url);
}

add_filter('woocommerce_payment_gateways', 'add_vapulus_gateway_class');
function add_vapulus_gateway_class($methods)
{
    $methods[] = 'WC_Gateway_Vapulus';
    return $methods;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'plugin_action_links');
function plugin_action_links($links)
{
    $plugin_links = array(
        '<a href="' . menu_page_url('woocommerce-gateway-vapulus', false) . '">' . __('Settings') . '</a>'
    );
    return array_merge($plugin_links, $links);
}

add_filter('plugin_row_meta', 'woocommerce_payment_gateways_links', 10, 4);
function woocommerce_payment_gateways_links($links_array, $plugin_file_name, $plugin_data, $status)
{

    if (strpos($plugin_file_name, basename(__FILE__))) {
        $links_array[''] = '<a target="_blank" href="https://app.vapulus.com/Business/">' . __('Vapulus Business') . '</a>';
        $links_array[] =  '<a target="_blank" href="https://www.vapulus.com/">' . __('Support') . '</a>';
    }

    return $links_array;
}

add_action('plugins_loaded', 'init_vapulus_gateway_class');
function init_vapulus_gateway_class()
{

    //check if woocommerce is activated
    if (!class_exists('WooCommerce')) {
        return;
    }

    class WC_Gateway_Vapulus extends WC_Payment_Gateway
    {

        public $domain;
        public $order;
        /**
         * Constructor for the gateway.
         */
        public function __construct()
        {
            $this->domain = 'vapulus_gateway';

            $this->id                 = 'vapulus';
            $this->icon               = esc_url(plugins_url('assets/images/vapulus.jpg', __FILE__));
            $this->has_fields         = false;
            $this->method_title       = __('Vapulus Gateway', $this->domain);
            $this->method_description = __('VAPULUS gateway is the easiest gateway to install, activate and start receiving payments. Login to your portal to get access to customers globally for your online, mobile app business.', $this->domain);

            // Load the settings.
            $this->init_settings();
            foreach ($this->settings as $setting_key => $value) {
                $this->$setting_key = $value;
            }

            // Define user set variables
            $this->title        = $this->get_option('title');
            $this->description  = $this->get_option('description');
            $this->website  = $this->get_option('website');

            $this->init_form_fields();

            //Actions woocommerce_receipt_	
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields()
        {
            // for administration panel
            $this->form_fields = array();
            $this->form_fields['guide'] = $this->get_guides();
            $this->form_fields['enabled'] = array(
                'title'   => __('Enable/Disable', $this->domain),
                'type'    => 'checkbox',
                'label'   => __('Enable Vapulus Gateway', $this->domain),
                'default' => 'yes'
            );
            $this->form_fields['title'] = array(
                'title'       => __('Title', $this->domain),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', $this->domain),
                'default'     => __('Vapulus Gateway', $this->domain),
                'desc_tip'    => true,
            );
            $this->form_fields['website'] = array(
                'title'       => __('Website ID', $this->domain),
                'type'        => 'text',
                'description' => __('Website id from your merchant portal.', $this->domain),
                'default'     => __('', $this->domain),
                'desc_tip'    => true,
            );
            $this->form_fields['production'] = $this->get_website_mode();
        }

        /**
         * Get guide description from user
         */
        private function get_guides()
        {
            $text = '';
            if ($this->website) {
                $text =  __(
                    '
                    <div id="message" class="updated inline">
                        <p>
                            <img src="' . esc_url(plugins_url('assets/images/logo.svg', __FILE__)) . '" alt="Vapulus" style="width:180px;">
                        </p> 
                        <p>
                            <strong>Click here to manage your vapulus account, get access to more services, reach more clients, and look at your transactions reporting portal.
                            </strong>
                        </p> 
                        <p>
                           <a class="button button-primary" target="_blank" href="https://app.vapulus.com/business">Vapulus.com</a>
                        </p>
                    </div>
            ',
                    $this->domain
                );
            } else {
                $text = __(
                    '
                    <div id="message" class="updated inline">
                        <p>
                            <img src="' . esc_url(plugins_url('assets/images/logo.svg', __FILE__)) . '" alt="Vapulus" style="width:180px;">
                        </p> 
                        <p>
                            <strong>After you sign up your Vapulus Account , A website ID will be added in Vapulus Gateway setting.</strong>
                        </p>
                        <p>
                            <a class="button button-primary" href="' . $this->get_vapulus_url($this->website) . '">Account Log in/Sign up</a>
                        </p>
                    </div>
            ',
                    $this->domain
                );
            }

            return array(
                'title'       => __('Guide', $this->domain),
                'type'        => 'title',
                'description' => $text,
                'default'     => __('', $this->domain),
            );
        }

        /**
         * Get guide website mode [ test/production ] from user
         */
        private function get_website_mode()
        {
            if (!$this->website)
                return array(
                    'title'       => __('Production', $this->domain),
                    'type'        => 'title',
                    'description' =>  __('Please Add Website ID first to Get Production Mode.', $this->domain)
                );

            $this->mode = false;

            $response = wp_remote_post(
                'https://api.vapulus.com/merchant/website/info',
                array(
                    'method' => 'POST',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' => array(
                        'uHeader' => 'wordpress-plugin',
                        'fingerPrint' => $_SERVER['REQUEST_TIME'],
                        'siteId' => $this->website,
                        'link' => home_url($wp->request)
                    ),
                    'cookies' => array()
                )
            );
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                echo "Something went wrong: $error_message";
            } else {
                $data = json_decode($response['body']);
                if ($data->statusCode == 200) {
                    $this->mode = $data->data->production;
                }
            }

            if ($this->website) {
                $link =  'https://app.vapulus.com/business/dashboard/sales-channels/websites/details/' . $this->website;
            } else {
                $link =  'https://app.vapulus.com/business/dashboard/sales-channels/websites';
            }

            $text = __(
                '
                <div id="message" class="updated inline">
                    <p>
                        Current Gateway Mode is:<strong> ' . ($this->mode ? 'Production' : 'Test') . ' </strong>.
                    </p>
                    <p>
                        <a class="button button-primary" target="_blank" href="' . $link . '">Change Website Mode</a>
                    </p>
                </div>
        ',
                $this->domain
            );

            return array(
                'title'       => __('Production', $this->domain),
                'type'        => 'title',
                'description' => $text
            );
        }

        /**
         * Get guide url for user
         */
        private function get_vapulus_url()
        {
            global $wp;
            if (isset($this->website)) {
                $needSiteId = true;
            } else {
                $needSiteId = false;
            }

            return 'http://app.vapulus.com/wordpress/?parent=' . home_url($wp->request) .
                '&redirect=' . admin_url('admin.php?page=woocommerce-gateway-vapulus') .
                '&needSiteId=' . ($needSiteId ? 'true' : 'false');
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);

            $order->reduce_order_stock();

            // WC()->cart->empty_cart();
            $link = $this->get_return_url($order);

            $urlargs = [
                'siteId' => $this->website,
                'amount' => $order->get_total(),
                'pageTitle' => urlencode('Vapulus Gateway'),
                'onaccept' => urlencode($order->get_checkout_order_received_url()),
                'onfail' => urlencode($order->get_checkout_payment_url()),
                'link' => $link
            ];
            $str = '';
            foreach ($urlargs as $key => $value) {
                $str .= '&' . $key . '=' . $value;
            }
            $url = "https://app.vapulus.com/website/?";
            $url .= substr($str, 1);

            return array(
                'result'   => 'success',
                'redirect' =>  $url
            );
        }
    }
}

add_action('woocommerce_thankyou', 'vapulus_complete_order');
function vapulus_complete_order($order_id)
{
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    $order->update_status('completed');
}
