<?php

namespace App;

class MovieModel extends \App\base\BaseModel implements \JsonSerializable
{
  protected const TABLE_NAME = 'movies';
  protected const ID_FIELD = 'id';
  protected const LOCATE_FIELD = 'remote_id';
  protected const LOCATE_PROP = 'remoteId';
  protected const MAP_CREATION = [
    'remoteId' => 'remote_id',
    'title' =>  'title',
    'categoryId' => ['direct', 'category_id', 'getVocEntryId', 'categories'],
    'countryId' => ['direct', 'country_id', 'getVocEntryId', 'countries'],
    'prodYear' => 'prod_year',
    'imageUrl' => 'image',
    'abstract' => 'abstract',
    'anchor' => 'anchor',
    'history' => ['depend', 'makeDependentList', '\App\HistoryListDeps', ['id', 'mark_date', 'position']],
    'stats' => ['depend', 'makeDependentList', '\App\StatsListDeps', ['id', 'rate', 'votes']],
  ];

  protected const VOC_FIELDS = [
    'category' => ['source' => 'category_id', 'vocabulary' => 'categories'],
    'country' => ['source' => 'country_id', 'vocabulary' => 'countries'],
    'section' => ['source' => 'anchor', 'vocabulary' => 'anchors'],
  ];
  protected const DEP_FIELDS = [
    'history' => '\App\HistoryListDeps',
    'stats' => '\App\StatsListDeps',
  ];
  protected const CALC_FIELDS = [
    'totalVotes' => 'calcTotalVotes',
    'averageRate' => 'calcAverageRate',
    'lastPosition' => 'calcLastPosition',
  ];

  use \App\base\BaseModelTrait;

  protected $_fields;
  protected $delayed;

  function __construct(\App\System $system)
  {
    $this->_fields = [];
    $this->delayed = [];
    parent::__construct($system);
    $this->checkVocExists('categories', '\App\CategoriesListVoc');
    $this->checkVocExists('countries', '\App\CountriesListVoc');
    $this->checkVocExists('anchors', '\App\AnchorsListVoc');
  }

  public function jsonSerialize() {
    return [
      self::ID_FIELD => $this->_fields[self::ID_FIELD],
      'remote_id' => $this->_fields['remote_id'],
      'title' => $this->_fields['title'],
      'image' => basename($this->_fields['image']),
    ];
  }

  public function ratingList($category, $anchor = null, $draw = 1, $columns, $order = [['column' => '1', 'dir' => 'asc']], $start = 0, $length = 10, $date = null): array
  {
    $items = [];
    $pdo = self::$system->pdo;

    $conditions = ["m.category_id = {$category}"];
    if ($anchor) {
      $conditions[] = "m.anchor = {$anchor}";
    }
    if (!$date) {
      $sql = 'SELECT MAX(h.mark_date) FROM movies m JOIN movies_history h ON h.movie_id = m.id ' .
        ($conditions ? "WHERE " . implode(' AND ', $conditions ) . ' ' : '');
      $stmt = $pdo->query($sql);
      $date = $stmt->fetchColumn();
    }
    else {
      $date = (new \DateTime($date))->format('Y-m-d');
    }
    $conditions[] = "h.mark_date = \"{$date}\"";

    $orderStr = array_reduce($order, function($carry, $item) use ($columns) {
      return $carry .= (empty($carry) ? '' : ', ') .
        $columns[$item['column']]['data'] . ' ' . $item['dir'];
    }, '');

    $sql = 'SELECT COUNT(m.id)' .
      'FROM movies m ' .
      'JOIN movies_history h ON h.movie_id = m.id ' .
      ($conditions ? "WHERE " . implode(' AND ', $conditions ) . ' ' : '');
    $stmt = $pdo->query($sql);
    $total = $stmt->fetchColumn();

    $sql = 'SELECT m.id, m.title, m.country_id, m.prod_year, m.image, h.mark_date, h.position, c.name AS country, SUM(s.votes) AS votes, ROUND(SUM(s.rate * s.votes) / SUM(s.votes), 4) AS rate, ROUND(SUM(s.rate * s.votes) / SUM(s.votes), 1) AS rate_calc ' .
      'FROM movies m ' .
      'JOIN countries c ON c.id = m.country_id ' .
      'JOIN movies_history h ON h.movie_id = m.id ' .
      'LEFT JOIN movies_stats s ON s.movie_id = m.id ' .
      ($conditions ? "WHERE " . implode(' AND ', $conditions ) . ' ' : '') .
      'GROUP BY m.id ' .
      "ORDER BY {$orderStr} " .
      "LIMIT {$start}, {$length}";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $config = self::$system->conf->common;
    array_walk($data, function(&$item) use ($config) {
      $id = $item['id'];
      $link = "/movie/{$id}";
      $item['title'] = "<a class=\"modal-link\" data-id=\"$id\" href=\"{$link}\">" . $item['title'] . '</a>';
      $item['image'] = "<a class=\"modal-link\" data-id=\"$id\" href=\"{$link}\">" . '<img width="75px" src="/' .
        $item['image'] . '" />' . '</a>';
    });

    return [
      'debug' => $sql,
      'recordsTotal' => $total,
      'recordsFiltered' => $total,
      'draw' => $draw,
      'data' => $data,
    ];
  }

  private function calcTotalVotes()
  {
    return array_reduce($this->_fields['stats'], function($carry, $item) {
      return $carry + $item['votes'];
    }, 0);
  }

  private function calcAverageRate()
  {
    $totals = array_reduce($this->_fields['stats'], function($carry, $item) {
      return [
        $carry[0] + $item['votes'] * $item['rate'], 
        $carry[1] + $item['votes']
      ];
    }, [0, 0]);
    return $totals[1] > 0 ? $totals[0] / $totals[1] : null;
  }

  private function calcLastPosition()
  {
    
    return array_reduce($this->_fields['history'], function($carry, $item) {
      return \DateTime::createFromFormat('Y-m-d', $carry['mark_date']) < \DateTime::createFromFormat('Y-m-d', $item['mark_date']) 
        ? $item : $carry;
    }, ['id' => 0, 'mark_date' => '1970-01-01']);
  }

}
