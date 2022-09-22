<?php
/*
Template Name: Для фоток
*/
?>

<?php
echo "<pre>";

function create_img($ftp,$image_name){
	$upload_dir = wp_upload_dir();
	
	if ( wp_mkdir_p( $upload_dir['path'] ) ) {
	  $file = $upload_dir['path'] . '/' . $image_name;
	}
	else {
	  $file = $upload_dir['basedir'] . '/' . $image_name;
	}
	echo $file;
	
	$img = ftp_get($ftp, $file, '/4Clients/'.$image_name, FTP_BINARY);

	// проверка результата
	if (!$img) {
		echo "Не удалось закачать файл!";
		var_dump($img);
	} else {
		echo "Файл закачан";
	}


	$wp_filetype = wp_check_filetype( $image_name, null );

	$attachment = array(
	  'post_mime_type' => $wp_filetype['type'],
	  'post_title' => sanitize_file_name( $image_name ),
	  'post_content' => '',
	  'post_status' => 'inherit'
	);
	
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	
	$attach_id = wp_insert_attachment( $attachment, $file );
	$imagenew = get_post( $attach_id );
	$fullsizepath = get_attached_file( $imagenew->ID );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
	wp_update_attachment_metadata( $attach_id, $attach_data ); 
	
	return $attach_id;
}


$imgs=scandir(ABSPATH . 'wp-content/product_img');
$img_dir='https://dutybeauty.ru/wp-content/product_img/';


$uploads=scandir($upload_dir['path']);


$args = array(
    'post_type'             => 'product',
    'posts_per_page'        => '2000',
	'paged'          => 4,
);



$prods = new WP_Query($args);


//Импорт фоток

////////////////////////////////////////////////////////////////

$filename = "8809657116155_3.jpg";

$ftp = ftp_connect('77.91.121.70',21021);

$login_result = ftp_login($ftp, 'keauty_1', 'St09Lk7$');
ftp_pasv($ftp, true);
// проверка соединения
if ((!$ftp) || (!$login_result)) {
    echo "Не удалось установить соединение с FTP-сервером! <br>";
    exit;
} else {
    echo "Установлено соединение с FTP сервером <br>";
}
$ftp_files=ftp_nlist($ftp,'/4Clients/');

foreach($prods->posts as $prod){
	$prod=wc_get_product($prod->ID);
	$barcode='/'.$prod->get_meta('barcode').'/';
	$main_img='/'.$prod->get_meta('barcode').'\./';
	$gall_item='/'.$prod->get_meta('barcode').'_/';
	
	$f=true;

	foreach($uploads as $img){
		if(preg_match($barcode,$img)){
				$f=false;
		}
	}

	if($f && !$prod->get_image_id()){
echo 'lllll';
		$gall_id_arr=array();
		$image_id=0;
		foreach ($ftp_files as $img) {
			if(preg_match($main_img,$img)){
				$image_id= create_img($ftp,basename( $img ));
			}
			if(preg_match($gall_item,$img)){
				$gall_id_arr[]=create_img($ftp,basename( $img ));
			}
		}
		if(!$image_id){
			$image_id=array_shift($gall_id_arr);
		}
		set_post_thumbnail($prod->get_id(),$image_id);
		update_post_meta($prod->get_id(),'_product_image_gallery',implode(',',$gall_id_arr));
	}
	else{
	echo "<pre>";
	print_r('Есть такое');
	print_r($barcode);
	print_r($main_img);
	print_r($gall_item);
}
}

// закрытие соединения
ftp_close($ftp);

/////////////////////////////////////////////////////////////////////////


// foreach($prods->posts as $prod){
// 	$prod=wc_get_product($prod->ID);
// 	$barcode='/'.$prod->get_meta('barcode').'/';
// 	$main_img='/'.$prod->get_meta('barcode').'\./';
// 	$gall_item='/'.$prod->get_meta('barcode').'_/';
	
// 	$f=true;

// 	foreach($uploads as $img){
// 		if(preg_match($barcode,$img)){
// 				$f=false;
// 		}
// 	}

// 	if($f && !$prod->get_image_id()){

// 		$gall_id_arr=array();
// 		$image_id=0;
// 		foreach ($imgs as $img) {
// 			if(preg_match($main_img,$img)){
// 				$image_id=create_img($img_dir.$img);
// 			}
// 			if(preg_match($gall_item,$img)){
// 				$gall_id_arr[]=create_img($img_dir.$img);
// 			}
// 		}
// 		if(!$image_id){
// 			$image_id=array_shift($gall_id_arr);
// 		}
// 		set_post_thumbnail($prod->get_id(),$image_id);
// 		update_post_meta($prod->get_id(),'_product_image_gallery',implode(',',$gall_id_arr));

// // 		echo "<pre>";
		
// // 		print_r($barcode);
// // 		print_r($main_img);
// // 		print_r($gall_item);
		
// // 		print_r($image_id);
// // 		print_r($gall_id_arr);
// // 		print_r($prod);
// // 		print_r($prod->get_id());
// 	}
// 	else{
// 	echo "<pre>";
// 	print_r('Есть такое');
// 	print_r($barcode);
// 	print_r($main_img);
// 	print_r($gall_item);
// }
// }
 
// Конец - импорт фоток
?>
