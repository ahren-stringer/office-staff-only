<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if(!CModule::IncludeModule("iblock")) die();
if(!CModule::IncludeModule('catalog')) die();
if(!CModule::IncludeModule('main')) die();

$path = $_SERVER["DOCUMENT_ROOT"].'/bitrix/import/export_partner.xml';

$main_block_id= 35;

$parsedData=array();
$processedData=array();


$xmldata = simplexml_load_file($path) or die("Failed to load");

$categories=$xmldata->shop->categories->category[1];
$products = $xmldata -> shop -> offers->offer;

$pod_cat_obj = [
    'obj_id' => 0,
    'parent_id_xml'=> 0,
    'parent_id_bitrix' => 0
];


//$cats = array();
//$cats_ID = array();
////В цикле формируем массив разделов, ключом будет id родительской категории, а также массив разделов, ключом будет id категории
//foreach($categories as $cat){
//
//    $atts_obj = (array) $cat -> attributes();
//    $atts_array =  $atts_obj['@attributes'];
//
//    $parentId=$atts_array['parentId'];
//
//    if( !$atts_array['parentId'] ){
//        $parentId='root';
//    }
//
//    $cats[$parentId]['cats'][$atts_array['id']] = $cat;
//
//}

//foreach($products as $prod){
////  $count =( (array) $prod->model)[0];
//
////      echo '<pre>';
////      print_r($count );
////      echo '<pre>';
//
//$cat_id = ((array) $prod->categoryId)[0];
//
//    $cats[$cat_id]['prods'][] = $prod;
//}

function create_category($cat, $bitrix_parent_cat_id = ''){
   // echo $step.( (array) $cat)[0].'<br>';
    $cat_name = ( (array) $cat)[0];
    $cat_id = ( (array) $cat-> attributes())['@attributes']['id'];
    $parent_id = ( (array) $cat-> attributes())['@attributes']['parentId'];

   // file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/bitrix/import/log.txt', $cat_name . "\r\n", FILE_APPEND);

    $main_block_id =35;

    $bs = new CIBlockSection;

    $ID = 0;

    $arParams = array("replace_space"=>"-","replace_other"=>"-");
    $arFields = Array(
        "IBLOCK_ID" => $main_block_id,
        "NAME" => $cat_name,
        "CODE" => CUtil::translit($cat_name, 'ru', $arParams),
        "XML_ID" => $cat_id,
    );

    $existed_cat = CIBlockSection::GetList(Array(), $arFields, false, false, array("*"));

    echo '<pre>';
    echo $cat_id.'<br>';
    if ($parent_id){
        $parent = CIBlockSection::GetList(Array(), array("XML_ID" => $parent_id,"IBLOCK_ID" => $main_block_id), false, false, array("*"));
        if($pr = $parent->Fetch()){
            echo $parent_id.' - '.$cat_id;
//        print_r($parent);
//        print_r($pr);
            $arFields["IBLOCK_SECTION_ID"]=$pr["ID"];
        }
    }

    if($existed_cat_ob = $existed_cat->Fetch()){

        $bs->Update($existed_cat_ob['ID'], $arFields);
        $ID=  $existed_cat_ob['ID'];

    } else {
   // echo $cat_id;
    $ID = $bs->Add($arFields);
    $res = ($ID>0);

    }

    if(!$res)
        echo $bs->LAST_ERROR;

}

//create_category($xmldata->shop->categories->category[2]);

