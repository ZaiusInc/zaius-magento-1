# Zaius Magento 1 Connector

Integrate Zaius directly into your Magento instance using the Zaius Magento 1 Connector.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

1. Magento 1 "^1.9.x"
2. PHP 5 >= 5.5.0, PHP 7
3. (OPTIONAL) Composer

## Installing

### Composer

By far the quickest and easiest way to install and maintain the Zaius Magento 1 Connector is to use Composer. Unfortunately, Magento 1 was built before Composer, and therefore does not support it out-of-the-box. However, it is easy to get composer and Magneto 1 working together. To install Composer, one will need access to the server command line.

```bash
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
```

To test the installation, run:

```bash
composer
```

Composer is now globally installed on your server.

To install the Zaius Magento 1 Connector with composer, you can place the file `composer.json.example` into your Magento 1 root directory (without the .example extension). 

If you already have a composer.json file, add the required libraries from `composer.json.example` into it.

Once these requirements are noted, installing the Zaius Magento 1 Connector is as easy as running a simple command from the root of your Magento instance:

```
composer install
```

This will download the necessary files for Composer to work with Magento 1, as well as the Zaius Magento 1 Connector and the Zaius PHP SDK.

### ZIP

1. Download the Zaius Magento 1 module archive from Git: https://github.com/ZaiusInc/zaius-magento-1/archive/master.zip
2. Extract the contents of the ZIP file to <MAGENTO_ROOT>/<extract_here>.

In a correct directory structure, you will find the Zaius Magento 1 Connector files in <MAGENTO_ROOT>/app/code/community/Zaius

#### Setting Up The Connector

