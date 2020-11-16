<?php

namespace App;

class ParserController
{
  protected static $system;
  protected $helper;

  function __construct(\App\System $system)
  {
    self::$system = $system;
    $this->formatInit();
    $this->helper = new ParserHelper($system->conf->common);
  }

  protected function formatInit() {}
  protected function formatProcess(array $data) {}
  protected function formatResult(array $data) {}

  public function parseList(int $start, int $quantity = 1)
  {
    $list = $this->fillList($start, $quantity);
    $result = $this->parse($list);
    $this->formatResult($result);
    return $result;
  }

  public function parseContinue()
  {
    $start = (int) self::$system->dbconf->getVariable('parser.movies.last');
    $quantity = self::$system->conf->common->parser['chunksize'] ?? 10;
    $list = $this->fillList($start, $quantity);
    $result = $this->parse($list);
    self::$system->dbconf->setVariable('parser.movies.last', $start + $quantity);
    $this->formatResult($result);
    return $result;
  }

  public function parseTop()
  {
    $start = (int) self::$system->dbconf->getVariable('parser.top.last');
    $quantity = self::$system->conf->common->parser['chunksize'] ?? 10;
    $list = $this->helper->getTopList($start, $quantity);
    if (!empty($list)) {
      $result = $this->parse($list);
      self::$system->dbconf->setVariable('parser.top.last', $start + $quantity);
      $this->formatResult($result);
    }
    return $result ?? false;
  }

  public function parseTopSeries($anchor)
  {
    $lasts = self::$system->dbconf->getVariable('parser.top_series.last');
    $start = (int) @$lasts[$anchor];
    $quantity = 50;

    $list = $this->helper->getTopSeriesList($anchor, $start);
    $result = [];
    if (!empty($list)) {
      $result = $this->parse($list, [
        'anchor' => $anchor,
      ]);
      $lasts[$anchor] = $start + $quantity;
      self::$system->dbconf->setVariable('parser.top_series.last', $lasts);
    }
    $this->formatResult($result);
    return $result ?? false;
  }

  protected function parse($list, $addons = [])
  {
    $result = [];
    $delay = self::$system->conf->common->parser['delay'] * 1000000;

    array_walk($list, function($id) use (&$result, $delay, $addons) {
      $data = $this->helper->parseOne($id);
      if (!is_array($data) || empty($data)) {
        $result[] = ['status' => false, 'remote_id' => $id];
        return;
      }
      $data = array_merge($data, $addons);

      $model = MovieModel::locate($data, self::$system);
      if (is_object($model)) {
        $model->apply($data)->save();
      }
      else {
        $model = MovieModel::createFrom($data, self::$system);
      }
      $entry = ['status' => true, 'data' => $model];
      $result[] = $entry;
      $this->formatProcess($entry);

      if ($delay) {
        usleep($delay);
      }
    });
    return $result;
  }

  private function fillList($start, $quantity) {
    $result = [];
    while ($quantity-- > 0)
      $result[] = $start++;
    return $result;
  }
}
