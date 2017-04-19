<?php

class RestApiResponse {

  /**
   * A template to define the header of any HTML pages output. Replace {css} with the
   * path to the CSS file to load.
   * @var string
   */
  private $html_header = <<<'HTML'
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Indicia RESTful API</title>
  <link href="{css}" rel="stylesheet" type="text/css" />
</head>
<body>
HTML;

  /**
   * When outputting HTML this contains the title for the page.
   * @var string
   */
  public $responseTitle = '';

  /**
   * Is an index table required for this response when output as HTML?
   * @var bool
   */
  public $wantIndex = false;

  /**
   * HTML built dynamically for the page output index.
   * @var string
   */
  public $index = '';

  /**
   * Include empty output cells in HTML?
   * @var bool
   */
  public $includeEmptyValues = true;

  /**
   * Outputs a data object as JSON (or chosen alternative format), in the case of successful operation.
   *
   * @param array $data Response data to output.
   */
  public function succeed($data, $metadata = null) {
    $format = $this->getResponseFormat();
    if ($format==='html') {
      header('Content-Type: text/html');
      $css = url::base() . "modules/rest_api/media/css/rest_api.css";
      echo str_replace('{css}', $css, $this->html_header);
      if (!empty($this->responseTitle))
        echo '<h1>' . $this->responseTitle . '</h1>';
      if ($metadata) {
        echo '<h2>Metadata</h2>';
        echo $this->getArrayAsHtml($metadata, 'metadata');
      }
      // build the output HTML and the page index
      $output = $this->getArrayAsHtml($data, 'data');
      // output an index table if present for this output
      if ($this->wantIndex && !empty($this->index))
        echo '<table><caption>Index</caption>' . $this->index . '</table>';
      // output the main response body
      if ($metadata || !empty($this->responseTitle))
        echo '<h2>Response</h2>';
      echo $output;
      echo '</body></html>';
    } else {
      header('Content-Type: application/json');
      echo json_encode($data);
    }
  }


  /**
   * Returns an HTML error response code, logs a message and aborts the script.
   *
   * @param string $status HTTP error status message
   * @param integer $code HTTP error code
   * @param string $msg Detailed message to log
   */
  public function fail($status, $code, $msg=NULL) {
    http_response_code($code);
    $response = array(
      'code' => $code,
      'status' => $status
    );
    if ($msg)
      $response['message'] = $msg;
    $format = $this->getResponseFormat();
    if ($format === 'html') {
      header('Content-Type: text/html');
      $css = url::base() . "modules/rest_api/media/css/rest_api.css";
      echo str_replace('{css}', $css, $this->html_header);
      echo $this->getArrayAsHtml($response);
      echo '</body></html>';
    } else {
      header('Content-Type: application/json');
      echo json_encode($response);
    }
    if ($msg) {
      kohana::log('debug', "HTTP code: $code. $msg");
      kohana::log_save();
    }
    throw new RestApiAbort($status);
  }

  /**
   * Dumps out a nested array as a nested HTML table. Used to output response data when the
   * format type requested is HTML.
   *
   * @param array $array Data to output
   * @param string $label Label to be used when linking to this array in the index.
   */
  private function getArrayAsHtml($array, $label) {
    $r = '';
    if (count($array)) {
      $r .= '<table border="1">';
      $legendValues = array_intersect_key($array, array('title' => '', 'display' => ''));
      if (count($legendValues)>0 && !is_array($array[array_keys($legendValues)[0]])) {
        $legendFieldName = array_keys($legendValues)[0];
        $legendFieldValue = $array[array_keys($legendValues)[0]];
        $legendFieldDescription = empty($array['description']) ? '' : $array['description'];
        $id = preg_replace('/[^a-z0-9]/', '-', strtolower("$legendFieldName-$legendFieldValue"));
        $r .= "<caption id=\"$id\">$legendFieldName: $legendFieldValue</caption>";
        $this->index .= <<<ROW
<tr>
  <th scope="row"><a href="#$id">$label</a></th>
  <td>$legendFieldValue</td>
  <td>$legendFieldDescription</td>
</tr>
ROW;
      }
      $keys = array_keys($array);
      $col1 = is_integer($keys[0]) ? 'Row' : 'Field';
      $col2 = is_integer($keys[0]) ? 'Record' : 'Value';
      $r .= "<thead><th scope=\"col\">$col1</th><th scope=\"col\">$col2</th></thead>";
      $r .= '<tbody>';
      foreach ($array as $key=>$value) {
        if (empty($value) && !$this->includeEmptyValues)
          continue;
        $class = !empty($value['type']) ? " class=\"type-$value[type]\"" : '';
        $r .= "<tr><th scope=\"row\"$class>$key</th><td>";
        if (is_array($value))
          $r .= $this->getArrayAsHtml($value, $key);
        else {
          if (preg_match('/http(s)?:\/\//', $value)) {
            $parts = explode('?', $value);
            $displayUrl = $parts[0];
            if (count($parts)>1) {
              parse_str($parts[1], $params);
              unset($params['user']);
              unset($params['secret']);
              if (count($params)) {
                $displayUrl .= '?' . http_build_query($params);
              }
            }
            $value = "<a href=\"$value\">$displayUrl</a>";
          }
          $r .= "<p>$value</p>";
        }
        $r .= '</td></tr>';
      }
      $r .= '</tbody></table>';
    }
    return $r;
  }


  private function getResponseFormat() {
    if (isset($_REQUEST['format']) && preg_match('/(json|html)/', $_REQUEST['format'])) {
      return $_REQUEST['format'];
    } else {
      return 'json';
    }
  }

}