##### Part 1: Setting Up A SOAP Role & User
[imTLBMb8R8CmhcwZTeig_Screen%2520Shot%25202016-04-07%2520at%25208.53.34%2520AM.png](https://downloads.intercomcdn.com/i/o/42414843/46602f8e2b5a45b37dcba9db/imTLBMb8R8CmhcwZTeig_Screen%2520Shot%25202016-04-07%2520at%25208.53.34%2520AM.png)

In order to use Magento with Zaius, a user with SOAP API permissions must be created.
1. From the **System** drop down menu, navigate to the **Web Services -> SOAP/XML-RPC Roles** child sub-menu.
2. Click **Add New Role** in the upper right.
    a. Enter a name for the Role, such as `zaius_engage`.
    b. Click **Role Resources** in the left-hand menu.
    c. Tick the box next to `Zaius Engage`.
    *this will enable all Zaius Engage API methods for this SOAP user.*
    d. Navigate back to **Role Info** in the left-hand menu, and enter the **Current Admin Password** in the box provided.
    e. Click **Save Role** in the upper right.
3. From the **System** drop down menu, navigate to the **Web Services -> SOAP/XML-RPC Users** child sub-menu.
4. Click **Add New User** in the upper right.
    a. Fill in the appropriate required fields.
    **User Name:** *create a simple user name; e.g. `zaius_engage`.*
    **API KEY:** *create a 'password' for the user name to authenticate with; e.g. `password123`.*
    b. Click **User Role** in the left-hand menu.
    c. Select the appropriate role for the new SOAP user. In our example it is `zaius_engage`.
    d. Click **Save User** in the upper right.

The username and API key specified will be provided to Zaius in order to enable the integration.

##### Part 2: Integrating with Zaius

Enter the username, API key and the URL endpoint on your server into the boxes found in the integrations screen in the Magento box. The API URL is typically of the form: "http://your-magento-server/api/v2_soap?wsdl=1"

[9J9L6X4YR6WrU3Wkd4wo_Screen%2520Shot%25202016-04-12%2520at%252011.55.19%2520AM.png](https://downloads.intercomcdn.com/i/o/42414845/34c24ef17897bdd440d66a7a/9J9L6X4YR6WrU3Wkd4wo_Screen%2520Shot%25202016-04-12%2520at%252011.55.19%2520AM.png)

##### Part 3: Configuring Zaius Engage

**_Zaius Engage_**

* Tracking Identifier: The Zaius Tracking Identifier to send data to.
* Cart Abandonment Secret Key : This is a secret value used to encode link information for cart abandonment campaigns. Once set, this should not be changed. This is a random string that you enter in and is not something Zaius provides.
* Global ID Prefix
* Zaius Newsletter List ID
* Use Magento Customer ID
* Track Product Listing Views
* Track Orders On Frontend
* Collect All Product Attributes
* Reported Currency

**_Zaius Schema Update_**
* Update Schema:  Update the Magento Database Schema of three tables (`catalog_product_entity_media_gallery`, `newsletter_subscriber`, `salesrule_coupon`) to allow the processing of records that may not get picked up by Magento observers.

**_Zaius Localizations_**
* Localizations:  Enabling localizations will provide localized store_view data for events sent to Zaius.

Many Magento sites support multiple language/currency code combinations, and create each one as a separate store_view. Zaius has added support for this workflow.

The Zaius Localizations feature in the Zaius Engage module adds support for such sites. The feature automatically detects those store_views configured as localizations and sends additional, localized versions of products. These products provide all information necessary to generate dynamic product grids in your customers' preferred languages, even when the products shown are not necessarily products they have explicitly interacted with.

At this point in time this feature is in a beta phase, which means that the Zaius support team must make certain updates on the Zaius platform to fully utilize this feature. Please consult with your CSM to get those changes made prior to initializing this feature. 

* Navigate to the Zaius Engage configuration page.
* In the Zaius Localizations panel, switch the Enable/Disable dropdown to "Enable".

**_Zaius Batch Updating_**
* Recurring Batch Updating: With Batch Updating enabled, missed events are sent to Zaius on a scheduled cron interval.

Many Magento sites have a variety of backend processes which run direct SQL inserts and updates, especially ERPs. When the Magento database is modified via direct SQL, Magento's Observers don't know that there are updates to inform other technologies (like Zaius) about. This can cause those technologies to desynchronize from the Magento dataset.

To account for this, Zaius has built a Batch Update module which lives in Magento. This feature causes Magento to regularly look through the database to find those products, customers, and orders which have been updated since the last time it scanned through. 

* Navigate to the Zaius Engage configuration page.
* In the Zaius Schema Update panel, click "Run It!" to make necessary modifications so that the batch process can know which entries need to be synced. This adds two columns to the database tables which Zaius syncs, zaius_created_at and zaius_updated_at.
* In the Zaius Batch Updating panel, change the Crontab line to indicate how frequently you would like the updates to be run. More frequent updates mean that each update takes less time to process, but each time we're scheduled to update a small query will be run against the database to determine if there is anything which needs to be synced. Zaius recommends either a high frequency update (eg "*/5 * * * *" meaning every 5 minutes) or a daily batch at a low-traffic time (eg "0 1 * * *" meaning at 1 AM as indicated by your Magento server's clock).
* Make sure to set the "Enable/Disable" option to "Enable".

**_Zaius Double Opt-in_**
* Zaius Engage has a new option to support double opt-in workflows. To enable this, make sure to set the "Submit Event For Status Not Active" option in the Zaius Engage Configuration.
* With the "Submit Event For Status Not Active" option enabled, new users with subscription status 2 ("Not Active") will trigger a "newsletter" event with action "email_submitted"
* Users can configure an Event Triggered campaign to allow the user to opt-in, and use engagement with that campaign as a filter for their regularly-occurring sends. Your Zaius CSM can help set this up.

##### Part 4: Run the Bulk Import in Zaius

Zaius supports the bulk import of customers and product data via [CSV files](https://developers.zaius.com/v2.0/docs/csv-1).

CSV's can be uploaded directly from the app. To upload a CSV, navigate to Cog (Administration) -> (Data Management) -> Integrations -> **Upload CSV**.

[File Naming](https://developers.zaius.com/v2.0/docs/csv-1#section-file-naming)
[File Format](https://developers.zaius.com/v2.0/docs/csv-1#section-file-format)
[Fields and Data Types](https://developers.zaius.com/v2.0/docs/csv-1#section-fields-and-data-types)
[Orders](https://developers.zaius.com/v2.0/docs/csv-1#section-orders)

##### Part 5: Using the Event Inspector

Once events are being sent to Zaius' APIs, you can confirm Zaius is receiving your data and see what data Zaius is receiving. This is done via the Event Inspector in the Zaius UI.

To access the Event Inspector, navigate to Cog (Administration) -> Event Inspector.

1. Click "Start Inspector". Your API calls will now be recorded and made visible in the UI by clicking "Refresh".

## Client Testing Routine

In order to test the connector, clients are encouraged to try the following after the steps above are finished and the connector is set up.

Frontend testing:
---
* Create (or login to) a customer account in your Magento store.
* In your account settings, update your address.
* In your account settings, subscribe (or unsubscribe) to a newsletter.
* While still logged in, navigate to a product in your store, and add it to the cart.
* Add as many products as you'd like to the cart.
* Add a product to your wishlist.
* Navigate to your cart. If you have multiple items in your cart, remove one item.
* Make a test purchase of the item(s) in your cart.

Backend testing:
---
* Create a new product.
* Update an existing product.
* Create an order.
* Refund an order.
* Cancel an order.

**Every listed event above will trigger an update to Zaius**

## Zaius Engage Events

### Global Events

|Event   |Method   |Trigger   |Triggers   |Notes   |
|---|---|---|---|---|
|cataloginventory_stock_item_save_after   |Zaius_Engage_Model_observer_Stock_Item::saveAfter   |Catalog -> Manage Products -> (Edit Product) -> Inventory -> Qty -> **Save Product**   |   |   |
|sales_order_place_after   |Zaius_Engage_Model_Observer_Order::orderPlaced   |Sales -> Orders -> **(Create New Order)** \|\| **(Select Order) -> Reorder**   |   |   |
|sales_order_save_after   |Zaius_Engage_Model_Observer_Order::orderSaved   |Sales -> Orders -> **(Select Order)**  |   |This event triggers _only_ if an Order is under the following states, but not _originally_ of the following states:   ```STATE_NEW, STATE_PROCESSING, STATE_COMPLETE```|
|sales_order_payment_cancel   |Zaius_Engage_Model_Observer_Order::cancel   |Sales -> Orders -> **(Select Order)** -> Cancel    | _cataloginventory_stock_item_save_after_ for _n_ items.<br> _sales_order_save_after_   |   |
|sales_order_payment_refund   |Zaius_Engage_Model_Observer_Order::refund   |Sales -> Orders -> **(Select _Cancelled_ Order)**   |_cataloginventory_stock_item_save_after_ for _n_ items refunded.   |   |
|customer_save_after   |Zaius_Engage_Model_Observer_Customer::entity   |Customers -> Manage Customers -> **(Edit Customer)** -> **(Save Customer)**   |   |   |
|customer_address_save_after   |Zaius_Engage_Model_Observer_Customer::entityFromAddress   |Customers -> Manage Customers -> **(Edit Customer)** -> Addresses -> **(Save Customer)**   |_customer_save_after_   |   |

### Frontend Events

|Event   |Method   |Trigger    |Triggers   |Notes   |
|---|---|---|---|---|
|checkout_cart_add_product_complete   |Zaius_Engage_Model_Observer_Product::addToCart   |Add product to cart.   |   |
|sales_quote_remove_item   |Zaius_Engage_Model_Observer_Product::removeFromCart   |Remove product from cart.   |   |
|wishlist_add_product   |Zaius_Engage_Model_Observer_Product::wishlist   |Add product to wishlist.   |   |
|customer_register_success   |Zaius_Engage_Model_Observer_Customer::register   |Register a customer account.   |   |
|customer_login   |Zaius_Engage_Model_Observer_Customer::login   |Login with a valid customer account.   |   |
|customer_logout   |Zaius_Engage_Model_Observer_Customer::logout   |Logout from a valid customer account.   |   |
|newsletter_subscriber_save_after   |Zaius_Engage_Model_Observer_Newsletter::subscriptionChange   |Subscribe or unsubscribe from a newsletter.   |   |

### Adminhtml Events

|Event   |Method   |Trigger    |Triggers   |Notes   |
|---|---|---|---|---|
|catalog_product_save_after   |Zaius_Engage_Model_Observer_Product::entity   |Catalog -> Manage Products -> (Edit Product) -> **Save Product**   |cataloginventory_stock_item_save_after   |
|admin_system_config_changed_section_zaius_engage   |Zaius_Engage_Model_Observer::adminSystemConfigChangedSection   |System -> Configuration -> Zaius Engage -> (Modify _ANY_ Value) -> **Save Config**  |This method can be removed, as it was used in development for easy testing of batch updates.   |

## API Methods & Options

All API methods accept `limit` & `offset` options:

**Limit:** Allows you to limit the number of results in the returned payload.

**Offset:** Allows you to offset the results in the returned payload.
If we wanted to only return records over 16, set `"offset" => 15`.

```json
$jsonOpts   = array(
    "sessionId" => $sessionId,
    "jsonOpts"  => json_encode(array(
        "limit"                     => 5,
        "offset"                   => 0
    ))
);
```

|Method   |Options (type)   |Input   |Payload   |File   |Line   |
|---|---|---|---|---|---|
|[locales](#locales)   |   |   |   |\\\Zaius\Engage\Model\Api.php   |12   |
|[customers](#customers)   |   |   |   |\\\Zaius\Engage\Model\Api.php   |60   |
|[products](#products)   |   |   |   |\\\Zaius\Engage\Model\Api.php   |165   |
|[orders](#orders)   |   |   |   |\\\Zaius\Engage\Model\Api.php   |371   |
|[createCoupons](#createCoupons)   |   |   |   |\\\Zaius\Engage\Model\Api.php   |667   |
|[subscribers](#subscribers)   |   |   |   |\\\Zaius\Engage\Model\Api.php   |761   |
|[configuration](#configuration)   |   |   |   |\\\Zaius\Engage\Model\Api.php   |830   |

## locales

<details>
<summary>Request:</summary>
<p>

##### Method:

```php
zaiusEngageLocales();
```

##### Request:

**No Special Parameters**

</p>
</details>


<details>
<summary>Response:</summary>
<p>

```json
array(1) {
  ["locales"]=>
  array(2) {
    ["french"]=>
    array(5) {
      ["label"]=>
      string(15) "French (France)"
      ["code"]=>
      string(5) "fr_FR"
      ["currency_symbol"]=>
      string(3) "€"
      ["currency_code"]=>
      string(3) "EUR"
      ["base_url"]=>
      string(18) "http://zaius.mage/"
    }
    ["german"]=>
    array(5) {
      ["label"]=>
      string(16) "German (Germany)"
      ["code"]=>
      string(5) "de_DE"
      ["currency_symbol"]=>
      string(3) "€"
      ["currency_code"]=>
      string(3) "EUR"
      ["base_url"]=>
      string(18) "http://zaius.mage/"
    }
  }
}
```

</p>
</details>

## customers

<details>
<summary>Request:</summary>
<p>

##### Method:

```php
zaiusEngageCustomers();
```

##### Request:

```php
$jsonOpts   = array(
    "sessionId" => $sessionId,
    "jsonOpts"  => json_encode(array(
        "updated_at"                => "2018-01-01",
        "created_at"                => "2018-01-01",
        "id"                        => 1,
        "email"                     => "email@example.com",
        "limit"                     => 5,
        "offset"                    => 0
    ))
);
```

</p>
</details>

|Parameter	|Type	|Usage	|Required   |
|---|---|---|---|
|**updated_at** |string	|Use this parameter to filter your results by a specific updated date. |No  |
|**created_at** |string	|Use this parameter to filter your results by a specific created date. |No  |
|**id**	|string, int	|Use this parameter to filter customers by ID.	|No  |
|**email**	|string	|Use this parameter to filter customers by email.	|No  |

<details>
<summary>Response:</summary>
<p>

```json
array(1) {
  [0]=>
  array(2) {
    ["type"]=>
    string(8) "customer"
    ["data"]=>
    array(13) {
      ["email"]=>
      string(16) "jack@example.com"
      ["first_name"]=>
      string(4) "Jack"
      ["last_name"]=>
      string(4) "Fitz"
      ["subscribed"]=>
      bool(false)
      ["customer_id"]=>
      string(2) "24"
      ["street1"]=>
      string(13) "7 N Willow St"
      ["street2"]=>
      string(0) ""
      ["city"]=>
      string(9) "Montclair"
      ["state"]=>
      string(10) "New Jersey"
      ["zip"]=>
      string(5) "07042"
      ["country"]=>
      string(2) "US"
      ["phone"]=>
      string(12) "222-555-4190"
      ["zaius_engage_version"]=>
      array(1) {
        [0]=>
        string(6) "0.13.1"
      }
    }
  }
```

</p>
</details>

## products

<details>
<summary>Request:</summary>
<p>

##### Method:

```php
zaiusEngageProducts();
```

##### Request:

```php
$jsonOpts   = array(
    "sessionId" => $sessionId,
    "jsonOpts"  => json_encode(array(
        "updated_at"                => "2018-01-01",
        "created_at"                => "2018-01-01",
        "type_id"                   => "simple",
        "store_view_code"           => "french",
        "synthetic_upsert_default"  => true,
        "append_store_view_code"    => true,
        "product_id"                => 240,
        "sku"                       => "mtk000",
        "limit"                     => 5,
        "offset"                    => 0
    ))
);
```

</p>
</details>

|Parameter	|Type	|Usage	|Required   |
|---|---|---|---|
|**updated_at** |string	|Use this parameter to filter your results by a specific updated date. |No  |
|**created_at** |string	|Use this parameter to filter your results by a specific created date. |No  |
|**type_id**	|string	|Use this parameter to filter simple or configurable products.	|No  |
|**store_view_code**	|string	|Use this parameter to filter products by a valid store view.	|No  |
|**append_store_view_code**	|string, bool	|Use this parameter to append a `$LOCALE$` delimiter and the `store_view_code` to the product ID.	|No  |
|**synthetic_upsert_default**	|string, bool	|Use this parameter to provide a "synthetic upsert" for the default config to contain "productid_localestoreview".	|No*  |
|**product_id**	|string, int	|Use this parameter to filter products by ID.	|No  |
|**sku**	|string	|Use this parameter to filter products by sku.	|No  |

* `append_store_view_code` must be included and set to `true` if `synthetic_upsert_default` is included and set to `true`.

<details>
<summary>Response:</summary>
<p>

```json
array(1) {
  [0]=>
  array(2) {
    ["type"]=>
    string(7) "product"
    ["data"]=>
    array(12) {
      ["product_id"]=>
      string(3) "231"
      ["product_url"]=>
      string(56) "http://zaius.mage/index.php/catalog/product/view/id/231/"
      ["name"]=>
      string(31) "French Cuff Cotton Twill Oxford"
      ["sku"]=>
      string(6) "msj000"
      ["description"]=>
      string(126) "Made with wrinkle resistant cotton twill, this French-cuffed luxury dress shirt is perfect for Business Class frequent flyers."
      ["image_url"]=>
      string(52) "http://zaius.mage/media/catalog/product/no_selection"
      ["category"]=>
      string(31) "Default Category > Men > Shirts"
      ["price"]=>
      string(8) "190.0000"
      ["qty"]=>
      string(7) "15.0000"
      ["is_in_stock"]=>
      string(1) "1"
      ["parent_product_id"]=>
      string(3) "402"
      ["zaius_engage_version"]=>
      array(1) {
        [0]=>
        string(6) "0.13.1"
      }
    }
  }
```

</p>
</details>

## orders

<details>
<summary>Request:</summary>
<p>

##### Method:

```php
zaiusEngageOrders();
```

##### Request:

```php
$jsonOpts   = array(
	"sessionId" => $sessionId,
	"jsonOpts"  => json_encode(array(
		"updated_at"    => "2018-01-01",
		"created_at"    => "2018-01-01",
		"refund"        => true,
		"cancel"        => true,
		"id"            => 123,
		"entity_id"     => 123,
		"customer_id"   => 123,
		"email"         => "example@email.com"					
	))
);
```

</p>
</details>

|Parameter	|Type	|Usage	|Required   |
|---|---|---|---|
|**updated_at** |string	|Use this parameter to filter results by a specific updated date. |No  |
|**created_at** |string	|Use this parameter to filter results by a specific created date. |No  |
|**refund**	|bool	|Use this parameter to filter results to include only refunded orders.	|No  |
|**cancel**	|bool	|Use this parameter to filter results to include only cancelled orders.	|No  |
|**id**	|string, int	|Use this parameter to filter order results by increment ID. 	|No  |
|**entity_id**	|string, int	|Use this parameter to filter order results by `entity_id`	|No  |
|**customer_id**	|string, int	|Use this parameter to filter order results by `customer_id`.	|No  |
|**email**	|string	|Use this parameter to filter order results by customer email.	|No  |

<details>
<summary>Response:</summary>
<p>

```json
array(1) {
  [0]=>
  array(2) {
    ["type"]=>
    string(5) "order"
    ["data"]=>
    array(11) {
      ["action"]=>
      string(8) "purchase"
      ["ts"]=>
      int(1363287694)
      ["ip"]=>
      string(15) "216.113.168.131"
      ["ua"]=>
      string(0) ""
      ["order"]=>
      array(21) {
        ["order_id"]=>
        string(9) "100000049"
        ["total"]=>
        string(8) "823.4500"
        ["subtotal"]=>
        string(8) "750.0000"
        ["coupon_code"]=>
        NULL
        ["discount"]=>
        int(0)
        ["tax"]=>
        string(7) "61.8800"
        ["shipping"]=>
        string(7) "11.5700"
        ["currency"]=>
        string(3) "USD"
        ["native_total"]=>
        string(8) "823.4500"
        ["native_subtotal"]=>
        string(8) "750.0000"
        ["native_discount"]=>
        int(0)
        ["native_tax"]=>
        string(7) "61.8800"
        ["native_shipping"]=>
        string(7) "11.5700"
        ["native_currency"]=>
        NULL
        ["bill_address"]=>
        string(68) "10441 Jefferson Blvd., Suite 200, Culver City, California, 90232, US"
        ["email"]=>
        string(15) "mosses@ebay.com"
        ["phone"]=>
        string(10) "3105551212"
        ["first_name"]=>
        string(6) "Mosses"
        ["last_name"]=>
        string(7) "Akizian"
        ["ship_address"]=>
        string(68) "10441 Jefferson Blvd., Suite 200, Culver City, California, 90232, US"
        ["items"]=>
        array(1) {
          [0]=>
          array(9) {
            ["product_id"]=>
            string(3) "377"
            ["subtotal"]=>
            string(8) "750.0000"
            ["sku"]=>
            string(6) "abl007"
            ["quantity"]=>
            string(6) "1.0000"
            ["price"]=>
            string(8) "750.0000"
            ["discount"]=>
            int(0)
            ["native_subtotal"]=>
            string(8) "750.0000"
            ["native_price"]=>
            string(8) "750.0000"
            ["native_discount"]=>
            int(0)
          }
        }
      }
      ["magento_website"]=>
      string(12) "Main Website"
      ["magento_store"]=>
      string(14) "Madison Island"
      ["magento_store_view"]=>
      string(7) "English"
      ["store_view_code"]=>
      string(7) "default"
      ["email"]=>
      string(15) "mosses@ebay.com"
      ["zaius_engage_version"]=>
      array(1) {
        [0]=>
        string(6) "0.13.1"
      }
    }
  }
```

</p>
</details>

## createCoupons

<details>
<summary>Request:</summary>
<p>

##### Method:

```php
zaiusEngageCreateCoupons();
```

##### Request:

```php
$jsonOpts   = array(
	"sessionId" => $sessionId,
	"jsonOpts"  => json_encode(array(
		"rule_id"       => 1,
		"format"        => "alphanum",
		"qty"           => 12,
		"length"        => 12,
		"delimiter"     => "",
		"dash"          => "",
		"prefix"        => "prefix",
		"suffix"        => "suffix"					
	))
);
```

</p>
</details>

|Parameter	|Type	|Usage	|Required   |
|---|---|---|---|
|**rule_id** |string, int	|Use this parameter to apply coupon creation to a specific shopping cart rule.	|Yes  |
|**format** |string	|Use this parameter to apply a format to generated coupon codes of a specific shopping cart rule.	|No  |
|**qty** |string, int	|Use this parameter to generated a specified number of coupons.	|No  |
|**length** |string, int	|Use this parameter to generated coupons of a specified length.	|No  |
|**delimiter** |string	|Use this parameter to add a delimiter to generated coupons.	|No  |
|**dash** |string, int	|Use this parameter to generate dashes in coupon codes every X characters.	|No  |
|**prefix** |string	|Use this parameter to add a prefix to generated coupon codes.	|No  |
|**suffix** |string	|Use this parameter to add a suffix to generated coupon codes.	|No  |


<details>
<summary>Response:</summary>
<p>

```json
array(2) {
  ["type"]=>
  string(6) "coupon"
  ["data"]=>
  array(2) {
    ["zaius_engage_version"]=>
    string(6) "0.13.1"
    ["codes"]=>
    array(1) {
      [0]=>
      string(12) "DSTXKP2I567N"
    }
  }
}
```

</p>
</details>

## subscribers

<details>
<summary>Request:</summary>
<p>

##### Method:

```php
zaiusEngageSubscribers();
```

##### Request:

```json
$jsonOpts   = array(
    "sessionId" => $sessionId,
    "jsonOpts"  => json_encode(array(
        "updated_at"  => "2018-01-01",
        "created_at"  => "2018-01-01"
    ))
);
```

</p>
</details>

|Parameter	|Type	|Usage	|Required   |
|---|---|---|---|
|**updated_at** |string	|Use this parameter to filter your results by a specific updated date. |No  |
|**created_at** |string	|Use this parameter to filter your results by a specific created date. |No  |

<details>
<summary>Response:</summary>
<p>

```json
array(1) {
  [0]=>
  array(2) {
    ["type"]=>
    string(10) "subscriber"
    ["data"]=>
    array(5) {
      ["email"]=>
      string(18) "mickey@example.com"
      ["list_id"]=>
      string(18) "default_newsletter"
      ["subscribed"]=>
      bool(true)
      ["ts"]=>
      string(19) "2018-05-21 09:48:23"
      ["zaius_engage_version"]=>
      array(1) {
        [0]=>
        string(6) "0.13.1"
      }
    }
  }
```

</p>
</details>

## configuration

<details>
<summary>Request:</summary>
<p>

##### Method:

```php
zaiusEngageConfiguration();
```

##### Request:

```json
$jsonOpts   = array(
  "jsonOpts"  => json_encode(array(
    "zaius_tracking_id" => "RanDOmZaIus-tRAcKinGiD"
  ))
);
```

</p>
</details>

|Parameter	|Type	|Usage	|Required   |
|---|---|---|---|
|**zaius_tracking_id** |string	|Use this parameter to filter your results by a specific Zaius Tracking Identifier.	|No |


<details>
<summary>Response:</summary>
<p>

```json
array(3) {
  ["default"]=>
  array(7) {
    ["wsi_enabled"]=>
    bool(true)
    ["magento_fpc_enabled"]=>
    bool(false)
    ["magento_edition"]=>
    string(9) "Community"
    ["magento_version"]=>
    string(7) "1.9.3.9"
    ["zaius_engage_version"]=>
    string(6) "0.13.1"
    ["zaius_engage_enabled"]=>
    bool(true)
    ["config"]=>
    array(3) {
      ["zaius_config"]=>
      array(8) {
        ["zaius_newsletter_list_id"]=>
        string(10) "newsletter"
        ["track_orders_on_frontend"]=>
        string(1) "1"
        ["track_product_listings"]=>
        string(1) "0"
        ["use_magento_customer_id"]=>
        string(1) "1"
        ["tracking_id"]=>
        string(22) "piUzfdlpdHM-rtbS6OpeCQ"
        ["global_id_prefix"]=>
        string(0) ""
        ["collect_all_product_attributes"]=>
        string(1) "0"
        ["reported_currency"]=>
        string(1) "0"
      }
      ["zaius_cron"]=>
      array(2) {
        ["active"]=>
        string(1) "0"
        ["cron_settings"]=>
        string(11) "*/5 * * * *"
      }
      ["zaius_localizations"]=>
      array(1) {
        ["locale_toggle"]=>
        string(1) "0"
      }
    }
  }
  ["french"]=>
  array(7) {
    ["magento_fpc_enabled"]=>
    bool(false)
    ["magento_edition"]=>
    string(9) "Community"
    ["wsi_enabled"]=>
    bool(true)
    ["magento_version"]=>
    string(7) "1.9.3.9"
    ["zaius_engage_version"]=>
    string(6) "0.13.1"
    ["zaius_engage_enabled"]=>
    bool(true)
    ["config"]=>
    array(3) {
      ["zaius_config"]=>
      array(8) {
        ["zaius_newsletter_list_id"]=>
        string(10) "newsletter"
        ["track_orders_on_frontend"]=>
        string(1) "1"
        ["track_product_listings"]=>
        string(1) "0"
        ["use_magento_customer_id"]=>
        string(1) "1"
        ["tracking_id"]=>
        string(22) "piUzfdlpdHM-rtbS6OpeCQ"
        ["global_id_prefix"]=>
        string(0) ""
        ["collect_all_product_attributes"]=>
        string(1) "0"
        ["reported_currency"]=>
        string(1) "0"
      }
      ["zaius_cron"]=>
      array(2) {
        ["active"]=>
        string(1) "0"
        ["cron_settings"]=>
        string(11) "*/5 * * * *"
      }
      ["zaius_localizations"]=>
      array(1) {
        ["locale_toggle"]=>
        string(1) "0"
      }
    }
  }
  ["german"]=>
  array(7) {
    ["magento_fpc_enabled"]=>
    bool(false)
    ["magento_edition"]=>
    string(9) "Community"
    ["wsi_enabled"]=>
    bool(true)
    ["magento_version"]=>
    string(7) "1.9.3.9"
    ["zaius_engage_version"]=>
    string(6) "0.13.1"
    ["zaius_engage_enabled"]=>
    bool(true)
    ["config"]=>
    array(3) {
      ["zaius_config"]=>
      array(8) {
        ["zaius_newsletter_list_id"]=>
        string(10) "newsletter"
        ["track_orders_on_frontend"]=>
        string(1) "1"
        ["track_product_listings"]=>
        string(1) "0"
        ["use_magento_customer_id"]=>
        string(1) "1"
        ["tracking_id"]=>
        string(22) "piUzfdlpdHM-rtbS6OpeCQ"
        ["global_id_prefix"]=>
        string(0) ""
        ["collect_all_product_attributes"]=>
        string(1) "0"
        ["reported_currency"]=>
        string(1) "0"
      }
      ["zaius_cron"]=>
      array(2) {
        ["active"]=>
        string(1) "0"
        ["cron_settings"]=>
        string(11) "*/5 * * * *"
      }
      ["zaius_localizations"]=>
      array(1) {
        ["locale_toggle"]=>
        string(1) "0"
      }
    }
  }
}
```
</p>
</details>


## Changelog

|Version    |Release    |Log    |
|---|---|---|
|0.14.0|2019-01-25|* Removed bulk import legacy subscriber states which had no effect in Zaius.<br>* Added list support to bulk subscriber imports<br>* Removed unnecessary product listing events which caused significant performance issues.<br>* Cleaned up localizations for broader compatibility.<br>* Further updated double opt-in workflows against a stricter audit. Added default to ensure visibility in configuration.<br>* Added parameter to subscriber API to enable pulling a particular subscriber by email address (for troubleshooting purposes) |
|0.13.4|2018-11-05|* Updated beta support for double opt-in workflows.<br>|
|0.13.3|2018-10-22|* Added beta support for double opt-in workflows<br>* Cleaned up potential undefined case when getting subscriber information (which would have appeared in logs, but didn't interrupt tracking or loading)|
|0.13.2|2018-09-19|* Hotfixes for a variety of bulk import errors<br>* Minor tweaks to localizations code for broader compatibility<br>* Additional logging of errors to Zaius-specific logs<br>* Added product availability field which is populated using the Magento "getIsSalable" function.|
|0.13.1 |2018-07-13 |* Hotfixes for tracking live updates to products when using new localizations feature. |
|0.13.0 |2018-07-12 |* **Localizations Update:** Adds support for localized store_views, including inheritance, linking between languages, varied currency reporting, and more. (During the public beta phase, configuration requires support from the Zaius team, please contact your Customer Success Manager)<br>* Improved support for configurable products (more to come)<br>* Improved and extended APIs to enable the Zaius support team to provide more accurate troubleshooting, more quickly. |
|0.12.2 |2018-07-12 |* Hotfixes for Coupon Code Support which addresses a case where coupons were not setting the appropriate type, causing the generated codes to be usable multiple times.    |
|0.12.1 |2018-06-20 |* Hotfixes for Batch Updates which cover more environments and improve consistency of APIs.    |
|0.12.0 |2018-06-04 |* Further fix for Customer address import issues causing some addresses to be cut off prematurely. If affected, install the update, then ask Zaius Support to reimport customers for you.<br>* Fix for subscriber import and updates ensuring subscribe events are appropriately timestamped - prevents a subscriber synchronization causing all subscribers to appear actively engaged within Zaius.<br>* New Feature: Batch Updates. In certain situations, updates (frequently to products)  are not detected by Magento, which meant Magento never updated Zaius. This could cause Magento and Zaius to get out of sync. We've added an optional component which will configure Magento to send regular updates to Zaius. When activated, this feature will add columns to Magento databases (zaius_created_at and zaius_updated_at) so that the batch process can send us updates of only new and updated data points. While we have not previously identified cases beyond the products feed, this feature is available for products, customers, and orders (but not refunds or cancellations). Please see "Enabling Batch Updates" above for details on configuration.  |
|0.11.1 |2018-05-14 |* Fix for Customer address import issues causing some addresses to be cut off prematurely. If affected, install the update, then ask Zaius Support to reimport customers for you.  |
|0.11.0 |   |Support Magento EE Full Page Cache |
|0.10.2 |   |Fixed for WS-I compliance for python and .NET clients. |
|0.10.1 |   |The initial data ingest will no longer fail if orders correspond to an invalid Magento store.  | 
|0.10.0 |   |Zaius works with Magento when WS-I mode is enabled.    |
|0.9.0  |   |In-stock information/quantity remaining is now provided for items each time they are purchased.    |
|0.8.3  |   |Orders now use base currency, rather than native currency. |
|0.8.2  |   |Fix a regression in support for older PHP versions where the plugin would not work correctly.  |
|0.8.1  |   |Every API call to Zaius now submits data via an encrypted connection.  |
|0.8.0  |   |Allow product/order/customer ids to be prefixed by a unique id to allow multiple magento instances to be pulled into a single Zaius account.   |
|0.7.2  |   |For products, Zaius now uses the "media URL" for an item, rather than the cached, rescaled version of an image.    |
|0.7.1  |   |Provide product brand information from the Magento manufacturer field. |
|0.7.0  |   |Add support for generating unique coupon codes via Zaius campaigns.    |
|0.6.11 |   |Fixed a potential problem with cart abandonment link handling. |
|0.6.10 |   |* Support installs with php up to 7.1.2<br>* Fix a bug where cart abandonment required a static cart creation time (other Magento plugins can change the cart creation timestamp) resulting in problems with cart abandonment campaigns.   |
|0.6.8  |   |Backend order tracking now correctly handles orders that move directly from "pending" to "complete". Previously, because "processing" was skipped, the order purchase event was not being sent. This only applies if the "Track Orders on Frontend" option is turned off.  |
|0.6.7  |   |* Cart Abandonment links now work for installs with multiple servers.<br>* Orders are now tracked when the order moved into the "processing" state, rather than when it is created (only if the "Track Orders on Frontend" option is turned off).    |
|0.6.6  |   |* UTM parameters are passed along during abandoned cart redirects.<br>* Custom fields are now optionally returned on products.<br>* The plugin version number (0.6.6) is provided back to Zaius when data is sent to Zaius, to aid in debugging.   |
|0.6.4  |   |* Abandon cart links work more reliably with Zaius Engage.<br>* Product images are now correctly imported into Zaius.  |
|0.6.2  |   |Provide historical information about customer unsubscribe status upon customer ingest. |
|0.6.1  |   |Bug fixes related to posting of backend orders and product entities..  |
|0.6.0  |   |* Default orders to be produced via the Magento backend, rather than relying on the post-order page fully loading.<br>* Fix an issue related to UTF-8 corruption in billing address which could cause lost orders for customers in foreign locales.    |
|0.5.7  |   |Allows cart abandonment to work for customers who are not logged into the system (but whose identities can be inferred through user stitching). |
|0.5.5  |   |Minor bugfix related to producing errors instead of exceptions on missing image files. |
|0.5.4  |   |Minor bugfix related to incorrect processing of purchases entered from the admin console.  |
|0.5.3  |   |Changed order tracking strategy to work more reliably with checkouts involving redirection to third party payment processors.  |
|0.5.2  |   |Capture image_url and description for product entities.    |
|0.5.1  |   |Provide information for shopping cart abandonment campaigns.   |
|0.5.0  |   |Support order cancellation and order refunds.  |
|0.4.0  |   |* New bulk retrieval API to streamline and generally improve the initial import process.<br>* Capture price for product entities.<br>* Capture associated Magento website, store, and store view on all events (and bulk imported orders) to aid in scoping and analysis.  |
|0.3.2  |   |Fixed order tracking bug that was causing multi-shipping checkouts to fail.    |
|0.3.1  |   |* Added safer handling of potentially missing internal data fields. If you are having trouble with the checkout process not completing, this should fix the issue.<br>* Explicitly reverts to anonymous mode upon customer logout. |
|0.3.0  |   |
