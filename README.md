## Fixed Price Across EU

It solves a common problem for WooCommerce stores selling across the European Union: customers in different countries seeing different prices due to varying VAT rates.

This mini plugin automatically adjusts the product's net price (without VAT) based on the customer's location, ensuring that the final price displayed to the customer (including VAT) is the same regardless of which EU country they are in.

## How It Works

1. When a customer visits your store, the plugin detects their country.
2. It calculates the appropriate net price needed to achieve the same final price as in your base country.
3. The adjusted price is used for display and cart calculations.
4. The customer sees the same final price regardless of their country's VAT rate.

## Requirements

* WooCommerce 6.0 or higher.
* Taxes must be enabled in WooCommerce.
* "Display prices in the shop" should be set to "Including tax".
* "Display prices during cart and checkout" should be set to "Including tax".

## Installation

1. Upload the `fixed-price-across-eu` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Make sure your WooCommerce tax settings are properly configured:
   - Taxes should be enabled.
   - "Display prices in the shop" set to "Including tax".
   - "Display prices during cart and checkout" set to "Including tax".
   - All relevant tax rates for EU countries should be set up.

## FAQs

### Does this plugin handle digital goods?

Yes, the plugin works for both physical and digital products.

### Does this work for variable products?

Yes, the plugin adjusts prices for simple, variable, and variation products.

### Will this affect my profit margins?

Yes, your net profit may vary slightly between countries due to the different VAT rates. The plugin ensures customers see the same final price, which means your net earnings will be lower in countries with higher VAT rates.
