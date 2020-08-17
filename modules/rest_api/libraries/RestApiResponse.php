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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

class RestApiResponse {

  private $startTime;

  /**
   * A template to define the header of any HTML pages output. Replace
   * {{ base }} with the root path of the warehouse.
   * @var string
   */
  private $htmlHeader = <<<'HTML'
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Indicia RESTful API</title>
  <link href="{{ base }}vendor-other/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
  <link href="{{ base }}vendor-other/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet" type="text/css" />
  <link href="{{ base }}modules/rest_api/media/css/rest_api.css" rel="stylesheet" type="text/css" />
</head>
<body>
  <div class="container">
HTML;

  private $htmlFooter = <<<'HTML'
  </div>
  <script src="{{ base }}media/js/jquery.js"></script>
  <script src="{{ base }}vendor-other/bootstrap/js/bootstrap.min.js"></script>
  <script src=""></script>
</body>
</html>
HTML;

  /**
   * When outputting HTML this contains the title for the page.
   *
   * @var string
   */
  public $responseTitle = '';

  /**
   * Is an index table required for this response when output as HTML?
   *
   * @var bool
   */
  public $wantIndex = false;

  /**
   * Include empty output cells in HTML?
   *
   * @var bool
   */
  public $includeEmptyValues = true;

  /**
   * Index method which provides top level help for the API resource endpoints.
   *
   * @param array $resourceConfig
   *   Configuration for the list of available resources and the methods they
   *   support.
   */
  public function index(array $resourceConfig) {
    switch ($this->getResponseFormat()) {
      case 'html':
        $this->indexHtml($resourceConfig);
        break;

      case 'csv':
        $this->indexCsv($resourceConfig);
        break;

      default:
        $this->indexJson($resourceConfig);
    }
  }

  /**
   * Index method in HTML format, which provides top level help for the API resource endpoints.
   *
   * @param array $resourceConfig
   *   Configuration for the list of available resources and the methods they support.
   */
  private function indexHtml($resourceConfig) {
    // Output an HTML page header.
    echo str_replace('{{ base }}', url::base(), $this->htmlHeader);
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
      if (!in_array('allow_http', $cfg)) {
        $methodNotes[] = kohana::lang("rest_api.onlyAllowHttps") .
            ' (' . str_replace('http:', 'https:', url::base()) . 'index.php/services/rest).';
      }
      if (isset($cfg['resource_options'])) {
        foreach ($cfg['resource_options'] as $resource => $options) {
          if (!empty($options)) {
            // Look for a resource specific note.
            $note = kohana::lang("rest_api.resourceOptionInfo-$resource");
            if ($note === "rest_api.resourceOptionInfo-$resource") {
              // Revert to generic note if not available.
              $note = kohana::lang('rest_api.resourceOptionInfo', '<em>' . $resource . '</em>');
            }
            $optionTexts = [];
            foreach ($options as $option => $value) {
              if (is_array($value)) {
                foreach ($value as $subValue) {
                  $optionTexts[] = '<li>' . $subValue . '</li>';
                }
              }
              else {
                $key = "rest_api.resourceOptionInfo-$resource-" . (is_int($option) ? '' : "$option-");
                if ($value === TRUE) {
                  $key .= 'true';
                } elseif ($value === TRUE) {
                  $key .= 'false';
                } else  {
                  $key .= json_encode($value);
                }
                $optionTexts[] = '<li>' . kohana::lang($key) . '</li>';
              }
            }
            $methodNotes[] = '<p>' . str_replace('{{ list }}', '<ul>' . implode('', $optionTexts) . '</ul>', $note) . '</p>';
          }
        }
      }
      $authOptionNotes = [];
      if (preg_match('/^(direct|hmac)/', $method)) {
        if (kohana::lang("rest_api.{$method}HelpHeader") !== "rest_api.{$method}HelpHeader") {
          $authOptionNotes[] = '<li>' . kohana::lang("rest_api.{$method}HelpHeader") . '</li>';
        }
        else {
          $authOptionNotes[] = '<li>' . kohana::lang("rest_api.genericHelpHeader") . '</li>';
        }
      }
      if (Kohana::config('rest.allow_auth_tokens_in_url') && preg_match('/^direct/', $method)) {
        if (kohana::lang("rest_api.{$method}HelpUrl") !== "rest_api.{$method}HelpUrl") {
          $authOptionNotes[] = '<li>' . kohana::lang("rest_api.{$method}HelpUrl") . '</li>';
        }
        else {
          $authOptionNotes[] = '<li>' . kohana::lang("rest_api.genericHelpUrl") . '</li>';
        }
      }
      if (!empty($authOptionNotes)) {
        $methodNotes[] = '<p>' . kohana::lang('rest_api.authMethodsHelpHeader') . '</p><ul>' . implode('', $authOptionNotes) . '</ul>';
      }
      $authRows .= '<tr><th scope="row">' . kohana::lang("rest_api.$method") . '</th>';
      $authRows .= '<td>' . kohana::lang("rest_api.{$method}Help") . ' ' . implode(' ', $methodNotes) . '</td></tr>';
    }
    echo <<<HTML
<h1>$lang[title]</h1>
<p>$lang[intro]</p>
<h2>$lang[authentication]</h2>
<p>$lang[authIntro]</p>
<table class="table"><caption>$lang[authMethods]</caption>
<tbody>$authRows</tbody>
<tfoot><tr><td colspan="2">* $extraInfo</td></tr></tfoot>
</table>
<h2>$lang[resources]</h2>
HTML;

