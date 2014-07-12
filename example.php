<?php
require 'demo.class.php';
$gc = new Goodscloud("http://sandbox.goodscloud.com", "me@mycompany.com", "PASSWORD");

function getProduct($gc, $gtin){
  return $gc->get('/api/internal/company_product',  array(
    "q" => json_encode(array(
      "filters" => array(
        array("name" => "gtin", "op" => "eq", "val" => $gtin)
      )
    ))
  ));
}

$product = getProduct($gc, "00845982004017");

var_dump($product);
