<?php
class Goodscloud{
  private $gtin = null;
  private $host;
  private $email;
  private $password;
  private $port;
  private $session;

  public function __construct($host, $email, $password){  // pass global trade item number
    date_default_timezone_set('Europe/Berlin');
    $this->host = $host;
    $this->email = $email;
    $this->password = $password;
    $this->port = strpos($this->host, "https") == 0 ? 443 : 80;
    $this->getSession();
  }

  private function getSession(){
    $this->session = $this::http_request_curl('POST', $this->host, $this->port, '/session', array('email'=>$this->email,'password' => $this->password));
    if (!isset($this->session) || $this->session->email != $this->email){
      throw new Exception("API credentials incorrect", 1);
    }
  }

  public function getProduct($gtin){
    $this->gtin = $gtin;
    $query = json_encode(array("filters" => array( array("name" => "gtin", "op" => "eq", "val" => $this->gtin))));
    $product = $this->signed_request('/api/internal/company_product', 'GET', array("q" => $query), $this->session->auth);
    if (!isset($product) || $product->num_results < 1){
      throw new Exception("No product with id $gtin", 1);
    }
    return $product;
  }

  private static function http_request_curl($method,$host,$port,$path,$params){
    // Initialize session.
    $ch = curl_init();
    $request_params = '';
    foreach ($params as $k => $v) {
      $request_params .= urlencode($k) .'='. urlencode($v) .'&';
    }
    $request_params = substr($request_params, 0, -1);

    if ($method == "GET"){
      curl_setopt($ch, CURLOPT_URL, $host.$path."?".$request_params);
    }
    if ($method == "POST"){
      curl_setopt($ch, CURLOPT_URL, $host.$path);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);
    }

    // Set so curl_exec returns the result instead of outputting it.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Get the response and close the channel.
    $json = json_decode(curl_exec($ch));
    curl_close($ch);
    return $json;
  }

  private function signed_request($path, $method, $params, $auth, $post_data=""){
    $expires = date("Y-m-d\TH:i:s\Z", time() + 60); //current time + 60 seconds
    $auth_params = array(
          "key"     => $auth->app_key,
          "token"   => $auth->app_token,
          "expires" => $expires
      );
    $params = array_merge($params, $auth_params);
    ksort($params);
    $str_params = "";
    foreach ($params as $key => $value) {
        $str_params.= "&$key=$value";
    }
    $str_params = trim($str_params, "&");
    $sign_str = implode(array(
        $method,
        $path,
        md5($str_params),
        md5($post_data),
        $auth->app_token,
        $expires
      ), "\n");

    $sign = trim(base64_encode(hash_hmac("sha1", utf8_encode($sign_str), $auth->app_secret, true)), "=");
    $params = array_merge($params, array("sign" => $sign));

    return $this::http_request_curl('GET', $this->host, $this->port,  $path, $params);
  }


}

$gc = new Goodscloud("host", "user", "password");
$product = $gc->getProduct("00845982004017");

var_dump($product);
