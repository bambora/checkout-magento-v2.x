# Worldline Checkout for Magento 2

## Supported Magento 2 versions

* Supports Magento 2.1.x ,2.2.x, 2.3.x and 2.4.x
* If you want to run the module with a Magento version lower than 2.1 you must remove
  these two files
    * view\adminhtml\ui_component\sales_order_grid.xml
    * view\adminhtml\ui_component\sales_order_invoice_grid.xml
    * **NB** When these are removed Mass actions functionality is no longer available,
      and we can no longer guaranty that the module work as intended.
* The ePay Payment gateway is from now it's own module and not part of this. Please visit contact ePay.dk for any questions regarding their new module. 

## Included Payment Gateway

* Worldline Checkout

Documentation: https://developer.bambora.com/europe/shopping-carts/shopping-carts/magento2
