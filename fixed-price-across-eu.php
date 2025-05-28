<?php
/**
 * Plugin Name: Fixed Price Across EU
 * Plugin URI: https://github.com/shameemreza/fixed-price-across-eu
 * Description: Display the same final price including VAT for products across all EU countries by automatically adjusting the product net price.
 * Version: 1.0.0
 * Author: Shameem Reza
 * Author URI: https://shameem.dev
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fixed-price-across-eu
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

defined( 'ABSPATH' ) || exit;

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

/**
 * Fixed Price Across EU main class
 */
class Fixed_Price_Across_EU {

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WooCommerce price filters
        add_filter( 'woocommerce_product_get_price', array( $this, 'adjust_product_price' ), 99, 2 );
        add_filter( 'woocommerce_product_get_regular_price', array( $this, 'adjust_product_price' ), 99, 2 );
        add_filter( 'woocommerce_product_get_sale_price', array( $this, 'adjust_product_price' ), 99, 2 );
        
        // Handle variations
        add_filter( 'woocommerce_product_variation_get_price', array( $this, 'adjust_product_price' ), 99, 2 );
        add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'adjust_product_price' ), 99, 2 );
        add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'adjust_product_price' ), 99, 2 );
        
        // Prevent base tax rate adjustment
        add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false', 99 );
        
        // Add admin notice if WooCommerce settings are not compatible
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    /**
     * Adjust product price to maintain the same final price across all EU countries
     *
     * @param float $price Product price
     * @param object $product WC_Product object
     * @return float Adjusted price
     */
    public function adjust_product_price( $price, $product ) {
        // Only apply to taxable products
        if ( ! $product->is_taxable() ) {
            return $price;
        }

        // Don't adjust price in admin
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $price;
        }

        // Don't adjust if no customer is set yet (e.g., during initial store load)
        if ( ! WC()->customer ) {
            return $price;
        }

        // If we're in the base country, don't adjust the price
        $base_country = WC()->countries->get_base_country();
        $customer_country = WC()->customer->get_billing_country();
        
        if ( $customer_country === $base_country ) {
            return $price;
        }
        
        // Get the target final price (price including VAT in base country)
        $target_final_price = $this->get_target_final_price( $price, $product );
        
        // Get the customer's tax rate
        $customer_tax_rate = $this->get_customer_tax_rate( $product );
        
        if ( 0 === $customer_tax_rate ) {
            return $price;
        }
        
        // Calculate the required net price to achieve the target final price
        $adjusted_price = $this->calculate_net_price_from_gross( $target_final_price, $customer_tax_rate );
        
        return $adjusted_price;
    }

    /**
     * Get the target final price (price including VAT in base country)
     *
     * @param float $price Current price
     * @param object $product WC_Product object
     * @return float Target final price
     */
    private function get_target_final_price( $price, $product ) {
        // Get base country tax rate
        $base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class() );
        $base_tax_rate = 0;
        
        if ( ! empty( $base_tax_rates ) ) {
            foreach ( $base_tax_rates as $rate ) {
                $base_tax_rate += floatval( $rate['rate'] );
            }
        }
        
        // Calculate final price in base country
        if ( wc_prices_include_tax() ) {
            // If prices include tax, this is already the final price in the base country
            $final_price = $price;
        } else {
            // If prices exclude tax, calculate the final price in the base country
            $tax_amount = $price * ( $base_tax_rate / 100 );
            $final_price = $price + $tax_amount;
        }
        
        return $final_price;
    }

    /**
     * Get the customer's tax rate for the product
     *
     * @param object $product WC_Product object
     * @return float Tax rate percentage
     */
    private function get_customer_tax_rate( $product ) {
        $tax_rates = WC_Tax::get_rates( $product->get_tax_class() );
        $tax_rate = 0;
        
        if ( ! empty( $tax_rates ) ) {
            foreach ( $tax_rates as $rate ) {
                $tax_rate += floatval( $rate['rate'] );
            }
        }
        
        return $tax_rate;
    }

    /**
     * Calculate net price from gross price and tax rate
     *
     * @param float $gross_price Gross price (including tax)
     * @param float $tax_rate Tax rate percentage
     * @return float Net price (excluding tax)
     */
    private function calculate_net_price_from_gross( $gross_price, $tax_rate ) {
        return $gross_price / ( 1 + ( $tax_rate / 100 ) );
    }

    /**
     * Display admin notices if WooCommerce settings are not compatible
     */
    public function admin_notices() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Check if tax calculation is enabled
        if ( ! wc_tax_enabled() ) {
            ?>
            <div class="notice notice-error">
                <p><?php _e( 'Fixed Price Across EU: WooCommerce taxes are not enabled. Please enable taxes in WooCommerce settings for this plugin to work.', 'fixed-price-across-eu' ); ?></p>
            </div>
            <?php
        }

        // Check price display settings
        $display_shop = get_option( 'woocommerce_tax_display_shop' );
        $display_cart = get_option( 'woocommerce_tax_display_cart' );
        
        if ( 'incl' !== $display_shop || 'incl' !== $display_cart ) {
            ?>
            <div class="notice notice-warning">
                <p><?php _e( 'Fixed Price Across EU: For best results, WooCommerce should be configured to display prices including tax. Please check your tax settings.', 'fixed-price-across-eu' ); ?></p>
            </div>
            <?php
        }
    }
}

// Initialize the plugin
add_action( 'plugins_loaded', function() {
    new Fixed_Price_Across_EU();
} );
