<?php
$server = 'http://test.goodscloud.net';
$email = '';
$password = '';


function authenticate($url, $data) {
  $params = array('http' => array(
              'method' => 'POST',
              'content' => http_build_query($data)
            ));
  $ctx = stream_context_create($params);
  $fp = @fopen($url, 'rb', false, $ctx);
  if (!$fp) {
    throw new Exception("Problem with $url, $php_errormsg");
  }
  $meta = @stream_get_meta_data($fp);
  foreach ($meta['wrapper_data'] as $thing) {
    if (strpos($thing, 'Set-Cookie: ', 0) === 0) {
      $cookie = explode(';', substr($thing, 12));
      $cookie = $cookie[0];
    }
  }
  $response = @stream_get_contents($fp);
  if ($response === false) {
    throw new Exception("Problem reading data from $url, $php_errormsg");
  }
  return array('cookie'=>$cookie, 'response'=>json_decode($response));
}


function access_API($method, $cookie, $resource, $content=False) {
  $params = array('http' => array(
    'method'=>$method,
    'header'=>array('Cookie: ' . $cookie, 'Content-type: application/json'), // when PHP is compiled --with-curlwrappers
    // 'header' => 'Cookie: ' . $cookie . '\r\nContent-type: application/json\r\n', // when PHP is not compiled --with-curlwrappers
  ));
  if ($content) {
    $params['http']['content'] = json_encode($content);
  }
  $ctx = stream_context_create($params);
  $fp = @fopen($resource, 'rb', false, $ctx);
  if (!$fp) {
    throw new Exception("Problem with $resource, $php_errormsg");
  }
  $data = @stream_get_contents($fp);
  if ($data === false) {
    throw new Exception("Problem reading data from $url, $php_errormsg");
  }
  return json_decode($data);
}


function GET($cookie, $url) {
    return access_API('GET', $cookie, $url);
}

function POST($cookie, $url, $data) {
  return access_API('POST', $cookie, $url, $data);
}

function PUT($cookie, $url, $data) {
  return access_API('PUT', $cookie, $url, $data);
}

function DELETE($cookie, $url, $data) {
  return access_API('DELETE', $cookie, $url);
}


// Authenticate
$auth = authenticate($server . '/session', array('email'=>$email, 'password'=>$password));
$cookie = $auth['cookie'];

// Get list of companies (only one)
$company_list = GET($cookie, $server . '/api/internal/company');
print_r($company_list);

// Change the label on the first (and only) company
$response = PUT($cookie, $server . '/api/internal/company/' . $company_list->objects[0]->id, array('label'=>$company_list->objects[0]->label . " (modified)"));
print_r($response);

// Verify that the label was changed
$company = GET($cookie, $server . '/api/internal/company/' . $company_list->objects[0]->id);
print_r($company);
?>