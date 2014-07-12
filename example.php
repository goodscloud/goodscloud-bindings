<?php
require 'goodscloud.class.php';
$gc = new Goodscloud("http://sandbox.goodscloud.com", "me@mycompany.com", "PASSWORD");

function getProduct($gc, $gtin){
  return $gc->get('/api/internal/company_product',  array(
    'q'=> array(
      'filters' => array(
        array('name' => 'gtin', 'op' => 'eq', 'val' => $gtin)
      )
    ),
    'flat' => true,
  ));
}

$product = getProduct($gc, '00845982004017');
var_dump($product);