    // Loop the resource names and output each of the available methods.
    foreach ($resourceConfig as $resource => $methods) {
      echo "<h3>$resource</h3>";
      foreach ($methods as $method => $methodConfig) {
        foreach ($methodConfig['subresources'] as $urlSuffix => $resourceDef) {
          echo '<h4>' . strtoupper($method) . ' ' . url::base() . "index.php/services/rest/$resource" .
              ($urlSuffix ? "/$urlSuffix" : '') . '</h4>';
          // Note we can't have full stops in a lang key
          $extra = $urlSuffix ? str_replace('.', '-', "/$urlSuffix") : '';
          $help = kohana::lang("rest_api.resources.$resource$extra.$method");
          echo "<p>$help</p>";
          // splice in the format parameter which is always accepted.
          $resourceDef['params'] = array_merge(
            $resourceDef['params'],
            array('format' => array(
              'datatype' => 'text'
            ))
          );
          // output the documentation for parameters.
          echo '<table class="table table-bordered table-responsive"><caption>Parameters</caption>';
          echo '<thead><th scope="col">Name</th><th scope="col">Data type</th><th scope="col">Description</th></thead>';
          echo '<tbody>';
          foreach ($resourceDef['params'] as $name => $paramDef) {
            echo "<tr><th scope=\"row\">$name</th>";
            $datatype = preg_match('/\[\]$/', $paramDef['datatype']) ?
                'Single or JSON array of ' . substr($paramDef['datatype'], 0, -2) : $paramDef['datatype'];
            echo "<td>$datatype</td>";
            if ($name === 'format') {
              $help = kohana::lang('rest_api.format_param_help');
            } else {
              $help = kohana::lang("rest_api.$resource.$name");
            }
            if (!empty($paramDef['options'])) {
              $help .= ' Options available are ' . json_encode($paramDef['options']) . '.';
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
    $es = Kohana::config('rest.elasticsearch');
    if ($es) {
      echo '<h3>Elasticsearch end-points</h3>';
      foreach ($es as $endpoint => $esConfig) {
        // Also allow if authentication provided.
        if ($esConfig['open'] === TRUE) {
          echo '<h4>' . url::base() . "index.php/services/rest/$endpoint</h4>";
          echo '<table class="table table-bordered table-responsive"><caption>Allowed methods</caption>';
          echo '<thead><tr><th>HTTP method</th><th>Expression</th><th>Description</th></tr></thead>';
          echo '<tbody>';
          foreach ($esConfig['allowed'] as $method => $patterns) {
            foreach ($patterns as $expr => $desc) {
              echo "<tr><td>$method</td><td>$expr</td><td>$desc</desc></tr>";
            }
          }
          echo '</tbody></table>';
        }
      }
    }
    echo str_replace('{{ base }}', url::base(), $this->htmlFooter);
  }

  /**
   * Index method in CSV format, which provides top level help for the API resource endpoints.
   * @param array $resourceConfig Configuration for the list of available resources and the methods they support.
   */
  private function indexCsv($resourceConfig) {
    // Header row
    echo "Method,Resource,Params\r\n";
    foreach ($resourceConfig as $resource => $methods) {
      foreach ($methods as $method => $methodConfig) {
        foreach ($methodConfig['subresources'] as $urlSuffix => $resourceDef) {
          echo strtoupper($method) . ',' .
               $resource . (empty($urlSuffix) ? '' : "/$urlSuffix") . ',' .
               json_encode($resourceDef['params']);
          echo "\r\n";
        }
      }
    }
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
      if (isset($cfg['resource_options'])) {
        foreach ($cfg['resource_options'] as $resource => $options) {
          if (!empty($options)) {
            // Look for a resource specific note.
            $note = kohana::lang("rest_api.resourceOptionInfo-$resource");
            if ($note === "rest_api.resourceOptionInfo-$resource") {
              // Revert to generic note if not available.
              $note = kohana::lang('rest_api.resourceOptionInfo', $resource);
            }
            $optionTexts = [];
            foreach ($options as $option => $value) {
              if (is_array($value)) {
                foreach ($value as $subValue) {
                  $optionTexts[] = '<li>' . $subValue . '</li>';
                }
              }
              else {
                $key = "rest_api.resourceOptionInfo-$resource-" . (is_int($option) ? '' : "$option-");
                if ($value === TRUE) {
                  $key .= 'true';
                } elseif ($value === TRUE) {
                  $key .= 'false';
                } else  {
                  $key .= json_encode($value);
                }
                $optionTexts[] = kohana::lang($key);
              }
            }
            $methodNotes[] = str_replace('{{ list }}', implode('; ', $optionTexts), $note);
          }
        }
      }
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
   * Get's the timestamp of a process start.
   *
   * Allows a change of behaviour if a max_time parameter is exceeded.
   */
  public function trackTime() {
    $this->startTime = microtime(TRUE);
  }

  /**
   * Outputs a data object as JSON (or chosen alternative format), in the case of successful operation.
   *
   * @param array $data
   *   Response data to output.
   * @param array $options
   *   Additional options for the output. Content can include:
   *   * metadata - information to display at top of HTML output
   *   * columns - list of column definitions for tabular output
   *   * attachHref
   *   * columnsToUnset - an array of columns to remove from tabular output.
   */
  public function succeed($data, $options = array(), $autofeed = FALSE) {
    $format = $this->getResponseFormat();
    switch ($format) {
      case 'html':
        header('Content-Type: text/html');
        $this->succeedHtml($data, $options);
        break;

      case 'csv':
        header('Content-Type: text/csv');
        $this->succeedCsv($data, $options);
        break;

      case 'json':
        header('Content-Type: application/json');
        $this->succeedJson($data, $options, $autofeed);
        break;

      default:
        throw new RestApiAbort("Invalid format $format", 400);
    }
  }

  /**
   * Returns an HTML error response code, logs a message and aborts the script.
   *
   * @param string $status
   *   HTTP error status message.
   * @param int $code
   *   HTTP error code.
   * @param string $msg
   *   Detailed message to log.
   */
  public function fail($status, $code, $msg = NULL) {
    http_response_code($code);
    $response = array(
      'code' => $code,
      'status' => $status,
    );
    if ($msg) {
      $response['message'] = $msg;
    }
    $format = $this->getResponseFormat();
    if ($format === 'html') {
      header('Content-Type: text/html');
      echo str_replace('{{ base }}', url::base(), $this->htmlHeader);
      $this->outputArrayAsHtml($response);
      echo str_replace('{{ base }}', url::base(), $this->htmlFooter);
    }
    else {
      header('Content-Type: application/json');
      echo json_encode($response);
    }
    if ($msg) {
      $msg = is_array($msg) ? json_encode($msg) : $msg;
      kohana::log('debug', "HTTP code: $code. $msg");
      kohana::log_save();
    }
    throw new RestApiAbort($status);
  }

  /**
   * Takes a URL and adds the current metadata parameters from the request and
   * adds them to the URL.
   */
  public function getUrlWithCurrentParams($url) {
    $url = url::base() . "index.php/services/rest/$url";
    $query = array();
    $paramsToCopy = [
      'proj_id',
      'format',
      'user',
      'user_id',
      'website_id',
      'secret',
    ];
    foreach ($paramsToCopy as $param) {
      if (!empty($_REQUEST[$param])) {
        $query[$param] = $_REQUEST[$param];
      }
    }
    return $url . (empty($query) ? '' : '?' . http_build_query($query));
  }

  /**
   * Echos a successful response in HTML format.
   * @param array $data
   * @param array $options
   */
  private function succeedHtml($data, $options) {
    echo str_replace('{{ base }}', url::base(), $this->htmlHeader);
    if (!empty($this->responseTitle)) {
      echo '<h1>' . $this->responseTitle . '</h1>';
    }
    if (isset($options['metadata'])) {
      echo '<h2>Metadata</h2>';
      $this->outputArrayAsHtml($options['metadata']);
    }

    // output an index table if present for this output
    if ($this->wantIndex && isset($data['data'])) {
      echo $this->getIndexAsHtml($data['data']);
    }
    // output the main response body
    if (isset($options['metadata']) || !empty($this->responseTitle)) {
      echo '<h2>Response</h2>';
    }
    if (is_array($data)) {
      $this->outputArrayAsHtml($data, $options);
    }
    elseif (is_object($data)) {
      $options['preprocess'] = true;
      // We are returning a single row from the database.
      $this->outputResultAsHtml(array($data), $options);
    }
    echo str_replace('{{ base }}', url::base(), $this->htmlFooter);
  }

  /**
   * For some resources when output as HTML, we insert an index into the top of the page.
   * @return string HTML for the index.
   */
  private function getIndexAsHtml($data) {
    $r = '';
    if (!empty($data)) {
      $r = '<table class="table table-bordered table-responsive"><caption>Index</caption>';
      $r .= '<thead><tr><th>Entry</th><th>Title</th><th>Description</th></tr></thead>';
      $r .= '<tbody>';
      foreach ($data as $key => $row) {
        // If we have a title, display or caption value, it can be used as the main label for the entry
        $labelValues = array_intersect_key($row, array('title' => '', 'display' => '', 'caption' => ''));
        if (count($labelValues) > 0 && !is_array($row[array_keys($labelValues)[0]])) {
          // Use the first one found as a label - probably only 1 anyway.
          $label = array_shift($labelValues);
        }
        else {
          $label = $key;
        }
        $description = empty($row['description']) ? '' : $row['description'];
        $r .= <<<ROW
<tr>
  <th scope="row"><a href="#$key">$key</a></th>
  <td>$label</td>
  <td>$description</td>
</tr>
ROW;
      }
      $r .= '</tbody></table>';
    }
    return $r;
  }

  /**
   * Dumps out a nested array as a nested HTML table. Used to output response data when the
   * format type requested is HTML.
   *
   * @param array $array Data to output
   * @param array $options
   */
  private function outputArrayAsHtml($array, $options = array()) {
    if (count($array)) {
      $id = isset($options['tableId']) ? " id=\"$options[tableId]\"" : '';
      echo "<table class=\"table table-bordered table-responsive\"$id>";
      // If the data has a suitable field to generate a table caption then do so.
      $labelValues = array_intersect_key($array, array('title' => '', 'display' => '', 'caption' => ''));
      if (count($labelValues)>0 && !is_array($array[array_keys($labelValues)[0]])) {
        // Use the first one found as a label - probably only 1 anyway.
        $label = array_shift($labelValues);
        echo "<caption>$label</caption>";
      }
      $keys = array_keys($array);
      $col1 = is_integer($keys[0]) ? 'Row' : 'Field';
      $col2 = is_integer($keys[0]) ? 'Record' : 'Value';
      $this->preProcessRow($array, $options);
      echo "<thead><th scope=\"col\">$col1</th><th scope=\"col\">$col2</th></thead>";
      echo '<tbody>';
      foreach ($array as $key=>$value) {
        if (empty($value) && !$this->includeEmptyValues)
          continue;
        // If in a simple list of data or pg output, start preprocessing rows. Other structural output elements are not
        // preprocessed.
        $options['preprocess'] = is_int($key) || is_object($value);
        $class = is_array($value) && !empty($value['type']) ? " class=\"type-$value[type]\"" : '';
        echo "<tr><th scope=\"row\"$class>$key</th><td>";
        $options['tableId'] = $key;
        // Object data here means a pg result object. Or, if it is an non-associative array so a simple list, then
        // output as a table rather than a nested structure.
        if (is_object($value) || (is_array($value) && count($value) > 0 && is_int(array_keys($value)[0]))) {
          // recurse into pg result data
          $this->outputResultAsHtml($value, $options);
        } elseif (is_array($value)) {
          // recurse into plain array data
          $this->outputArrayAsHtml($value, $options);
        } else {
          // a simple value to output. If it contains an internal link then process it to hide user/secret data.
          if (preg_match('/http(s)?:\/\//', $value)) {
            $parts = explode('?', $value);
            $displayUrl = $parts[0];
            if (count($parts)>1) {
              parse_str($parts[1], $params);
              unset($params['user']);
              unset($params['user_id']);
              unset($params['website_id']);
              unset($params['secret']);
              if (count($params)) {
                $displayUrl .= '?' . http_build_query($params);
              }
            }
            $value = "<a href=\"$value\">$displayUrl</a>";
          }
          echo "<p>$value</p>";
        }
        echo '</td></tr>';
      }
      echo '</tbody></table>';
    }
  }

  /**
   * Dumps out an HTML table containing results from a PostgreSQL query.
   * @param array $data PG result data to iterate through.
   * @param array $options Options array. If this has a columns element, it is used to generate a header row and control
   * the output.
   */
  private function outputResultAsHtml($data, $options) {
    echo '<table class="table table-bordered table-responsive">';
    if (isset($options['columns'])) {
      // Ensure href and foriegn key column titles are added if we are including either of them. That's because these
      // are dynamically added to the data for each row as we go.
      if (!empty($options['preprocess'])) {
        if (!empty($options['attachHref']) && !in_array('href', $options['columns'])) {
          $options['columns']['href'] = array();
        }
        if (!empty($options['attachFkLink']) && !in_array($options['attachFkLink'][0], $options['columns'])) {
          $options['columns'][$options['attachFkLink'][0]] = array();
        }
      }
      echo '<thead><tr>';
      foreach ($options['columns'] as $fieldname => $column) {
        $caption = isset($column['caption']) ? $column['caption'] : $fieldname;
        echo "<th>$caption</th>";
      }
      echo '</tr></thead>';
      $columns = array_keys($options['columns']);
    } elseif (count($data) > 0) {
      $columns = array_keys((array)$data[0]);
    }
    echo '<tbody>';

    foreach ($data as $row) {
      $this->preProcessRow($row, $options, $columns);
      echo '<tr>';
      foreach ($columns as $column) {
        echo '<td>';
        $value = isset($row[$column]) ? $row[$column] : 'not available';
        if (is_array($value)) {
          // Might have nested data to output, e.g. for a foreign key.
          $this->outputArrayAsHtml($value, array());
        } else {
          echo $value;
        }
        echo '</td>';
      }
      echo '</tr>';
    }
    echo '</tbody></table>';
  }

  /**
   * Echos a successful response in CSV format.
   * @param array $data
   * @param array $options
   */
  private function succeedCsv($data, $options) {
    if (is_array($data) && isset($data['data'])) {
      $rows = $data['data'];
    } elseif (is_object($data)) {
      // outputting a single row from a pg result
      $rows = array($data);
    }
    if (isset($rows)) {
      if (isset($options['columns'])) {
        $columns = array_keys($options['columns']);
      } else {
        // If we don't have columns metadata, we have to calculate the complete list of columns so we can line things up
        $columns = $this->findCsvColumns($rows);
      }
      // Remove columns that we aren't supposed to output
      if (isset($options['columnsToUnset'])) {
        $columns = array_diff($columns, $options['columnsToUnset']);
        unset($options['columnsToUnset']);
      }
      $count = count($rows);
      if (!empty($options['attachHref']) && !in_array('href', $columns)) {
        $columns[] = 'href';
      }
      if (!empty($options['attachFkLink']) && !in_array($options['attachFkLink'][0], $columns)) {
        $columns[] = $options['attachFkLink'][0];
      }
      echo $this->getCsvRow(array_combine($columns, $columns), $columns, $options) . "\r\n";
      $options['preprocess'] = true;
      foreach ($rows as $idx => $row) {
        echo $this->getCsvRow($row, $columns, $options);
        if ($idx < $count - 1) {
          echo "\r\n";
        }
      }
    }
  }

  /**
   * When outputting CSV data we need a fixed list of columns. If not available in the metadata, work it out from the
   * data.
   * @param $data
   * @return array List of column field names.
   */
  private function findCsvColumns($data) {
    $r = array();
    foreach ($data as $row) {
      $r = array_merge($r, (array)$row);
    }
    return array_keys($r);
  }

  /**
   * Return a line of CSV from an array or pg result object row. This is instead of PHP's fputcsv because that
   * function only writes straight to a file, whereas we need a string.
   * @param mixed $data Either an array or pg result object row.
   * @param array $columns List of columns to output
   */
  private function getCsvRow($data, $columns, $options)
  {
    $output = '';
    $delimiter=',';
    $enclose='"';
    $this->preProcessRow($data, $options, $columns);
    foreach ($columns as $column) {
      // data can be either an array or pg result object row
      if (is_array($data)) {
        $cell = isset($data[$column]) ? $data[$column] : '';
      } elseif (is_object($data)) {
        $cell = isset($data->$column) ? $data->$column : '';
      }
      if (is_array($cell)) {
        $cell = json_encode($cell);
      }
      // If not numeric and contains the delimiter, enclose the string
      if (!is_numeric($cell) && (preg_match('/[' . $delimiter . '\r\n]/', $cell)))
      {
        //Escape the enclose
        $cell = str_replace($enclose, $enclose.$enclose, $cell);
        //Not numeric enclose
        $cell = $enclose . $cell . $enclose;
      }
      if ($output=='') {
        $output = $cell;
      }
      else {
        $output.=  $delimiter . $cell;
      }
    }
    return $output;
  }

  /**
   * Echos a successful response in JSON format.
   * @param array $data
   * @param array $options
   */
  private function succeedJson($data, $options, $autofeed) {
    // We strip empty stuff from JSON responses.
    $options['notEmpty'] = TRUE;
    // Force preprocessing for the rows we iterate through.
    $options['preprocess'] = TRUE;
    $lastTrackingId = FALSE;
    $lastTrackingDate = FALSE;
    // If data returned from db in a pg object, need to iterate it and output 1 row at a time to avoid loading into
    // memory. So we create a JSON string for the rest of the output using a stub for the data, then split it at the
    // stub. We can then output everything up to the stub, followed by the data one row at a time, followed by the
    // second part after the stub.
    if (is_array($data) && isset($data['data']) && is_object($data['data'])) {
      $dbObject = $data['data'];
      if ($autofeed) {
        echo '[';
      }
      else {
        $data['data'] = array('|#data#|');
        $parts = explode('"|#data#|"', json_encode($data));
        echo $parts[0];
      }
      // Output 1 row at a time instead of json encoding the lot or imploding
      // as it could be big.
      foreach ($dbObject as $idx => $row) {
        $this->preProcessRow($row, $options);
        echo json_encode($row);
        if ($idx < $dbObject->count() - 1) {
          echo ',';
        }
        elseif ($autofeed) {
          // Capture the ID and update tracking ID of the last row in the
          // report, so we can autofeed the next batch. Also capture the
          // tracking datae for reports that track on updated_on.
          $lastId = $row['id'];
          $lastTrackingId = isset($row['tracking']) ? $row['tracking'] : FALSE;
          $lastTrackingDate = isset($row['tracking_date']) ? $row['tracking_date'] : FALSE;
        }
      }
      if ($autofeed) {
        echo ']';
        $afSettings = (array) variable::get("rest-autofeed-$_GET[proj_id]", [], FALSE);
        if ($dbObject->count() > 0) {
          // Don't store tracking info that's not relevant for this report.
          if (!$lastTrackingId) {
            unset($afSettings['last_tracking_id']);
          }
          if (!$lastTrackingDate) {
            unset($afSettings['last_tracking_date']);
          }
        }
        if ($afSettings['mode'] === 'initialLoad' && isset($lastId) && $dbObject->count() >= AUTOFEED_DEFAULT_PAGE_SIZE) {
          // On initial load mode, we want the next autofeed batch to start on
          // our highest row ID + 1, unless we've reached the end..
          $afSettings['last_id'] = $lastId;
        }
        elseif ($afSettings['mode'] === 'initialLoad') {
          // At the end of the initial load, switch to updates only mode.
          $afSettings['mode'] = 'updates';
          unset($afSettings['last_id']);
        }
        elseif ($afSettings['mode'] === 'updates') {
          if ($lastTrackingId && preg_match('/^\d+$/', $lastTrackingId)) {
            // Whilst in updates only mode, we want to start the next batch
            // after the same tracking ID as the last batch finished so we get
            // no gaps.
            $afSettings['last_tracking_id'] = $lastTrackingId;
          }
          elseif ($lastTrackingDate) {
            // Or same principle for date tracking mode.
            $afSettings['last_tracking_date'] = $lastTrackingDate;
          }
        }
        // Do not set the tracking variable if we have exceeded a time limit
        // specified in the request. Otherwise a failure to process the batch
        // on the client results in a batch being skipped.
        if (isset($this->startTime) && isset($_REQUEST['max_time'])) {
          if (microtime(TRUE) - $this->startTime < $_REQUEST['max_time']) {
            variable::set("rest-autofeed-$_GET[proj_id]", $afSettings);
          }
          else {
            // In this instance, we don't update the variable, so the next batch
            // will be the same as this one.
            kohana::log('error', "Max time exceeded: " . (microtime(TRUE) - $this->startTime) .
              " is greater than $_REQUEST[max_time]");
          }
        }
      }
      else {
        echo $parts[1];
      }
    }
    else {
      // Preprocess any row data.
      if (is_array($data) && isset($data['data'])) {
        foreach ($data['data'] as &$row) {
          $this->preProcessRow($row, $options);
        }
      }
      elseif (is_object($data)) {
        // A single row from a db result object is being returned.
        $this->preProcessRow($data, $options);
      }
      echo json_encode($data);
    }
  }

  /**
   * Method to determine the required format for the response, either json or html.
   * The format can be specified in a format query parameter in the URL, or in the accept header of the request.
   *
   * @return string
   *   Format, either json or html
   */
  private function getResponseFormat() {
    // Allow a format query string parameter to override the Accept header.
    if (isset($_REQUEST['format']) && preg_match('/(json|html|csv)/', $_REQUEST['format'])) {
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
        } elseif (trim($mimeType) === 'text/csv') {
          return 'csv';
        } elseif (trim($mimeType) === 'text/html') {
          return 'html';
        }
      }
    }
    // fall back on default
    return 'json';
  }

  /**
   * Applies any preprocessing to the row of data about to be output. Includes:
   * * Application of vague date processing
   * * Removal of empty values
   * * Attaching hrefs to data to point back to self
   * * Attaching links to data to point to objects identified by foreign keys.
   * * Removing unwanted columns.
   * @param $row
   * @param $options
   * @param array $columns
   */
  private function preProcessRow(&$row, &$options, &$columns = array()) {
    // For simplicity, convert pg result row objects to arrays so we can treat all the same.
    $row = (array)$row;
    // Skip row preprocessing if not actually on a data row.
    if (empty($options['preprocess']))
      return;
    // Unset any columns we need to skip
    if (isset($options['columnsToUnset'])) {
      foreach ($options['columnsToUnset'] as $column) {
        unset($row[$column]);
      }
    }
    // Apply vague date processing where relevant
    if (isset($row['date_start']) && isset($row['date_end']) && isset($row['date_type'])) {
      $row['date'] = vague_date::vague_date_to_string(array($row['date_start'], $row['date_end'], $row['date_type']));
    }
    // Attach an href field to the resource row pointing back to itself.
    if (isset($options['attachHref'])) {
      $attachResource = $options['attachHref'][0];
      $attachId = $options['attachHref'][1];
      $row['href'] = "$attachResource/$row[$attachId]";
      //$row['href'] = $this->getUrlWithCurrentParams($row['href']);
      if (!in_array('href', $columns)) {
        $columns[] = 'href';
      }
    }
    // 'attachFkLink' => array('taxonObservation', 'taxon_observation_id', 'taxon-observation'),
    if (isset($options['attachFkLink'])) {
      $row[$options['attachFkLink'][0]] = array(
        'id' => $row[$options['attachFkLink'][1]],
        'href' => $this->getUrlWithCurrentParams($options['attachFkLink'][2] . '/' . $row[$options['attachFkLink'][1]])
      );
      unset($row[$options['attachFkLink'][1]]);
    }
    if (isset($options['notEmpty'])) {
      $row = array_filter((array)$row, array($this, 'notEmpty'));
    }
  }

  /**
   * Utility method for filtering empty values from an array.
   * @param $value
   * @return bool
   */
  function notEmpty($value) {
    return $value !== NULL && $value !== '';
  }

}