function create_product($prod, $bitrix_cat_id=''){
    //echo $step.((array) $prod->model)[0].'<br>';
echo '<pre>';
   // print_r($prod);

    global $USER;

    $prod_name = ((array) $prod->model)[0];
    $img_link= ((array) $prod->picture)[0];
    $price= (float) ((array) $prod->price)[0];
    $currency= ((array) $prod->currencyId)[0];
    $categoryId= ((array) $prod->categoryId)[0];
    $prod_id = ( (array) $prod-> attributes())['@attributes']['id'];

//    $prod_name = iconv('utf-8', 'windows-1251', $prod_name);
//    file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/bitrix/import/log.txt', $prod_name . "\r\n", FILE_APPEND);

    $main_block_id = 35;

    $el = new CIBlockElement;

    $arLoadProductArray = Array(
        //"MODIFIED_BY"    => $USER->GetID(), // элемент изменен текущим пользователем
        "IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
        "IBLOCK_ID"      => $main_block_id,
        //"PROPERTY_VALUES"=> $PROP,
        "NAME"           => $prod_name,
        "ACTIVE"         => "Y",            // активен
        //"PREVIEW_TEXT"   => "текст для списка элементов",
        //"DETAIL_TEXT"    => "текст для детального просмотра",
        "DETAIL_PICTURE" => CFile::MakeFileArray($img_link),
        "PREVIEW_PICTURE" => CFile::MakeFileArray($img_link),
        "CODE" => CUtil::translit($prod_name, 'ru', array("replace_space"=>"-","replace_other"=>"-")),
    );

    if ($categoryId){
        $category = CIBlockSection::GetList(Array(), array("XML_ID" => $categoryId,"IBLOCK_ID" => $main_block_id), false, false, array("*"));
        if($pr = $category->Fetch()){
            echo $prod_id.' - '.$pr["ID"];
            //print_r($pr);
            $arLoadProductArray["IBLOCK_SECTION_ID"]=$pr["ID"];
        }
    }

    $arFields = Array(
        "IBLOCK_ID" => $main_block_id,
        "NAME" => $prod_name,
    );

    $res = CIBlockElement::GetList(Array(), $arFields, false, false, array("*"));
//
//    $PRODUCT_ID = 0;
//
    if($ob = $res->Fetch())
    {
        //print_r($ob);
        //$el->Update($ob['ID'], $arLoadProductArray);
//        $PRODUCT_ID=$ob['ID'];
//        $tutu = CPrice::SetBasePrice($PRODUCT_ID, $price,'RUB',false,false,true);
//        print_r(CCatalogProduct::GetOptimalPrice($prod_id));
//        CCatalogProduct::setPriceVatIncludeMode(true);
//        //var_dump($r);
        CIBlockElement::Delete($ob['ID']);

        $PRODUCT_ID = $el->Add($arLoadProductArray);
        echo $el->LAST_ERROR;
        echo "New ID: ".$PRODUCT_ID;
        $tutu = CPrice::SetBasePrice($PRODUCT_ID, $price,'RUB',false,false,true);
        \Bitrix\Catalog\Model\Product::add(array("ID" => $PRODUCT_ID,VAT_INCLUDED => "Y"));

    }
    else
    {
        $PRODUCT_ID = $el->Add($arLoadProductArray);
        echo $el->LAST_ERROR;
        echo "New ID: ".$PRODUCT_ID;
         $tutu = CPrice::SetBasePrice($PRODUCT_ID, $price,'RUB',false,false,true);
    }

    if($PRODUCT_ID == 0){
        echo "Error: ".$el->LAST_ERROR;
    }


}

//create_product($xmldata -> shop -> offers->offer[0]);


//function import($cats, $branch_arr, $bitrix_parent_cat_id='', $step=0){
//    ++$step;
//    $br_cats = $branch_arr['cats'];
//    $br_prods = $branch_arr['prods'];
//    if( !empty($br_cats) ){
//        foreach ($br_cats as $cat_id => $cat_obj){
//
//            $bitrix_cat_id = create_category($cat_obj, $bitrix_parent_cat_id,$step);
//
//            import($cats, $cats[$cat_id], $bitrix_cat_id,$step);
//
//        }
//    }
//    if( !empty($br_prods) ){
//        foreach ($br_prods as $prod_id => $prod_obj){
//            create_product($prod_obj, $bitrix_parent_cat_id, $step);
//        }
//    }
//    $step=0;
//
//}
//
//import($cats,$cats['526']);

// $xmldata->shop->categories->category[1]->attributes()

$all_cats = (array) $xmldata->shop->categories->category[1]->attributes();
echo '<pre>';
//foreach($xmldata->shop->categories->category as $cat){
//   create_category($cat);
//}

//foreach($xmldata -> shop -> offers->offer as $prod){
//   create_product($prod);
//}

$prods = $xmldata -> shop -> offers->offer;

//for ($i = 0; $i < 500; $i++)
//{
//    create_product($prods[$i]);;
//}


$arFields = Array(
    "IBLOCK_ID" => 35,
    "ID" => 637,
);

$res = CIBlockElement::GetList(Array(), $arFields, false, false, array("*"));
print_r( (int) date_create(date("Y-m-d",$res->Fetch()['TIMESTAMP_X_UNIX']))->format('m'));
//print_r(count($xmldata -> shop -> offers->offer));
echo '</pre>';

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>