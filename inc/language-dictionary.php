<?php
/**
 * ===================================================================
 * HKDEV GLOBAL LANGUAGE DICTIONARY
 * ===================================================================
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if (!function_exists('hkdev_t')) {
    function hkdev_t($key) {
        $lang = (isset($_COOKIE['hkdev_lang']) && $_COOKIE['hkdev_lang'] === 'en') ? 'en' : 'bn';
        
        $dict = array(
            
            // ---------------- Global & Common ----------------
            'processing'         => ['bn' => 'প্রসেস হচ্ছে...', 'en' => 'Processing...'],
            'apply'              => ['bn' => 'এপ্লাই করুন', 'en' => 'Apply'],
            'remove'             => ['bn' => 'রিমুভ করুন', 'en' => 'Remove'],
            'subtotal'           => ['bn' => 'সাবটোটাল', 'en' => 'Subtotal'],
            'qty'                => ['bn' => 'পরিমাণ:', 'en' => 'Qty:'],
            'home'               => ['bn' => 'হোম', 'en' => 'Home'],
            
            // ---------------- Shop / Grid / General ----------------
            'shop'               => ['bn' => 'শপ', 'en' => 'Shop'],
            'buy_now'            => ['bn' => 'অর্ডার করুন', 'en' => 'Buy Now'],
            'add_to_cart'        => ['bn' => 'কার্টে রাখুন', 'en' => 'Add to Cart'],
            'stock_out'          => ['bn' => 'স্টক আউট', 'en' => 'Stock Out'],
            'out_of_stock'       => ['bn' => 'স্টকে নেই', 'en' => 'Out Of Stock'],
            'in_stock'           => ['bn' => 'স্টকে আছে', 'en' => 'In Stock'],
            'best_seller'        => ['bn' => 'বেস্ট সেলার', 'en' => 'Best Seller'],
            'trending'           => ['bn' => 'ট্রেন্ডিং', 'en' => 'Trending'],
            'hot_today'          => ['bn' => 'হট টুডে', 'en' => 'Hot Today'],
            'off'                => ['bn' => 'ছাড়', 'en' => 'Off'],
            'off_text'           => ['bn' => 'ছাড়!', 'en' => 'Off!'],
            'all'                => ['bn' => 'সকল', 'en' => 'All'],
            'added_success'      => ['bn' => 'সফলভাবে কার্টে যুক্ত হয়েছে!', 'en' => 'Successfully added to cart!'],
            'checkout'           => ['bn' => 'চেকআউট', 'en' => 'Checkout'],
            'view_cart'          => ['bn' => 'কার্ট দেখুন', 'en' => 'View Cart'],
            'search_placeholder' => ['bn' => 'প্রোডাক্ট খুঁজুন...', 'en' => 'Search products...'],
            'product_not_found'  => ['bn' => 'কোনো প্রোডাক্ট পাওয়া যায়নি!', 'en' => 'Product Not Found!'],
            
            // ---------------- Mini Cart & Main Cart ----------------
            'cart_empty'         => ['bn' => 'আপনার কার্ট খালি!', 'en' => 'Your cart is empty!'],
            'cart_empty_sub'     => ['bn' => 'অর্ডার সম্পন্ন করতে অনুগ্রহ করে কার্টে প্রোডাক্ট যুক্ত করুন।', 'en' => 'Please add products to your cart to proceed.'],
            'start_shop'         => ['bn' => 'শপিং শুরু করুন', 'en' => 'Start Shopping'],
            'total_items'        => ['bn' => 'মোট আইটেম:', 'en' => 'Total Items:'],
            'shopping_cart'      => ['bn' => 'শপিং কার্ট', 'en' => 'Shopping Cart'],
            'selected_products'  => ['bn' => 'আপনার নির্বাচিত পণ্যসমূহ', 'en' => 'Your Selected Products'],
            'cart_summary'       => ['bn' => 'কার্ট সামারি', 'en' => 'Cart Summary'],
            'updating'           => ['bn' => 'আপডেট হচ্ছে...', 'en' => 'Updating...'],
            'total_text'         => ['bn' => 'মোট', 'en' => 'Total'],
            
            // ---------------- Checkout Page ----------------
            'invalid_phone'      => ['bn' => 'সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন', 'en' => 'Enter a valid 11-digit mobile number'],
            'summary'            => ['bn' => 'অর্ডার সামারি', 'en' => 'Order Summary'],
            'shipping'           => ['bn' => 'শিপিং চার্জ', 'en' => 'Shipping Charge'],
            'grand_total'        => ['bn' => 'সর্বমোট', 'en' => 'Grand Total'],
            'del_area'           => ['bn' => 'ডেলিভারি এলাকা', 'en' => 'Delivery Area'],
            'address_pay'        => ['bn' => 'ডেলিভারি ঠিকানা ও পেমেন্ট', 'en' => 'Delivery Details & Payment'],
            'form_name'          => ['bn' => 'আপনার নাম', 'en' => 'Your Name'],
            'form_name_ph'       => ['bn' => 'যেমন: মোঃ আব্দুল্লাহ', 'en' => 'e.g. Md. Abdullah'],
            'form_phone'         => ['bn' => 'মোবাইল নম্বর', 'en' => 'Mobile Number'],
            'form_phone_ph'      => ['bn' => '০১৭XXXXXXXX', 'en' => '017XXXXXXXX'],
            'form_address'       => ['bn' => 'বিস্তারিত ঠিকানা', 'en' => 'Full Address'],
            'form_address_ph'    => ['bn' => 'গ্রাম/মহল্লা, রোড নম্বর, থানা ও জেলা', 'en' => 'House, Road, Thana, District'],
            'confirm_btn'        => ['bn' => 'অর্ডার কনফার্ম করুন', 'en' => 'Confirm Order'],
            
            // ---------------- Coupons ----------------
            'coupon'             => ['bn' => 'কুপন:', 'en' => 'Coupon:'],
            'coupon_ph'          => ['bn' => 'কুপন কোড লিখুন (যদি থাকে)', 'en' => 'Enter coupon code (if any)'],
            'enter_coupon'       => ['bn' => 'অনুগ্রহ করে কুপন কোড লিখুন।', 'en' => 'Please enter a coupon code.'],
            'coupon_success'     => ['bn' => 'কুপনটি সফলভাবে এপ্লাই হয়েছে!', 'en' => 'Coupon successfully applied!'],
            'coupon_fail'        => ['bn' => 'কুপনটি সঠিক নয় অথবা মেয়াদ শেষ হয়েছে।', 'en' => 'Coupon is invalid or expired.'],
            'coupon_removed'     => ['bn' => 'কুপনটি রিমুভ করা হয়েছে!', 'en' => 'Coupon has been removed!'],
            
            // ---------------- Order Success / Thank You ----------------
            'success_title'      => ['bn' => 'অর্ডারটি সফলভাবে সম্পন্ন হয়েছে!', 'en' => 'Order Successfully Placed!'],
            'success_sub'        => ['bn' => 'অর্ডারটি নিশ্চিত করতে আমাদের প্রতিনিধি শীঘ্রই আপনাকে কল করবেন। ধন্যবাদ!', 'en' => 'Our representative will call you soon to confirm the order. Thank you!'],
            'order_no'           => ['bn' => 'অর্ডার নম্বর:', 'en' => 'Order Number:'],
            'date'               => ['bn' => 'তারিখ', 'en' => 'Date'],
            'total_bill'         => ['bn' => 'মোট বিল', 'en' => 'Total Bill'],
            'pay_method'         => ['bn' => 'পেমেন্ট মেথড', 'en' => 'Payment Method'],
            'shop_more'          => ['bn' => 'আরও শপিং করুন', 'en' => 'Shop More'],
            'order_not_found'    => ['bn' => 'দুঃখিত, অর্ডারটি খুঁজে পাওয়া যায়নি।', 'en' => 'Sorry, the order could not be found.'],
            'address_title'      => ['bn' => 'ডেলিভারি ঠিকানা', 'en' => 'Delivery Address'],
            'name_label'         => ['bn' => 'নাম:', 'en' => 'Name:'],
            'scan_qr'            => ['bn' => 'স্ক্যান করে অর্ডার যাচাই করুন', 'en' => 'Scan to verify order'],
            'pcs'                => ['bn' => 'টি', 'en' => 'pcs'],
            
            // ---------------- Single Product ----------------
            'click_to_zoom'      => ['bn' => 'জুম করতে ক্লিক করুন', 'en' => 'Click to Zoom'],
            'select'             => ['bn' => 'সিলেক্ট করুন', 'en' => 'Select'],
            'completed_order'    => ['bn' => 'চেকআউটে যান', 'en' => 'Completed your order'],
            'sku'                => ['bn' => 'এসকেইউ (SKU)', 'en' => 'SKU'],
            'stock_status'       => ['bn' => 'স্টক স্ট্যাটাস', 'en' => 'Stock Status'],
            'description'        => ['bn' => 'বিস্তারিত বিবরণ', 'en' => 'Description'],
            'review'             => ['bn' => 'রিভিউ', 'en' => 'Review'],
            'remove_item'        => ['bn' => 'পণ্যটি মুছে ফেলুন', 'en' => 'Remove Item'],
            
            // ---------------- Mega Menu & Category Slider ----------------
            'all_categories'     => ['bn' => 'সকল ক্যাটাগরি', 'en' => 'All Categories'],
            'items'              => ['bn' => 'টি প্রোডাক্ট', 'en' => 'Items'],
            'no_specific_cat'    => ['bn' => 'কোনো নির্দিষ্ট ক্যাটাগরি পাওয়া যায়নি।', 'en' => 'No specific category found.'],
            'no_cat_found'       => ['bn' => 'কোনো ক্যাটাগরি পাওয়া যায়নি।', 'en' => 'No category found.'],
            
            // ---------------- Hero Slider ----------------
            'no_banner_found'    => ['bn' => 'এই লোকেশনের জন্য কোনো ব্যানার ইমেজ পাওয়া যায়নি। দয়া করে ড্যাশোর্ড থেকে ব্যানার আপলোড করুন।', 'en' => 'No banner image found for this location. Please upload banners from the dashboard.'],
			
			//----------------- size chart-----------
			'size_chart' => ['bn' => 'সাইজ চার্ট দেখুন', 'en' => 'View Size Chart'],
			
			
			'bogo_badge_loop'    => ['bn' => '%dটি কিনলে %dটি ফ্রি!', 'en' => 'Buy %d Get %d Free!'],
			'bogo_notice'        => ['bn' => '🎁 অফার: %dটি কিনলে %dটি ফ্রি! (অফার পেতে কার্টে %dটি অ্যাড করুন)', 'en' => '🎁 Offer: Buy %d Get %d Free! 			(Add %d items to cart)'],
			'bogo_cart_badge'    => ['bn' => '🎁 %dটি ফ্রি পেয়েছেন!', 'en' => '🎁 %d Item Free!'],
			'bogo_fee_name'      => ['bn' => 'ফ্রি আইটেম (%s)', 'en' => 'Free Item (%s)'],
			'free_item_congrats' => ['bn' => 'অভিনন্দন! আপনি %dটি ফ্রি আইটেম পেয়েছেন!', 'en' => 'Congratulations! You got %d free item(s)!'],
        );
        return isset($dict[$key][$lang]) ? $dict[$key][$lang] : $key;
    }
}