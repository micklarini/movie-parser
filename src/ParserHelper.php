<?php

namespace App;

final class ParserHelper
{
  public $config;
  protected $client;

  function __construct($config)
  {
    $this->config = $config;
    import('PLUG.JavaScript.JTokenizer');

    $this->client = new \GuzzleHttp\Client(
      [
        'base_uri' => $this->config->parser['baseUrl'],
        'timeout'  => $this->config->parser['timeout'],
        'cookies' => true,
        'http_errors' => false,
      ],
      [ 'redirect.disable' => true ],
    );

  }

  public function parseOne(int $id)
  {
    $result = [];
    $query = ['id' => $id];
    try {
      $response = $this->client->request('GET', $this->config->parser['infoPage'], ['query' => $query]);
      if ($response->getStatusCode() != 200) {
        return false;
      }

      $dom = new \DOMDocument();
      @$dom->loadHTML((string) $response->getBody());
      $xpath = new \DOMXpath($dom);
      $result['remoteId'] = $id;
      $result['title'] = $this->lookupTitle($xpath);
      $result['categoryId'] = $this->lookupCategory($xpath);
      $result['countryId'] = $this->lookupCountry($xpath);
      $result['prodYear'] = $this->lookupProdYear($xpath) ?? $this->lookupProdStart($xpath);
      $result['imageUrl'] = $this->lookupImage($xpath, $response);
      $result['abstract'] = $this->lookupAbstract($xpath);

      $historyResponse = $this->client->request('GET', $this->config->parser['historyPage'], ['query' => $query]);
      if ($historyResponse->getStatusCode() == 200) {
        $historyDom = new \DOMDocument();
        @$historyDom->loadHTML((string) $historyResponse->getBody());
        $xpath = new \DOMXpath($historyDom);

        $result['title'] = $this->lookupExactTitle($xpath) ?? $result['title'];
        $result['history'] = $this->lookupHistory($xpath);
        $result['stats'] = $this->lookupStats($xpath);
      }
    }
    catch (\Exception $e) {
      print (string) $e . PHP_EOL;
      return false;
    }
    return $result !== [] ? $result : false;
  }
  
  public function getTopList(int $start = 0, int $qty = 10)
  {
    $result = [];
    $query = $start == 0 ? [] : ['limit_1' => $start + 1, 'limit_2' => $start + $qty];

    $response = $this->client->request('GET', $this->config->parser['topPage'], ['query' => $query]);
    if ($response->getStatusCode() != 200) {
      return false;
    }

    $dom = new \DOMDocument();
    @$dom->loadHTML((string) $response->getBody());
    $xpath = new \DOMXpath($dom);
    $nodes = $xpath->query(".//h3[contains(text(),'Рейтинг полнометражных фильмов')]/following-sibling::center/table[2]/tr/td/a[contains(@href,'" .
      $this->config->parser['infoPage'] . "')]/@href");
    foreach ($nodes as $node) {
      $matches = [];
      if (preg_match('/\?id=(\d+)/i', $node->nodeValue, $matches)) {
        $result[] = (int) $matches[1];
      }
    }
    return $result !== [] ? $result : false;
  }

  public function getTopSeriesList(int $anchor = 1, int $start = 0)
  {
    $result = [];
    $query = ($start == 0 ? [] : ['limit_1' => $start]) + ['public_list_anchor' => $anchor];
    $response = $this->client->request('GET', $this->config->parser['topSeriesPage'], ['query' => $query]);
    if ($response->getStatusCode() != 200) {
      return false;
    }
    $dom = new \DOMDocument();
    @$dom->loadHTML((string) $response->getBody());
    $xpath = new \DOMXpath($dom);
    $nodes = $xpath->query(".//h3[contains(text(),'Рейтинг:')]/following-sibling::center/table[2]/tr/td/a[contains(@href,'" .
      $this->config->parser['infoPage'] . "')]/@href");
    foreach ($nodes as $node) {
      $matches = [];
      if (preg_match('/\?id=(\d+)/i', $node->nodeValue, $matches)) {
        $result[] = (int) $matches[1];
      }
    }
    return $result !== [] ? $result : false;
  }

  private function lookupTitle(\DOMXPath $xpath)
  {
    $nodes = $xpath->query(".//title");
    if ($nodes->length) {
      $title = preg_replace('/Фильм\s+[-]*\s*(.+)\s+\(\s+\)$/', '$1', (string) $nodes->item(0)->textContent);
      if (empty($title)) {
        $title = $nodes->item(0)->textContent;
      }
      return $title;
    }
    return null;
  }

  private function lookupExactTitle(\DOMXPath $xpath)
  {
    $nodes = $xpath->query("..//table/descendant::table[@width='100%'][@height='100%']/tr/td/a[contains(@href,'" .
      $this->config->parser['infoPage'] . "')]/*");
    return $nodes->length ? $nodes->item(0)->textContent : null;
  }

