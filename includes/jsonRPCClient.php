<?php
/*
					COPYRIGHT

Copyright 2007 Sergio Vaccaro <sergio@inservibile.org>

This file is part of JSON-RPC PHP.

JSON-RPC PHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-RPC PHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with JSON-RPC PHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The object of this class are generic jsonRPC 1.0 clients
 * http://json-rpc.org/wiki/specification
 *
 * @author sergio <jsonrpcphp@inservibile.org>
 */
class jsonRPCClient {

  /**
   * Debug state
   *
   * @var boolean
   */
  private $debug;

  /**
   * The server URL
   *
   * @var string
   */
  private $url;
  /**
   * The request id
   *
   * @var integer
   */
  private $id;
  /**
   * If true, notifications are performed instead of requests
   *
   * @var boolean
   */
  private $notification = FALSE;

  /**
   * Takes the connection parameters
   *
   * @param string $url
   * @param boolean $debug
   */
  public function __construct($url, $debug = FALSE) {
    // server URL
    $this->url = $url;
    // proxy
    empty($proxy) ? $this->proxy = '' : $this->proxy = $proxy;
    // debug state
    empty($debug) ? $this->debug = FALSE : $this->debug = TRUE;
    // message id
    $this->id = 1;
  }

  /**
   * Sets the notification state of the object. In this state, notifications are performed, instead of requests.
   *
   * @param boolean $notification
   */
  public function setRPCNotification($notification) {
    empty($notification) ?
      $this->notification = FALSE
      :
      $this->notification = TRUE;
  }

  /**
   * Performs a jsonRCP request and gets the results as an array
   *
   * @param string $method
   * @param array $params
   * @return array
   */
  public function __call($method, $params) {

    // check
    if (!is_scalar($method)) {
      throw new Exception('Method name has no scalar value');
    }

    // check
    if (is_array($params)) {
      // no keys
      $params = array_values($params);
    }
    else {
      throw new Exception('Params must be given as array');
    }

    // sets notification or request task
    if ($this->notification) {
      $currentId = NULL;
    }
    else {
      $currentId = $this->id;
    }

    // prepares the request
    $request = array(
      'method' => $method,
      'params' => $params,
      'id' => $currentId
    );
    $request = json_encode($request);
    $this->debug && $this->debug .= '***** Request *****' . "\n" . $request . "\n" . '***** End Of request *****' . "\n\n";

    // performs the HTTP POST
    $opts = array(
      'http' => array(
        'method' => 'POST',
        'header' => 'Content-type: application/json',
        'content' => $request
      )
    );
    $context = stream_context_create($opts);
    // the @ is required to suppress any errors that could be used by an attacker to learn more about your system
    if ($fp = @fopen($this->url, 'r', FALSE, $context)) {
      $response = '';
      while ($row = fgets($fp)) {
        $response .= trim($row) . "\n";
      }
      $this->debug && $this->debug .= '***** Server response *****' . "\n" . $response . '***** End of server response *****' . "\n";
      $response = json_decode($response, TRUE);
    }
    else {
      throw new Exception('Unable to connect to ' . $this->url);
    }

    // debug output
    if ($this->debug) {
      echo nl2br($debug);
    }

    // final checks and return
    if (!$this->notification) {
      // check
      if (!empty($response['id'])) {
        if ($response['id'] != $currentId) {
          throw new Exception('Incorrect response id (request id: ' . $currentId . ', response id: ' . $response['id'] . ')');
        }
      }

      if (!empty($response['error'])) {
        if(!empty($response['error']['message'])) {
          throw new Exception('Request error: ' . $response['error']['message'], $response['error']['code']);
        } else {
          throw new Exception('Request error', $response['error']['code']);
        }
      }

      return $response['result'];
    }
    else {
      return TRUE;
    }
  }
}

?>