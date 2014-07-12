<?php
class Goodscloud{
  private $host;
  private $email;
  private $password;
  private $session;

  public function __construct($host, $email, $password){
    date_default_timezone_set('Europe/Berlin');
    $this->host = $host;
    $this->email = $email;
    $this->password = $password;
    $this->login();
  }

  private function login(){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->host . '/session');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'GC-Email: ' . $this->email,
      'GC-Password: ' . $this->password,
    ));
    // Set so curl_exec returns the result instead of outputting it.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $this->session = json_decode(curl_exec($ch));
    curl_close($ch);
    if (!isset($this->session) || $this->session->email != $this->email){
      throw new Exception("API credentials incorrect", 1);
    }
  }

  private static function serialize_params($params) {
    ksort($params);
    $str_params = array();
    foreach ($params as $key => $value) {
      if (is_array($value)) {
        $value = json_encode($value);
      }
      $str_params[] = "$key=$value";
    }
    return join('&', $str_params);
  }

  private static function http_request_curl($method, $host, $path, $params, $data){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
    ));

    $param_str = Goodscloud::serialize_params($params);
    if ($param_str) {
      curl_setopt($ch, CURLOPT_URL, $host.$path."?".$param_str);
    } else {
      curl_setopt($ch, CURLOPT_URL, $host.$path);
    }

    if ($method == 'POST') {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    } elseif ($method == 'PUT') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    } elseif ($method == 'PATCH') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    } elseif ($method == 'DELETE') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    // Set so curl_exec returns the result instead of outputting it.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Get the response and close the channel.
    $result = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status_code >= 200 and $status_code < 300) {
      return json_decode($result);
    } else {
      throw new Exception("API request failed with status code " . $status_code);
    }
  }

  private function signed_request($method, $path, $params, $data=""){
    if (is_array($data)) {
      $data = json_encode($data);
    }
    $expires = date("Y-m-d\TH:i:s\Z", time() + 60); //current time + 60 seconds
    $auth_params = array(
      'key'     => $this->session->auth->app_key,
      'token'   => $this->session->auth->app_token,
      'expires' => $expires
      );
    $params = array_merge($params, $auth_params);
    $param_str = $this::serialize_params($params);
    $sign_str = implode(array(
      $method,
      $path,
      md5($param_str),
      md5($data),
      $this->session->auth->app_token,
      $expires
    ), "\n");

    $sign = trim(base64_encode(hash_hmac('sha1', utf8_encode($sign_str), $this->session->auth->app_secret, true)), '=');
    $params = array_merge($params, array('sign' => $sign));

    return $this::http_request_curl($method, $this->host, $path, $params, $data);
  }

  public function get($uri, $params=array()) {
    return $this->signed_request('GET', $uri, $params);
  }

  public function post($uri, $params, $data) {
    return $this->signed_request('POST', $uri, $params, $data);
  }

  public function put($uri, $params, $data) {
    return $this->signed_request('PUT', $uri, $params, $data);
  }

  public function patch($uri, $params, $data) {
    return $this->signed_request('PATCH', $uri, $params, $data);
  }

  public function delete($uri) {
    return $this->signed_request('DELETE', $uri, array());
  }

}
