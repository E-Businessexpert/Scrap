<?php  if ( ! defined( 'ABSPATH' ) ) exit;  

    class Extendons_Wp_Scrapper_admin extends Extendons_wp_scrapper {


        public function __construct() {
            
            add_action('admin_menu', array($this,'my_menu_pages'));
            add_action( 'wp_ajax_scrape_data', array($this,'scrape_site_data' ));
            add_action( 'wp_ajax_nopriv_scrape_data', array($this,'scrape_site_data' ));
            add_action( 'wp_ajax_scrape_custom_data', array($this,'scrape_custom_site_data' ));
            add_action( 'wp_ajax_nopriv_scrape_custom_data', array($this,'scrape_custom_site_data' ));
            add_action( 'wp_ajax_post_data', array($this,'post_site_data' ));
            add_action( 'wp_ajax_nopriv_post_data', array($this,'post_site_data' ));
            add_action( 'init',array( $this, 'front_init' ));
        }
        
        public function front_init() {
            wp_enqueue_script('jquery');
            wp_enqueue_media();
            wp_enqueue_style('bootstrap_css2',EXT_FSP_URL.'admin/style/bootstrap-iso.css');
            wp_enqueue_style( 'bootstrap_style', EXT_FSP_URL.'admin/style/bootstrap-iso.css', false );
            wp_enqueue_style( 'awesome-bootstrap-checkbox-css', EXT_FSP_URL.'admin/style/awesome-bootstrap-checkbox.css', false );
            wp_enqueue_style( 'font-awesome-min', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/3.2.1/css/font-awesome.min.css', false );


            require_once(EXT_FSP_PLUGIN_DIR.'admin/lib/simple_html_dom.php');
        }

        public function my_menu_pages(){
            add_menu_page('Extendons WP Scrapper', 'Extendons WP Scrapper', 'manage_options', 'wp_scraper', ARRAY($this,'wp_scrapper_setting' ),plugins_url( 'admin/style/ext_icon.png', dirname( __FILE__ ) ));
            add_submenu_page('wp_scraper', 'Extendons Custom Site Scraper', 'Extendons Custom Site Scraper', 'manage_options',"custom_scrapper",ARRAY($this,'custom_scrapper_setting' ));
            // add_submenu_page('my-menu', 'Submenu Page Title2', 'Whatever You Want2', 'manage_options', 'my-menu2' );
        }

        public function scrape_custom_site_data(){
            global $wpdb;

            if(isset($_POST['link']) && isset($_POST['dtype']) ) {

                $link = $_POST['link'];
                $dtype= $_POST['dtype'];
                $c_id = $_POST['c_id'];
                $title= $_POST['title'];
                $price = $_POST['price'];
                $desp= $_POST['desp'];
                $image = $_POST['image'];
                switch($dtype){
                    // case 'post':
                    //     $this->scrape_custom_post($link,$c_id,$title,$desp,$image);
                    // break;

                    case 'product':
                        $this->scrape_custom_product($link,$c_id,$title,$price,$desp,$image);
                    break;

                    // case 'shop':
                    //     $this->scrape_custom_shop($link,$c_id,$title,$price,$desp,$image);
                    // break;

                    default:
                    echo 'Please Select the scrape type';

                }
            }
            die();
        }

        public function scrape_custom_product($link,$c_id,$title,$price,$desp,$image){
            $context = stream_context_create(
                array(
                    "http" => array(
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                    )
                )
            );
            $dom = file_get_html($link, 'false', $context);
            $answer = array();
            if(!empty($dom)) {
                // $divClass = $title ='';
                // $i = 0;
                foreach($dom->find($c_id) as $divClass) {
                    //title
                    foreach($divClass->find($title) as $title ) {
                        $answer['title'] = $title->plaintext;
                        break;
                    }
                    //price
                    foreach($divClass->find($price) as $price ) {
                        $answer['price'] = trim($price->plaintext);
                        break;
                    }
                    // //content
                    foreach($divClass->find($desp) as $content ) {
                        $answer['content'] = trim($content->plaintext);
                        break;
                    }
                    //main image 
                    foreach($divClass->find($image) as $imagee ) {
                        $answer['image'] = trim($imagee->src);
                        break;
                    }
                    //extra image 
                    if(sizeof($divClass->find('div[class=woocommerce-product-gallery__image]') > 1))
                        {
                        $size=sizeof($divClass->find('div[class=woocommerce-product-gallery__image]'));
                        for($imgnum=1;$imgnum<$size;$imgnum++ ){
                            $answer['extimage'][$imgnum] = $divClass->find('div[class=woocommerce-product-gallery__image]\a\img')[$imgnum]->src; 
                        }
                    }                      
                    // }
                    //variations_form
                    // foreach($divClass->find('form[class=variations_form]') as $variation ) {
                    //     $answer['variation'] = $variation->getAttribute('data-product_variations');
                    //     //str_replace('&quot;', '"',
                    // } 

                    // $i++;
                }
            }
            $Result=array($answer,'product');
            echo json_encode($Result);
            // echo json_encode($answer);
        }
        public function scrape_custom_shop($link,$c_id,$title,$price,$desp,$image){
            $context = stream_context_create(
                array(
                    "http" => array(
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                    )
                )
            );

            $dom = file_get_html($link, 'false', $context);
            $answer = array();
            if(!empty($dom)) {
            $divClass = $title ='';$i = 0;
                foreach($dom->find($c_id) as $divClass) {
                //for shop page
                    $size=sizeof($divClass->find('a[class=woocommerce-loop-product__link]'));
                    for($pno=0;$pno<$size;$pno++ ){
                        $answer['products'][$pno] = $divClass->find('a[class=woocommerce-loop-product__link]')[$pno]->getAttribute('href'); 
                    } 
                    $products = array();
                    for($loop=0;$loop<$size;$loop++){
                        // title
                        $context = stream_context_create(
                            array(
                                "http" => array(
                                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                                )
                            )
                        );
                        $dom = file_get_html($answer['products'][$loop], 'false', $context);
                        if(!empty($dom)) {
                            $divClass = $title ='';$i = 0;
                            foreach($dom->find($c_id) as $divClass) {
                                foreach($divClass->find($title) as $title ) {
                                    $products[$loop]['title'] = $title->plaintext;
                                    break;
                                }
                                //price
                                foreach($divClass->find($price) as $price ) {
                                    $products[$loop]['price'] = trim($price->plaintext);
                                    break;
                                }
                                //content
                                // foreach($divClass->find($desp) as $content ) {
                                //     $products[$loop]['content'] = trim($content->plaintext);
                                //     break;
                                // }
                                //main image 
                                foreach($divClass->find('img[class='.$image.']') as $image ) {
                                    $products[$loop]['image'] = trim($image->src);
                                break;
                                }
                                //extra image 
                                // if(sizeof($divClass->find('div[class=woocommerce-product-gallery__image]')) > 1){
                                //     $sizeimg=sizeof($divClass->find('div[class=woocommerce-product-gallery__image]'));
                                //     for($imgnum=1;$imgnum<$sizeimg;$imgnum++ ){
                                //         $products[$loop]['extimage'][$imgnum] = $divClass->find('div[class=woocommerce-product-gallery__image]\a\img')[$imgnum]->src;
                                //     }
                                // }
                                //variations_form
                                // foreach($divClass->find('form[class=variations_form]') as $variation ) {
                                //     $products[$loop]['variation'] = trim($variation->getAttribute('data-product_variations'));
                                // } 
                            }
                        }    
                    }
                }
            }
            $Result=array($products,'shop');
            echo json_encode($Result);
        }

        public function scrape_site_data(){
            global $wpdb;

            if(isset($_POST['link']) && isset($_POST['dtype']) ) {

                $link = $_POST['link'];
                $dtype= $_POST['dtype'];
                switch($dtype){
                    case 'post':
                        $this->scrape_post($link);
                    break;

                    case 'product':
                        $this->scrape_product($link);
                    break;

                    case 'shop':
                        $this->scrape_shop($link);
                    break;

                    default:
                    echo 'Please Select the scrape type';

                }
            }
            die();
        }

        public function scrape_shop($link) {
            $products = array();
            $context = stream_context_create(
                array(
                    "http" => array(
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                    )
                )
            );

            $dom = file_get_html($link, 'false', $context);
            $answer = array();

            if (!empty($dom)) {

                $divClass = $title ='';$i = 0;
                foreach($dom->find(".products") as $divClass) {

                //for shop page [class=woocommerce-loop-product__link]
                    $size=sizeof($divClass->find('.product'));
                    $product_div = $divClass->find('.product');

                    
                    for($pno=0;$pno<$size;$pno++ ){
                        $answer['products'][$pno] = $product_div[$pno]->find('a')[0]->getAttribute('href');

                    } 
                    $products = array();
                    for($loop=0;$loop<$size;$loop++){
                        // title
                        $context = stream_context_create(
                            array(
                                "http" => array(
                                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                                )
                            )
                        );
                        $dom = file_get_html($answer['products'][$loop], 'false', $context);
                        if(!empty($dom)) {
                            $divClass = $title ='';$i = 0;
                            foreach($dom->find("#main") as $divClass) {
                                
                                foreach($divClass->find(".product_title") as $title ) {
                                    $products[$loop]['title'] = $title->plaintext;
                                    break;
                                }


                                if ( '' == $products[$loop]['title']) {
                                    foreach($divClass->find(".product-title") as $title ) {
                                        $products[$loop]['title'] = $title->plaintext;
                                        break;
                                    }
                                }
                                //price
                                foreach($divClass->find('.price') as $price ) {
                                    $products[$loop]['price'] = trim($price->plaintext);
                                    break;
                                }

                                if ('' == $products[$loop]['price']) {
                                    $products[$loop]['price'] = '';

                                }
                                //content
                                foreach($divClass->find('.woocommerce-product-details__short-description') as $content ) {
                                    $products[$loop]['content'] = trim($content->plaintext);
                                    break;
                                }


                                if ( '' == $products[$loop]['content']) {
                                    //content
                                    foreach($divClass->find('.product-short-description') as $content ) {
                                        $products[$loop]['content'] = trim($content->plaintext);
                                        break;
                                    }
                                }

                                if ( '' == $products[$loop]['content']) {
                                    //content
                                    foreach($divClass->find('#tab-description') as $content ) {
                                        $products[$loop]['content'] = trim($content->plaintext);
                                        break;
                                    }
                                }

                                //main image 
                                foreach($divClass->find('.wp-post-image') as $image ) {
                                    $products[$loop]['image'] = trim($image);
                                    break;
                                }
                                //extra image 
                                if(sizeof($divClass->find('div[class=woocommerce-product-gallery__image]')) > 1){
                                    $sizeimg=sizeof($divClass->find('div[class=woocommerce-product-gallery__image]'));
                                    for($imgnum=1;$imgnum<$sizeimg;$imgnum++ ){
                                        $products[$loop]['extimage'][$imgnum] = $divClass->find('div[class=woocommerce-product-gallery__image]\a\img')[$imgnum]->src;
                                    }
                                }
                                //variations_form
                                foreach ($divClass->find('form[class=variations_form]') as $variation ) {
                                    $products[$loop]['variation'] = trim($variation->getAttribute('data-product_variations'));
                                } 
                            }
                        }    
                    }
                }
            }
            $Result=array($products,'shop');
            echo json_encode($Result);
        }

        public function scrape_product($link) {
            $context = stream_context_create(
                array(
                    "http" => array(
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                    )
                )
            );
            $dom = file_get_html($link, 'false', $context);
            $answer = array();
            if(!empty($dom)) {
                $divClass = $title  = $answer['title'] = '';$i = 0;
                foreach($dom->find(".type-product") as $divClass) {
                    //title
                    foreach($divClass->find(".product_title") as $title ) {
                        $answer['title'] = $title->plaintext;
                        break;
                    }

                    if ( '' == $answer['title']) {
                        foreach($divClass->find(".product-title") as $title ) {
                            $answer['title'] = $title->plaintext;
                            break;
                        }
                    }
                    //price
                    foreach($divClass->find('.price') as $price ) {
                        $answer['price'] = trim($price->plaintext);
                        break;
                    }

                    if ('' == $answer['price']) {
                        $answer['price'] = '';
                    }
                    //content
                    foreach($divClass->find('.product-short-description') as $content ) {
                        $answer['content'] = trim($content->plaintext);
                        break;
                    }

                    if ( '' == $answer['content']) {
                        //content
                        foreach($divClass->find('.woocommerce-product-details__short-description') as $content ) {
                            $answer['content'] = trim($content->plaintext);
                            break;
                        }
                    }


                    if ( '' == $answer['content']) {
                        //content
                        foreach($divClass->find('#tab-description') as $content ) {
                            $answer['content'] = trim($content->plaintext);
                            break;
                        }
                    }

                    //main image 
                    foreach($divClass->find('.wp-post-image') as $image ) {
                        $answer['image'] = trim($image);
                        break;
                    }

                    // if ('' == $answer['image']) {
                    //    //main image
                    //     foreach($divClass->find('img') as $image ) {
                    //         $answer['image'] = trim($image->src);
                    //         break;
                    //     } 
                    // }
                    //extra image 
                    if(sizeof($divClass->find('div[class=woocommerce-product-gallery__image]') > 1))
                        {
                        $size=sizeof($divClass->find('div[class=woocommerce-product-gallery__image]'));
                        for($imgnum=1;$imgnum<$size;$imgnum++ ){
                            $answer['extimage'][$imgnum] = $divClass->find('div[class=woocommerce-product-gallery__image]\a\img')[$imgnum]->src; 
                        }

                      
                    }
                    //variations_form
                    foreach($divClass->find('form[class=variations_form]') as $variation ) {
                        $answer['variation'] = $variation->getAttribute('data-product_variations');
                        //str_replace('&quot;', '"',
                    } 

                    $i++;
                    break;
                }
            }
            $Result=array($answer,'product');
            echo json_encode($Result);
        }

        public function scrape_post($link){
            $context = stream_context_create(
                array(
                    "http" => array(
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                    )
                )
            );
            $dom = file_get_html($link, 'false', $context);
            $answer = array();
            if(!empty($dom)) {
                $divClass = $title ='';$i = 0;
                foreach($dom->find("#main") as $divClass) {
                    //title
                    foreach($divClass->find(".entry-title") as $title ) {
                        $answer['title'] = $title->plaintext;
                        break;
                    }
                    //content
                    $answer['content']="";
                        foreach($divClass->find('div[class=post-content]\p') as $content ) {
                        $answer['content'] = $answer['content'].trim($content->plaintext);
                    }
                    //main image 
                    foreach($divClass->find('img[class=wp-post-image]') as $image ) {
                        $answer['image'] = trim($image->src);
                        break;
                    }

                    $i++;
                }
            }
            $Result=array($answer,'post');
            echo json_encode($Result);
        }

        public function post_site_data(){
            $TotalProducts= sizeof($_POST['data']);

            if ($_POST['ptype'] == 'product' || $_POST['ptype'] == 'shop') {
                global $wpdb;
                $upPrp=array();
                for($i=0;$i<$TotalProducts;$i++) {
                    if(isset($_POST['data'][$i]['variation'])) {

                        $res = array();
                        $str = $_POST['data'][$i]['price'];
                        $str = preg_replace("/[^0-9\.]/", " ", $str);
                        $str = trim(preg_replace('/\s+/u', ' ', $str));
                        $arr = explode(' ', $str);
                        for ($j = 0; $j < count($arr); $j++) { 
                            if (is_numeric($arr[$j])) {
                                
                                $res[] = $arr[$j];
                            }
                        }
                    } else {

                        if(isset($_POST['data'][$i]['price'])) {
                            $str = $_POST['data'][$i]['price'];
                            $str = preg_replace("/[^0-9\.]/", " ", $str);
                            $str = trim(preg_replace('/\s+/u', ' ', $str));
                            $res = array($str,$str);
                        }
                        
                    } 
                    if(isset($_POST['data'][$i]['content'])){
                        
                        $content=$_POST['data'][$i]['content'];
                    }
                    else{
                        
                        $content="";
                    }
                    if(isset($_POST['data'][$i]['title'])){
                        
                        $title=$_POST['data'][$i]['title'];
                    }
                    else{
                        
                        $title="none";
                    }

                    $post = array(
                        'post_author' => get_current_user_id(),
                        'post_content' => $content,
                        'post_status' => "publish",
                        'post_title' => $title,
                        'post_parent' => '',
                        'post_type' => "product",
                    );
                    //Create post
                    $post_id = wp_insert_post( $post, $wp_error );
                    if($post_id){
                        array_push($upPrp, $post_id);
                        if(isset($_POST['data'][$i]['image'])){
                            $image_url=   $_POST['data'][$i]['image'];
                            $image_name       = $image_url;
                            $upload_dir       = wp_upload_dir(); // Set upload folder
                            $image_data       = file_get_contents($image_url); // Get image data
                            $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
                            $filename         = basename( $unique_file_name ); // Create image file name

                            // Check folder permission and define file location
                            if( wp_mkdir_p( $upload_dir['path'] ) ) {
                                $file = $upload_dir['path'] . '/' . $filename;
                            } else {
                                $file = $upload_dir['basedir'] . '/' . $filename;
                            }

                            // Create the image  file on the server
                            file_put_contents( $file, $image_data );

                            // Check image file type
                            $wp_filetype = wp_check_filetype( $filename, null );

                            // Set attachment data
                            $attachment = array(
                                'post_mime_type' => $wp_filetype['type'],
                                'post_title'     => sanitize_file_name( $filename ),
                                'post_content'   => '',
                                'post_status'    => 'inherit'
                            );

                            // Create the attachment
                            $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

                            // Include image.php
                            require_once(ABSPATH . 'wp-admin/includes/image.php');

                            // Define attachment metadata
                            $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

                            // Assign metadata to attachment
                            wp_update_attachment_metadata( $attach_id, $attach_data );

                        }
                        set_post_thumbnail( $post_id, $attach_id );
                        add_post_meta($post_id, '_thumbnail_id', $attach_id);
                    }

                    wp_set_object_terms($post_id, 'simple', 'product_type');

                    update_post_meta( $post_id, '_visibility', 'visible' );
                    update_post_meta( $post_id, '_stock_status', 'instock');
                    update_post_meta( $post_id, 'total_sales', '0');
                    update_post_meta( $post_id, '_downloadable', 'no');
                    update_post_meta( $post_id, '_virtual', 'no');
                    update_post_meta( $post_id, '_regular_price', $res[1] );
                    update_post_meta( $post_id, '_sale_price', $res[0] );
                    update_post_meta( $post_id, '_purchase_note', "" );
                    update_post_meta( $post_id, '_featured', "no" );
                    update_post_meta( $post_id, '_weight', "" );
                    update_post_meta( $post_id, '_length', "" );
                    update_post_meta( $post_id, '_width', "" );
                    update_post_meta( $post_id, '_height', "" );
                    update_post_meta( $post_id, '_sku', "");
                    update_post_meta( $post_id, '_product_attributes', array());
                    update_post_meta( $post_id, '_sale_price_dates_from', "" );
                    update_post_meta( $post_id, '_sale_price_dates_to', "" );
                    update_post_meta( $post_id, '_price', $res[0] );
                    update_post_meta( $post_id, '_sold_individually', "" );
                    update_post_meta( $post_id, '_manage_stock', "no" );
                    update_post_meta( $post_id, '_backorders', "no" );
                    update_post_meta( $post_id, '_stock', "" );
                    update_post_meta( $post_id, '_download_limit', '');
                    update_post_meta( $post_id, '_download_expiry', '');
                    update_post_meta( $post_id, '_download_type', '');
                    if(isset($_POST['data'][$i]['extimage'])){
                        $extimagesize= sizeof($_POST['data'][$i]['extimage']);
                        $imgids= array();
                        for($loop =1;$loop <= $extimagesize;$loop++){
                            $image_url=   $_POST['data'][$i]['extimage'][$loop];
                            $image_name       = $image_url;
                            $upload_dir       = wp_upload_dir(); // Set upload folder
                            $image_data       = file_get_contents($image_url); // Get image data
                            $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
                            $filename         = basename( $unique_file_name ); // Create image file name

                            // Check folder permission and define file location
                            if( wp_mkdir_p( $upload_dir['path'] ) ) {
                                $file = $upload_dir['path'] . '/' . $filename;
                            } else {
                                $file = $upload_dir['basedir'] . '/' . $filename;
                            }

                            // Create the image  file on the server
                            file_put_contents( $file, $image_data );

                            // Check image file type
                            $wp_filetype = wp_check_filetype( $filename, null );

                            // Set attachment data
                            $attachment = array(
                                'post_mime_type' => $wp_filetype['type'],
                                'post_title'     => sanitize_file_name( $filename ),
                                'post_content'   => '',
                                'post_status'    => 'inherit'
                            );

                            // Create the attachment
                            $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

                            // Include image.php
                            require_once(ABSPATH . 'wp-admin/includes/image.php');

                            // Define attachment metadata
                            $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

                            // Assign metadata to attachment
                            wp_update_attachment_metadata( $attach_id, $attach_data );
                            array_push($imgids, $attach_id);
                        }
                        
                        $join_string=implode(", ", $imgids);
                        update_post_meta( $post_id, '_product_image_gallery', $join_string);
                    }
                    else{
                        update_post_meta( $post_id, '_product_image_gallery', '');
                    }
                       
                } 
                if($post_id){
                    echo 'Products Uploaded Successfully';
                }
                else{
                    echo 'Product Upload Failed';
                }
            }
            else if($_POST['ptype'] == 'post'){
                for($i=0;$i<$TotalProducts;$i++){
                    if(isset($_POST['data'][$i]['content'])){
                            
                            $content=$_POST['data'][$i]['content'];
                    }
                    else{
                            
                        $content="";
                    }
                    if(isset($_POST['data'][$i]['title'])){
                            
                        $title=$_POST['data'][$i]['title'];
                    }
                    else{
                            
                        $title="none";
                    }
                    global $user_ID;
                    $new_post = array(
                    'post_title' => $title,
                    'post_content' => $content,
                    'post_status' => 'publish',
                    'post_date' => date('Y-m-d H:i:s'),
                    'post_author' => $user_ID,
                    'post_type' => 'post',
                    'post_category' => array(0)
                    );
                    $post_id = wp_insert_post($new_post);
                    if(isset($_POST['data'][$i]['image'])){
                                $image_url=   $_POST['data'][$i]['image'];
                                $image_name       = $image_url;
                                $upload_dir       = wp_upload_dir(); // Set upload folder
                                $image_data       = file_get_contents($image_url); // Get image data
                                $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
                                $filename         = basename( $unique_file_name ); // Create image file name

                                // Check folder permission and define file location
                                if( wp_mkdir_p( $upload_dir['path'] ) ) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }

                                // Create the image  file on the server
                                file_put_contents( $file, $image_data );

                                // Check image file type
                                $wp_filetype = wp_check_filetype( $filename, null );

                                // Set attachment data
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name( $filename ),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );

                                // Create the attachment
                                $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

                                // Include image.php
                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                // Define attachment metadata
                                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

                                // Assign metadata to attachment
                                wp_update_attachment_metadata( $attach_id, $attach_data );
                                set_post_thumbnail( $post_id, $attach_id );
                            }
                            
                    if($post_id){
                        echo 'Products Uploaded Successfully';
                    }
                    else{
                        echo 'Product Upload Failed';
                    }
                }
            }
            die();
        }

        public function custom_scrapper_setting(){
            ?>
                 <style type="text/css">
                .spinner {
                    position: fixed;
                    visibility: visible !important;
                    top: 50%;
                    left: 50%;
                    margin-left: -50px; /* half width of the spinner gif */
                    margin-top: -50px; /* half height of the spinner gif */
                    text-align:center;
                    z-index:1234;
                    overflow: auto;
                    width: 100px; /* width of the spinner gif */
                    height: 102px; /*hight of the spinner gif +2px to fix IE8 issue */
                }

            </style>
                <div id="spinner" class="spinner" style="display: none;">
                    <img id="img-spinner" src="//localhost/wordpress/wp-content/plugins/wp_scraper/admin/style/805.gif" alt="Loading" height="42" width="42"/>
                </div>

            <div id='section' class='bootstrap-iso'>
                <h1>Extendons Custom Site Scrapper</h1><br>
                 <p><i><?php _e('Start Id name with "#" and Class name with "."','wps_text');?></i></p>
                <div class="form-inline">
                    <label for="c_id" class="col-2 col-form-label" style="width: 16%;"><?php _e('Container Id/Class:','wps_text');?></label>
                    <input type="text" class="form-control mb-2 mr-sm-2 mb-sm-0" style="width: 20%;" id="c_id" name='c_id'>
                     <a href="<?php echo plugin_dir_url(__FILE__); ?>assets/images/Container/Container.gif" target="_blank" rel="noopener noreferrer"><?php _e('Help','wps_text');?></a>
                   
                </div>
                <br>

                <div class="form-inline">
                    <label for="c_title" class="col-2 col-form-label" style="width: 16%;"><?php _e('Product Title Id/Class:','wps_text');?></label>
                    <input type="text" class="form-control mb-2 mr-sm-2 mb-sm-0" style="width: 20%;" id="c_title" name='c_title'>
                     <a href="<?php echo plugin_dir_url(__FILE__); ?>assets/images/Title/Title.gif" target="_blank" rel="noopener noreferrer"><?php _e('Help','wps_text');?></a>
                   
                </div>
                <br>
                <div class="form-inline">
                    <label for="c_price" class="col-2 col-form-label" style="width: 16%;"><?php _e('Product Price Id/Class:','wps_text');?></label>
                    <input type="text" class="form-control mb-2 mr-sm-2 mb-sm-0" style="width: 20%;" id="c_price" name='c_price'>
                     <a href="<?php echo plugin_dir_url(__FILE__); ?>assets/images/Price/Price.gif" target="_blank" rel="noopener noreferrer"><?php _e('Help','wps_text');?></a>
                   
                </div>
                <br>
                <div class="form-inline">
                    <label for="c_desp" class="col-2 col-form-label" style="width: 16%;"><?php _e('Product Description Id/Class:','wps_text');?></label>
                    <input type="text" class="form-control mb-2 mr-sm-2 mb-sm-0" style="width: 20%;" id="c_desp" name='c_desp'>
                     <a href="<?php echo plugin_dir_url(__FILE__); ?>assets/images/Description/Description.gif" target="_blank" rel="noopener noreferrer"><?php _e('Help','wps_text');?></a>
                   
                </div>
                <br>
                <div class="form-inline">
                    <label for="c_image" class="col-2 col-form-label" style="width: 16%;"><?php _e('Product Image Id/Class:','wps_text');?></label>
                    <input type="text" class="form-control mb-2 mr-sm-2 mb-sm-0" style="width: 20%;" id="c_image" name='c_image'>
                     <a href="<?php echo plugin_dir_url(__FILE__); ?>assets/images/Image/Image.gif" target="_blank" rel="noopener noreferrer"><?php _e('Help','wps_text');?></a>
                   
                </div>
                <br>
                <div class="form-inline">
                    <label for="link" class="col-2 col-form-label" style="width: 16%;"><?php _e('Target link:','wps_text');?></label>
                    <input type="text" class="form-control mb-2 mr-sm-2 mb-sm-0" style="width: 40%;" id="link" name='link'>
                    <label  for="ddtype"><?php _e('Select Type','wps_text');?></label>
                    <select  id="ddtype" name='ddtype' required>
                        <option ><?php _e('Choose...','wps_text');?></option>
                      <!--   <option value="post">Single Post Page</option> -->
                        <option value="product"><?php _e('Single Product Page','wps_text');?></option>
                       <!--  <option value="shop">Shop/Category Page</option> -->
                    </select>
                    <button type="button" id='btnSeach' class="btn btn-primary" onclick="c_scrape();"><?php _e('Search','wps_text');?></button>
                </div>
                <br>
                <input type="hidden" id="frmdata" name='frmdata' value="">
                <div class="container" id='container' style='background-color: grey;display: none'>
                    <h3>Result:</h3>
                    <div class="well well-lg" id='data'></div>
                   <button type="Button" class="btn btn-success" style="margin-bottom: 10px;" id='import_data' onclick="ImportData('product');"><?php _e('Import As Product','wps_text');?></button>
                   <!-- <button type="Button" class="btn btn-success" style="margin-bottom: 10px;" id='import_data' onclick="ImportData('csv');">Import As CSV</button> -->
                </div>
               
            </div>
            <script type="text/javascript">
                 jQuery(document).ready(function(){
                    jQuery("#spinner").bind("ajaxSend", function() {
                        jQuery("#btnSeach").attr("disabled", true);
                        jQuery("#import_data").attr("disabled", true);
                        jQuery(this).show();
                    }).bind("ajaxStop", function() {
                        jQuery("#btnSeach").removeAttr("disabled");
                        jQuery("#import_data").removeAttr("disabled");
                        jQuery(this).hide();
                    }).bind("ajaxError", function() {
                        jQuery("#btnSeach").removeAttr("disabled");
                        jQuery("#import_data").removeAttr("disabled");

                        jQuery(this).hide();
                    });
             
                });
                function ImportData(type){
                    jQuery('#spinner').show();
                    var chkArray = [];
                    jQuery(".chk:checked").each(function() {
                        chkArray.push(jQuery(this).val());
                    });
                    // alert(chkArray.length);
                    var arraydata = [];
                    var chkArraySize= chkArray.length;
                    if(chkArraySize == 0){
                        alert('Please Select Product');
                        return;
                    }
                    for(var i=0;i<chkArraySize;i++){
                        if(chkArraySize == 1){
                            if(!jQuery.parseJSON(jQuery('#frmdata').val())[0].length){
                                arraydata.push(jQuery.parseJSON(jQuery('#frmdata').val())[0]);
                            }else{
                                arraydata.push(jQuery.parseJSON(jQuery('#frmdata').val())[0][chkArray]);
                            }
                        }else if(chkArraySize > 1){
                            arraydata.push(jQuery.parseJSON(jQuery('#frmdata').val())[0][chkArray[i]]);
                        }
                    }

                    console.log(arraydata)
                    arraydata.forEach((val,ind,arr)=>{
                        val.price = val.price.replace('&#36; ', '');
                        val.price = val.price.replace('&#36;', '');
                       
                    })


                    var ajaxurl = "<?php echo admin_url( 'admin-ajax.php'); ?>";
                    jQuery.ajax({
                        url : ajaxurl,
                        type : 'post',
                        data : {
                            action : 'post_data',
                            data :arraydata,
                            ptype:type,
                        },
                        success : function( response ) {
                            alert(response);
                            jQuery('#spinner').hide();

                        }
                    });  
                }
                function c_scrape() {

                    var link=document.getElementById("link").value;
                    var dtype=document.getElementById("ddtype").value;
                    var c_id=document.getElementById("c_id").value;
                    var title=document.getElementById("c_title").value;
                    var price=document.getElementById("c_price").value;
                    var desp=document.getElementById("c_desp").value;
                    var image=document.getElementById("c_image").value;
                    get_custom_data(link,dtype,c_id,title,price,desp,image);
                }

                function get_custom_data(link,dtype,c_id,title,price,desp,image) {
                    jQuery('#spinner').show();

                    var ajaxurl = "<?php echo admin_url( 'admin-ajax.php'); ?>";
                    jQuery.ajax({
                        url : ajaxurl,
                        type : 'post',
                        data : {
                            action : 'scrape_custom_data',
                            link :link,
                            dtype: dtype,
                            c_id :c_id,
                            title: title,
                            price :price,
                            desp: desp,
                            image :image,
                        },
                        success : function( response ) {
                            console.log('Love')
                            console.log(response)
                            var vae=jQuery.parseJSON(response);

                            jQuery('#frmdata').val(response);

                            //<input id="selectall" onclick="selectAll();" class="checkbox checkbox-primary styled" type="checkbox" >
                            htmldata='<table><tr><th width="50px">Select</th><th>Name</th><th>Price</th><th style="width:63%;">Content</th><th>Image</th></tr>';
                            
                            if(vae[1]=='product'){
                                htmldata+= '<tr><td><input id="0" value="0"class="chk checkbox checkbox-primary styled" type="checkbox"></td><td><p>'+vae[0].title+'</p></td><td><p >'+vae[0].price+'</p></td><td><p >'+vae[0].content+'</p></td><td><img id="pimage" width="100px" height="100px"src="'+vae[0].image+'" class="img-rounded"></td></tr></table>';
                                jQuery('#data').html(htmldata);
                            }
                            else if(vae[1]=='shop'){
                                var size=vae[0].length;
                                for(var i=0;i<size;i++){
                                 htmldata += '<tr><td><input id="'+i+'" value="'+i+'" class="chk checkbox checkbox-primary styled" type="checkbox"></td><td><label>'+vae[0][i].title+'</label></td><td><label >'+vae[0][i].price+'</label></td><td><label >'+vae[0][i].content+'</label></td><td><img id="pimage" width="100px" height="100px"src="'+vae[0][i].image+'" class="img-rounded"></td></tr>';   
                                }
                                jQuery('#data').html(htmldata+'</table>');
                            }
                            else if(vae[1]=='post'){

                            }
                            // console.log(jQuery.parseJSON(vae[0].variation));
                            jQuery('#spinner').hide();

                            jQuery('#container').show();
                        }
                    });  
                }

            </script>
            <?php
        }

        public function wp_scrapper_setting(){
            ?>
            <style type="text/css">
                .spinner {
                    position: fixed;
                    visibility: visible !important;
                    top: 50%;
                    left: 50%;
                    margin-left: -50px; /* half width of the spinner gif */
                    margin-top: -50px; /* half height of the spinner gif */
                    text-align:center;
                    z-index:1234;
                    overflow: auto;
                    width: 100px; /* width of the spinner gif */
                    height: 102px; /*hight of the spinner gif +2px to fix IE8 issue */
                }

            </style>
             <div id="spinner" class="spinner" style="display: none;">
                    <img id="img-spinner"  alt="Loading" height="42" width="42"/>
                </div>
            <div id='section' class='bootstrap-iso'>
               
                <h1>Extendons WP Scrapper</h1><br>
                <div class="form-inline">
                    <label for="link" class="col-2 col-form-label"><?php _e('Target link:','wps_text');?></label>
                    <input type="text" class="form-control mb-2 mr-sm-2 mb-sm-0" style="width: 40%;" id="link" name='link'>
                    <label  for="ddtype"><?php _e('Select Type','wps_text');?></label>
                    <select  id="ddtype" name='ddtype' required>
                        <option ><?php _e('Choose...','wps_text');?></option>
                        <option value="post"><?php _e('Single Post Page','wps_text');?></option>
                        <option value="product"><?php _e('Single Product Page','wps_text');?></option>
                        <option value="shop"><?php _e('Shop/Category Page','wps_text');?></option>
                    </select>
                    <button type="button" id='btnSeach' class="btn btn-primary" onclick="scrape();"><?php _e('Search','wps_text');?></button>
                </div>
                <br>
                <input type="hidden" id="frmdata" name='frmdata' value="">
                <div class="container" id='container' style='background-color: grey;display: none'>
                    <h3><?php _e('Result:','wps_text');?></h3>
                    <div class="well well-lg" id='data'></div>
                   <button type="Button" class="btn btn-success" style="margin-bottom: 10px;" id='import_data' onclick="ImportData();"><?php _e('Import As Product','wps_text');?></button>
                  <!--  <button type="Button" class="btn btn-success" style="margin-bottom: 10px;" id='import_data' onclick="ImportData('csv');">Import As CSV</button> -->
                </div>
               
            </div>

           
            <script type="text/javascript">
                jQuery(document).ready(function(){
                    jQuery("#spinner").bind("ajaxSend", function() {
                        jQuery("#btnSeach").attr("disabled", true);
                        jQuery("#import_data").attr("disabled", true);
                        jQuery(this).show();
                    }).bind("ajaxStop", function() {
                        jQuery("#btnSeach").removeAttr("disabled");
                        jQuery("#import_data").removeAttr("disabled");
                        jQuery(this).hide();
                    }).bind("ajaxError", function() {
                        jQuery("#btnSeach").removeAttr("disabled");
                        jQuery("#import_data").removeAttr("disabled");

                        jQuery(this).hide();
                    });
             
                });

                function scrape() {

                    var link=document.getElementById("link").value;
                    var dtype=document.getElementById("ddtype").value;
                    get_data(link,dtype);
                }
                
                function ImportData(){
                    var type=jQuery.parseJSON(jQuery('#frmdata').val())[1];
                    var chkArray = [];
                    jQuery(".chk:checked").each(function() {
                        chkArray.push(jQuery(this).val());
                    });
                    // alert(chkArray.length);
                    var arraydata = [];
                    var chkArraySize= chkArray.length;
                    if(chkArraySize == 0){
                        alert('Please Select Product');
                        return;
                    }
                    for(var i=0;i<chkArraySize;i++){
                        if(chkArraySize == 1){
                            if(!jQuery.parseJSON(jQuery('#frmdata').val())[0].length){
                                arraydata.push(jQuery.parseJSON(jQuery('#frmdata').val())[0]);
                            }else{
                                arraydata.push(jQuery.parseJSON(jQuery('#frmdata').val())[0][chkArray]);
                            }
                        }else if(chkArraySize > 1){
                            arraydata.push(jQuery.parseJSON(jQuery('#frmdata').val())[0][chkArray[i]]);
                        }
                    }

                    console.log(arraydata)
                    arraydata.forEach((val,ind,arr)=>{
                        val.price = val.price.replace('&#36; ', '');
                        val.price = val.price.replace('&#36;', '');
                       
                    })
                    var ajaxurl = "<?php echo admin_url( 'admin-ajax.php'); ?>";
                    jQuery.ajax({
                        url : ajaxurl,
                        type : 'post',
                        data : {
                            action : 'post_data',
                            data :arraydata,
                            ptype:type,
                        },
                        success : function( response ) {
                            alert(response);
                        }
                    });  
                }
                
                function selectAll(){
                    jQuery("#selectall").click(function(){
                        jQuery('input:checkbox').prop('checked', this.checked);
                    });
                }
                function get_data(link,dtype) {
                    jQuery('#spinner').show();
                    var ajaxurl = "<?php echo admin_url( 'admin-ajax.php'); ?>";
                    jQuery.ajax({
                        url : ajaxurl,
                        type : 'post',
                        data : {
                            action : 'scrape_data',
                            link :link,
                            dtype: dtype,
                        },
                        success : function( response ) {
                            var vae=jQuery.parseJSON(response);
                            if (undefined == vae[0].content) {
                                vae[0].content = '';
                            }

                            if(vae[1]=='product'){
                                let image = jQuery(vae[0].image)[0]
                                vae[0].image = jQuery(image).attr('data-src')
                            }

                            if(vae[1]=='shop'){
                               vae[0].forEach((val2,ind2,arr2)=>{
                                let image = jQuery(val2.image)[0]
                                val2.image = jQuery(image).attr('data-src')
                               }) 
                            }

                            response = JSON.stringify(vae)

                            jQuery('#frmdata').val(response);
                            
                            if(vae[1]=='product'){
                                htmldata='<table><tr><th width="30px"><input id="selectall" onclick="selectAll();" class="checkbox checkbox-primary styled" type="checkbox" ></th><th width>Name</th><th>Price</th><th style="width: 63%;">Content</th><th>Image</th></tr>';
                                htmldata+= '<tr><td><input id="0" value="0"class="chk checkbox checkbox-primary styled" type="checkbox"></td><td><p>'+vae[0].title+'</p></td><td><p >'+vae[0].price+'</p></td><td><p >'+vae[0].content+'</p></td><td><img id="pimage" width="100px" height="100px"src="'+vae[0].image+'" class="img-rounded"></td></tr></table>';
                                jQuery('#data').html(htmldata);
                            }
                            else if(vae[1]=='shop'){
                                htmldata='<table><tr><th width="30px"><input id="selectall" onclick="selectAll();" class="checkbox checkbox-primary styled" type="checkbox" ></th><th>Name</th><th>Price</th><th style="width: 63%;">Content</th><th>Image</th></tr>';
                                var size=vae[0].length;
                                for(var i=0;i<size;i++){

                                    if (undefined == vae[0][i].content) {
                                        vae[0][i].content = '';
                                    }
                                 htmldata += '<tr><td><input id="'+i+'" value="'+i+'" class="chk checkbox checkbox-primary styled" type="checkbox"></td><td><p>'+vae[0][i].title+'</p></td><td><p >'+vae[0][i].price+'</p></td><td><p >'+vae[0][i].content+'</p></td><td style="padding-bottom: 10px;"><img id="pimage" width="100px" height="100px"src="'+vae[0][i].image+'" class="img-rounded"></td></tr>';   
                                }
                                jQuery('#data').html(htmldata+'</table>');
                            }
                            else if(vae[1]=='post'){
                                htmldata='<table><tr><th width="30px"><input id="selectall" onclick="selectAll();" class="checkbox checkbox-primary styled" type="checkbox" ></th><th>Title</th><th style="width: 63%;">Content</th><th>Image</th></tr>';
                                htmldata+= '<tr><td><input id="0" value="0"class="chk checkbox checkbox-primary styled" type="checkbox"></td><td><p>'+vae[0].title+'</p></td><td><p >'+vae[0].content+'</p></td><td><img id="pimage" width="100px" height="100px"src="'+vae[0].image+'" class="img-rounded"></td></tr></table>';
                                jQuery('#data').html(htmldata);

                            }
                            // console.log(jQuery.parseJSON(vae[0].variation));
                            jQuery('#container').show();
                            jQuery('#spinner').hide();

                        },
                        error:function(err) {
                            console.log(err)
                            jQuery('#spinner').hide();
                            alert('couldnt scrape the page')
                        }
                    });

                }
            </script>
            <?php
        }
        
    
    }
    new Extendons_Wp_Scrapper_admin();