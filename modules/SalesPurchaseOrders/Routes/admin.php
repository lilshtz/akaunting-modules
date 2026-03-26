<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'sales-purchase-orders' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('sales-purchase-orders', function () {
    // Sales Order settings
    Route::match(['get', 'post'], 'settings', 'SalesOrders@settings')->name('sales-purchase-orders.settings');

    // Sales Order actions
    Route::post('sales-orders/{sales_order}/send', 'SalesOrders@send')->name('sales-purchase-orders.sales-orders.send');
    Route::post('sales-orders/{sales_order}/confirm', 'SalesOrders@confirm')->name('sales-purchase-orders.sales-orders.confirm');
    Route::post('sales-orders/{sales_order}/issue', 'SalesOrders@issue')->name('sales-purchase-orders.sales-orders.issue');
    Route::post('sales-orders/{sales_order}/cancel', 'SalesOrders@cancel')->name('sales-purchase-orders.sales-orders.cancel');
    Route::post('sales-orders/{sales_order}/convert-invoice', 'SalesOrders@convertInvoice')->name('sales-purchase-orders.sales-orders.convert-invoice');
    Route::post('sales-orders/{sales_order}/convert-po', 'SalesOrders@convertPurchaseOrder')->name('sales-purchase-orders.sales-orders.convert-po');
    Route::post('sales-orders/{sales_order}/duplicate', 'SalesOrders@duplicate')->name('sales-purchase-orders.sales-orders.duplicate');
    Route::get('sales-orders/{sales_order}/pdf', 'SalesOrders@pdf')->name('sales-purchase-orders.sales-orders.pdf');
    Route::get('sales-orders/import', 'SalesOrders@import')->name('sales-purchase-orders.sales-orders.import');
    Route::post('sales-orders/import', 'SalesOrders@importProcess')->name('sales-purchase-orders.sales-orders.import.process');
    Route::get('sales-orders/export', 'SalesOrders@export')->name('sales-purchase-orders.sales-orders.export');

    // Sales Order CRUD
    Route::resource('sales-orders', 'SalesOrders');

    // Sales Order reports
    Route::get('reports/sales-orders', 'SalesOrders@report')->name('sales-purchase-orders.reports.sales-orders');

    // Purchase Order actions
    Route::post('purchase-orders/{purchase_order}/send', 'PurchaseOrders@send')->name('sales-purchase-orders.purchase-orders.send');
    Route::post('purchase-orders/{purchase_order}/confirm', 'PurchaseOrders@confirm')->name('sales-purchase-orders.purchase-orders.confirm');
    Route::post('purchase-orders/{purchase_order}/receive', 'PurchaseOrders@receive')->name('sales-purchase-orders.purchase-orders.receive');
    Route::post('purchase-orders/{purchase_order}/cancel', 'PurchaseOrders@cancel')->name('sales-purchase-orders.purchase-orders.cancel');
    Route::post('purchase-orders/{purchase_order}/convert-bill', 'PurchaseOrders@convertBill')->name('sales-purchase-orders.purchase-orders.convert-bill');
    Route::post('purchase-orders/{purchase_order}/duplicate', 'PurchaseOrders@duplicate')->name('sales-purchase-orders.purchase-orders.duplicate');
    Route::get('purchase-orders/{purchase_order}/pdf', 'PurchaseOrders@pdf')->name('sales-purchase-orders.purchase-orders.pdf');
    Route::get('purchase-orders/import', 'PurchaseOrders@import')->name('sales-purchase-orders.purchase-orders.import');
    Route::post('purchase-orders/import', 'PurchaseOrders@importProcess')->name('sales-purchase-orders.purchase-orders.import.process');
    Route::get('purchase-orders/export', 'PurchaseOrders@export')->name('sales-purchase-orders.purchase-orders.export');

    // Purchase Order CRUD
    Route::resource('purchase-orders', 'PurchaseOrders');

    // Purchase Order reports
    Route::get('reports/purchase-orders', 'PurchaseOrders@report')->name('sales-purchase-orders.reports.purchase-orders');
});
