<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package Services
 * @subpackage REST API
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link    http://code.google.com/p/indicia/
 */

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
   * Index method, which provides top level help for the API resource endpoints.
   * @param array $resourceConfig Configuration for the list of available resources and the methods they support.
   */
  public function index($resourceConfig) {
    switch ($this->getResponseFormat()) {
      case 'html':
        $this->indexHtml($resourceConfig);
        break;
      default:
        $this->indexJson($resourceConfig);
    }
  }

  /**
   * Index method in HTML format, which provides top level help for the API resource endpoints.
   * @param array $resourceConfig Configuration for the list of available resources and the methods they support.
   */
  private function indexHtml($resourceConfig) {
    // Output an HTML page header
    $css = url::base() . "modules/rest_api/media/css/rest_api.css";
    echo str_replace('{css}', $css, $this->html_header);
    $lang = array(
      'title' => kohana::lang("rest_api.title"),
      'intro' => kohana::lang("rest_api.introduction"),
      'authentication' => kohana::lang("rest_api.authenticationTitle"),
      'authIntro' => kohana::lang("rest_api.authIntroduction"),
      'authMethods' => kohana::lang("rest_api.authMethods"),
      'resources' => kohana::lang("rest_api.resourcesTitle"),
    );
    $authRows = '';
    $extraInfo = Kohana::config('rest.allow_auth_tokens_in_url')
        ? kohana::lang("rest_api.allowAuthTokensInUrl") : kohana::lang("rest_api.dontAllowAuthTokensInUrl");
    foreach (Kohana::config('rest.authentication_methods') as $method => $cfg) {
      $methodNotes = [];
      if (!in_array('allow_http', $cfg))
        $methodNotes[] = kohana::lang("rest_api.onlyAllowHttps") .
            ' (' . str_replace('http:', 'https:', url::base()) . 'index.php/services/rest).';
      if (!in_array('allow_all_report_access', $cfg))
        $methodNotes[] = kohana::lang("rest_api.onlyAllowFeaturedReports");
      $authRows .= '<tr><th scope="row">' . kohana::lang("rest_api.$method") . '</th>';
      $authRows .= '<td>' . kohana::lang("rest_api.{$method}Help") . ' ' . implode(' ', $methodNotes) . '</td></tr>';
    }
    echo <<<HTML
<h1>$lang[title]</h1>
<p>$lang[intro]</p>
<h2>$lang[authentication]</h2>
<p>$lang[authIntro]</p>
<table><caption>$lang[authMethods]</caption>
<tbody>$authRows</tbody>
<tfoot><tr><td colspan="2">* $extraInfo</td></tr></tfoot>
</table>
<h2>$lang[resources]</h2>
HTML;

    // Loop the resource names and output each of the available methods.
    foreach($resourceConfig as $resource => $methods) {
      echo "<h3>$resource</h3>";
      foreach ($methods as $method => $methodConfig) {
        foreach ($methodConfig['subresources'] as $urlSuffix => $resourceDef) {
          echo '<h4>' . strtoupper($method) . ' ' . url::base() . "index.php/services/rest/$resource" .
              ($urlSuffix ? "/$urlSuffix" : '') . '</h4>';
          // Note we can't have full stops in a lang key
          $extra = $urlSuffix ? str_replace('.', '-', "/$urlSuffix") : '';
          $help = kohana::lang("rest_api.resources.$resource$extra");
          echo "<p>$help</p>";
          // splice in the format parameter which is always accepted.
          $resourceDef['params'] = array_merge(
            $resourceDef['params'],
            array('format' => array(
              'datatype' => 'text'
            ))
          );
          // output the documentation for parameters.
          echo '<table><caption>Parameters</caption>';
          echo '<thead><th scope="col">Name</th><th scope="col">Data type</th><th scope="col">Description</th></thead>';
          echo '<tbody>';
          foreach ($resourceDef['params'] as $name => $paramDef) {
            echo "<tr><th scope=\"row\">$name</th>";
            echo "<td>$paramDef[datatype]</td>";
            if ($name === 'format') {
              $help = kohana::lang('rest_api.format_param_help');
            } else {
              $help = kohana::lang("rest_api.$resource.$name");
            }
            if (!empty($paramDef['required'])) {
              $help .= ' <strong>' . kohana::lang('Required.') . '</strong>';
            }
            echo "<td>$help</td>";
            echo "</tr>";
          }
          echo '</tbody></table>';
        }
      }
    }
    echo '</body></html>';
  }

  /**
   * Index method in JSON format, which provides top level help for the API resource endpoints.
   * @param array $resourceConfig Configuration for the list of available resources and the methods they support.
   */
  private function indexJson($http_methods) {
    $r = array('authorisation' => [], 'resources' => []);
    foreach (Kohana::config('rest.authentication_methods') as $method => $cfg) {
      $methodNotes = [];
      if (!in_array('allow_http', $cfg))
        $methodNotes[] = kohana::lang("rest_api.onlyAllowHttps") .
          ' (' . str_replace('http:', 'https:', url::base()) . 'index.php/services/rest).';
      if (!in_array('allow_all_report_access', $cfg))
        $methodNotes[] = kohana::lang("rest_api.onlyAllowFeaturedReports");
      $r['authorisation'][$method] = array(
        'name' => kohana::lang("rest_api.$method"),
        'help' => kohana::lang("rest_api.{$method}Help") . ' ' . implode(' ', $methodNotes)
      );
    }
    // Loop the resource names and output each of the available methods.
    foreach($http_methods as $resource => $methods) {
      $resourceInfo = [];
      foreach ($methods as $method => $methodConfig) {
        foreach ($methodConfig['subresources'] as $urlSuffix => $resourceDef) {
          // Note we can't have full stops in a lang key
          $extra = $urlSuffix ? str_replace('.', '-', "/$urlSuffix") : '';
          $help = kohana::lang("rest_api.resources.$resource$extra");
          $resourceDef['params'] = array_merge(
            $resourceDef['params'],
            array('format' => array(
              'datatype' => 'text'
            ))
          );
          foreach ($resourceDef['params'] as $name => &$paramDef) {
            if ($name === 'format') {
              $help = kohana::lang('rest_api.format_param_help');
            } else {
              $help = kohana::lang("rest_api.$resource.$name");
            }
            if (!empty($paramDef['required'])) {
              $help .= ' ' . kohana::lang('Required.');
            }
            $paramDef['help'] = $help;
          }
          $resourceInfo[] = array(
            'resource' => url::base() . "index.php/services/rest/$resource" . ($urlSuffix ? "/$urlSuffix" : ''),
            'method' => strtoupper($method),
            'help' => $help,
            'params' => $resourceDef['params']
          );
        }
      }
      $r['resources'][$resource] = $resourceInfo;
    }
    echo json_encode($r);
  }

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
      echo $this->getArrayAsHtml($response, 'Error');
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
        $legendFieldValue = $array[$legendFieldName];
        $legendFieldDescription = empty($array['description']) ? '' : $array['description'];
        $id = preg_replace('/[^a-z0-9]/', '-', strtolower("$legendFieldName-$legendFieldValue"));
        $r .= "<caption id=\"$id\">$legendFieldValue</caption>";
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

  /**
   * Method to determine the required format for the response, either json or html.
   * The format can be specified in a format query parameter in the URL, or in the accept header of the request.
   * @return string Format, either json or html
   */
  private function getResponseFormat() {
    // Allow a format query string parameter to override the Accept header.
    if (isset($_REQUEST['format']) && preg_match('/(json|html)/', $_REQUEST['format'])) {
      return $_REQUEST['format'];
    }
    $headers = apache_request_headers();
    // accept header is preferred RESTful approach
    if (!empty($headers['Accept'])) {
      $acceptParts = explode(';', $headers['Accept']);
      $acceptMimeTypes = explode(',', $acceptParts[0]);
      foreach ($acceptMimeTypes as $mimeType) {
        if (trim($mimeType) === 'application/json') {
          return 'json';
        } elseif (trim($mimeType) === 'text/html') {
          return 'html';
        }
      }
    }
    // fall back on default
    return 'json';
  }

}