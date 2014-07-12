<?php
require 'demo.class.php';
$product = $gc->getProduct("00845982004017");
$gc = new Goodscloud("http://sandbox.goodscloud.com", "me@mycompany.com", "PASSWORD");

var_dump($product);
