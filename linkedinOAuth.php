<?php
/*
 * Tonci Galic
 *
 * Basic lib to work with LinkedIn. Based on Taylor Singletary's work
 * 
 */

/* Load OAuth lib. You can find it at http://oauth.net */
if ( !class_exists('Snoopy') ) 
{
    require_once('OAuth.php');
    //include_once( ABSPATH . WPINC . '/class-snoopy.php' );
}




class LinkedInOAuth
{
  public $base_url = "https://www.linkedin.com";
  public $secure_base_url = "https://www.linkedin.com";
  public $oauth_callback = "oob";
  public $consumer;
  public $request_token;
  public $access_token;
  public $oauth_verifier;
  public $signature_method;
  public $request_token_url;
  public $access_token_url;
  public $authorize_url;
  public $debug = false;

  function __construct($consumer_key, $consumer_secret, $oauth_callback = NULL) {

    if($oauth_callback)
    {
      $this->oauth_callback = $oauth_callback;
    }
    if($debug) { echo " Oa_call:".$oauth_callback; }
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret, $this->oauth_callback);
    $this->signature_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->request_token_url = $this->secure_base_url . "/uas/oauth/requestToken";
    $this->access_token_url = $this->secure_base_url . "/uas/oauth/accessToken";
    $this->authorize_url = $this->secure_base_url . "/uas/oauth/authenticate";

  }

  function getRequestToken()
  {
    $consumer = $this->consumer;
    $request = OAuthRequest::from_consumer_and_token($consumer, NULL, "GET", $this->request_token_url);
    $request->set_parameter("oauth_callback", $this->oauth_callback);
    $request->sign_request($this->signature_method, $consumer, NULL);
    $headers = Array();
    $url = $request->to_url();
    $response = $this->httpRequest($url, $headers, "GET");
    parse_str($response, $response_params);
    
    $this->request_token = new OAuthConsumer($response_params['oauth_token'], $response_params['oauth_token_secret'], 1);
  }

  function generateAuthorizeUrl()
  {
    $consumer = $this->consumer;
    $request_token = $this->request_token;

    return $this->authorize_url . "?oauth_token=" . $request_token->key;
  }

  function getAccessToken($oauth_verifier)
  {
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->request_token, "GET", $this->access_token_url);
    $request->set_parameter("oauth_verifier", $oauth_verifier);
    $request->sign_request($this->signature_method, $this->consumer, $this->request_token);
    $headers = Array();
    $url = $request->to_url();
    $response = $this->httpRequest($url, $headers, "GET");
    parse_str($response, $response_params);
    if($debug) {  echo $response . "\n";    }
    $this->access_token = new OAuthConsumer($response_params['oauth_token'], $response_params['oauth_token_secret'], 1);


  }

  function getProfile($resource = "~:(id,first-name,last-name,headline,picture-url)")
  {
    $profile_url = $this->base_url . "/v1/people/" . $resource;
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->access_token, "GET", $profile_url);
    $request->sign_request($this->signature_method, $this->consumer, $this->access_token);
    $auth_header = $request->to_header("https://api.linkedin.com"); # this is the realm
    # This PHP library doesn't generate the header correctly when a realm is not specified.
    # Make sure there is a space and not a comma after OAuth
    // $auth_header = preg_replace("/Authorization\: OAuth\,/", "Authorization: OAuth ", $auth_header);
    // # Make sure there is a space between OAuth attribute
    // $auth_header = preg_replace('/\"\,/', '", ', $auth_header);
    if ($debug) { echo $auth_header;  }
    // $response will now hold the XML document
    $response = $this->httpRequest($profile_url, $auth_header, "GET");
    
    return $response;
  }

  function setStatus($status)
  {
    $status_url = $this->base_url . "/v1/people/~/current-status";
    if ($debug) {  echo "Setting status...\n";  }
    $xml = "<current-status>" . htmlspecialchars($status, ENT_NOQUOTES, "UTF-8") . "</current-status>";
    if ($debug) { echo $xml . "\n";  }
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->access_token, "PUT", $status_url);
    $request->sign_request($this->signature_method, $this->consumer, $this->access_token);
    $auth_header = $request->to_header("https://api.linkedin.com");
    if ($debug) {  echo $auth_header . "\n";   }
    $response = $this->httpRequest($status_url, $auth_header, "PUT", $xml);
    
    return $response;
  }

  function httpRequest($url, $auth_header, $method, $body = NULL)
  {
    if (!$method)
    {
      $method = "GET";
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array($auth_header)); // Set the headers.

    if ($body)
    {
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array($auth_header, "Content-Type: text/xml;charset=utf-8"));
    }

    $data = curl_exec($curl);
    if ($this->debug) {  echo $data . "\n";  }
    curl_close($curl);

    return $data;
  }
  
}

?>