  private function lookupCategory(\DOMXPath $xpath)
  {
    $nodes = $xpath->query("..//b[contains(text(),'Формат')]/../following-sibling::td[contains(@class,'review')]/a");
    return $nodes->length ? $nodes->item(0)->textContent : null;
  }

  private function lookupCountry(\DOMXPath $xpath)
  {
    $nodes = $xpath->query("..//b[contains(text(),'Производство')]/../following-sibling::td[contains(@class,'review')]/a");
    return $nodes->length ? $nodes->item(0)->textContent : '';
  }

  private function lookupProdYear(\DOMXPath $xpath)
  {
    $nodes = $xpath->query("..//b[contains(text(),'Первый показ')]/../following-sibling::td[contains(@class,'review')]");
    return $nodes->length ? (int) $nodes->item(0)->textContent : null;
  }

  private function lookupProdStart(\DOMXPath $xpath)
  {
    $nodes = $xpath->query("..//b[contains(text(),'Трансляция')]/../following-sibling::td[contains(@class,'review')]");
    return $nodes->length ? (int) preg_replace('/^\D*(\d+).*$/', '$1', $nodes->item(0)->textContent) : null;
  }

  private function lookupAbstract(\DOMXPath $xpath)
  {
    $nodes = $xpath->query("..//b/*[contains(text(),'Краткое содержание')]/ancestor::table/following-sibling::table[1]/descendant::*[contains(@class, 'review')]");
    return $nodes->length ? $nodes->item(0)->textContent : null;
  }

  private function lookupImage(\DOMXPath $xpath)
  {
    $nodes = $xpath->query(".//div[contains(@class,'comment_block')]/descendant::a/img/@src");
    $remote = $nodes->length ? $this->config->parser['baseUrl'] . $nodes->item(0)->nodeValue : null;
    if (empty($remote)) {
      return $remote;
    }
    $remoteInfo = pathinfo($remote);
    $localName = sprintf('%s%s.%s', $this->config->files['movies']['posters'], uniqid(), $remoteInfo['extension']);
    $response = $this->client->request('GET', $remote, ['sink' =>  $this->config->engine['doc_root'] . "/{$localName}"]);
    return ($response->getStatusCode() == 200) ? $localName : null;
  }

  private function lookupHistory(\DOMXPath $xpath): array
  {
    $nodes = $xpath->query(".//script[contains(@type,'text/javascript')]");
    $result = [];
    foreach ($nodes as $node) {
      if (empty($node->nodeValue)) {
        continue;
      }
      $tokenizer = new \JTokenizer(false, true);
      $tokens = $tokenizer->get_all_tokens($node->nodeValue);
      if (empty($tokens)) {
        continue;
      }

      $entries = $stack = [];
      foreach ($tokens as $entry) {
        if (empty($stack)) {
          if (in_array($entry[1], ['data', '.', 'addRows', '('])) {
            $entries[] = $entry[1];
          }
          elseif (!empty($entries) && $entry[1] == '[' && @$entries[2] == 'addRows') {
            $stack[] = $entry[1];
            $result = [$entry[1]];
          }
          else {
            $entries = $stack = [];
          }
        }
        else {
          $result[] = $entry[1];
          if ($entry[1] == ']') {
            array_pop($stack);
            if (empty($stack)) {
              break;
            }
          }
          elseif ($entry[1] == '[') {
            $stack[] = $entry[1];
          }
        }
      }
    }
    if (!empty($result)) {
      $offset = count($result) - 2;
      if ($result[$offset] == ',' && $result[$offset + 1] == ']') {
        unset($result[$offset]);
      }
      $result = json_decode(
        implode(' ',
          array_map(function($item) {
            return str_replace("'", '"', str_replace('"', '\"', $item));
          }, $result)
        ),
        true, 512, JSON_THROW_ON_ERROR
      );
    }

    $keys = array_map(function($item) { return preg_replace('/(\d+)\/(\d+)/', '$1-$2-01', $item[0]); }, $result);
    return array_combine(
      $keys,
      array_map(function($item, $key) { 
        return [
          'mark_date' => $key,
          'position' => (int) $item[1],
        ];
      }, 
      $result, $keys)
    );
  }

  private function lookupStats(\DOMXPath $xpath): array
  {
    $nodes = $xpath->query("..//td/a[contains(@href,'cinema.php')]/following-sibling::table[1]/descendant::td[@width]/table/tr");
    $result = [];
    foreach ($nodes as $row) {
      $result[(string) $row->childNodes->item(0)->nodeValue] = [
        'rate' => trim($row->childNodes->item(0)->nodeValue),
        'votes' => (int) trim($row->childNodes->item(2)->nodeValue)
      ];
    }
    return $result;
  }

}
