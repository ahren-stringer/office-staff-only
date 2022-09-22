<?php
/*
Template Name: excel
*/
?>

<?php

include __DIR__."/excel_parse/simplexlsx-master/src/SimpleXLSX.php";

use Shuchkin\SimpleXLSX;
echo "<pre>";

$brands_terms=get_terms(array(
    
    'taxonomy'=>'product_brand'
    
    ));



$brands_arr=[];

foreach($brands_terms as $brand){
    $brands_arr[$brand->term_id]=$brand->name;
}

print_r($brands_arr);

if ( $xlsx = SimpleXLSX::parse( __DIR__.'/excel_parse/Moskva Blank zakaza 140622.xlsx') ) {
    
    $arr=$xlsx->rows();
    
    $found_key = array_search('Штрихкод', array_column($xlsx->rows(), '1'));
     
    $unic_arr=[]; 
     
    foreach($brands_arr as $key=>$brand){
        
        $brand_row_id=0;
        
        for ($i = $found_key; $i < count($arr); $i++){
            if(trim($arr[$i][6])==$brand){
                $brand_row_id=$i;
                break;
            }
        }
        
        while($arr[$brand_row_id+1][2]!=''){
            $arr[$brand_row_id][17]=$key;
            ++$brand_row_id;
        }
    }
    
   // print_r($arr);


    for ($i = $found_key+1; $i < count($arr); $i++){
//------ Рассчет скидки ------------------------------------------------------------------------------------
        
        if($arr[$i][1]!=''&&$arr[$i][8]!=''){
           preg_match('/Скидка (\d+)%/', $arr[$i][8], $matches, PREG_OFFSET_CAPTURE);
            $arr[$i][18]=$matches[1][0];
            $arr[$i][19]=$arr[$i][14]-$arr[$i][14]*($matches[1][0]/100);
        }

//------ Конец - Рассчет скидки ------------------------------------------------------------------------------------
        
        
         for ($j = $i+1; $j <= count($arr); $j++){
             
            if($arr[$i][2]===$arr[$j][2]&&$arr[$i][2]!=''){
              if (strlen($arr[$i][6])<strlen($arr[$j][6])){
                $unic_arr[]=$arr[$i];
                break;
            }else if(strlen($arr[$i][6])>strlen($arr[$j][6])){
                $unic_arr[$j]=$arr[$j];
                break;
            }
            } 
         }
         
         if(!array_search($arr[$i][2], array_column($unic_arr, '2')) && $arr[$i][2]!=''){
             $unic_arr[$i]=$arr[$i];
         }

    }

  // print_r($unic_arr);

//------ Меняем данные о товарах ------------------------------------------------------------------------------------
    
    
    $inf_brands=array(
    	'BELOVE' =>483,
    	'ESTHETIC HOUSE'=>472,
    	'CELRANICO'=>478,
    	'COSIMA'=> 486,
    	'CHAMBERY'=>487,	
    	'HANIL'=>476,
    	'MISSHA'=> 414,
    	'STEM FARM'=>512,
    	'SECRET KEY'=>477,	
    	'3W CLINIC'=>515,
    );
    $sert_tag_id=470;
    
    /*$pt = wc_get_product_id_by_sku('АРМ 46');
    print_r(wc_get_product($pt));
   
    $brands=array();
    
    foreach($brands_arr as $brand_id=>$brand){
     
        $args = array(
        'post_type'             => 'product',
        'posts_per_page'        => '30',
        'tax_query'             => array(
                array(
                    'taxonomy'      => 'product_brand',
        			'field'    => 'id',
        			'terms'         => $brand_id,
                ),
            )
        );
        
        $prods = new WP_Query($args);
    
    	foreach ($prods->posts as $prod){
    		$ids[]=$prod->ID;
    	}
        $brands[$brand_id]=$ids;
    }*/
    
   // print_r($brands);
    
    $args = array(
        'post_type'             => 'product',
        'posts_per_page'        => '2000',
    	'paged'          => 4,
    );
    
    $prods = new WP_Query($args);
    
   foreach($prods->posts as $prod){    
        
        $prod=wc_get_product($prod->ID);
        $pt_id = $prod->get_id();
        
        $ruchnoy_brand=false;
    
        foreach($inf_brands as $brand){
    		if (
    		    get_the_terms($pt_id,'product_brand')[0]->term_id == $brand 
    		    
    		    || get_the_terms($pt_id,'product_cat')[0]->term_id==$sert_tag_id
    		    
    		     ){
    			$ruchnoy_brand=true;
    		}
    	}
    	
    	$prod_iz_unic_arr;
    	
    	foreach($unic_arr as $item){
    	    if ($item[2] == $prod->get_sku()){
    	        $prod_iz_unic_arr=$item;
    	        break;
    	    }else{
    	        $prod_iz_unic_arr=false;
    	    }
    	}
    	
    	if($prod_iz_unic_arr){
    	    
    	    update_post_meta($pt_id,'_stock_status','instock');
     		update_post_meta($pt_id,'_manage_stock','yes');
    		update_post_meta($pt_id,'_stock',(int)$prod_iz_unic_arr[9]);
    		
    		update_post_meta($pt_id, '_regular_price', (int)$prod_iz_unic_arr[14]);
    		if($prod_iz_unic_arr[19]){
    		    update_post_meta($pt_id, '_sale_price', (int)$prod_iz_unic_arr[19]);
    	        update_post_meta($pt_id, '_price', (int)$prod_iz_unic_arr[19]);   
    		}else{
    		    update_post_meta($pt_id, '_price', (int)$prod_iz_unic_arr[14]);
    		    update_post_meta($pt_id, '_sale_price', '');
    		}
    	    
    	}
        else if( !$ruchnoy_brand ){
    		update_post_meta($pt_id,'_stock_status','outofstock');
     		update_post_meta($prod->get_id(),'_manage_stock','yes');
    		update_post_meta($pt_id,'_stock',0);
    	}
    	else{
    		update_post_meta($pt_id,'_stock_status','instock');
     		update_post_meta($prod->get_id(),'_manage_stock','no');
    	}
    	
    	var_dump($prod_iz_unic_arr);
    	echo ($prod_iz_unic_arr_key).' - да да<br>';
        print_r($prod->get_sku());
    	
    }
    
//------ Конец - Меняем данные о товарах ------------------------------------------------------------------------------------    
    
} else {
    echo SimpleXLSX::parseError();
}

?>
