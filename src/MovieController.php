<?php

namespace App;

class MovieController extends \App\base\TwigController
{

  public function index()
  {
    $vars = [
      'page' => ['title' => 'Рейтинги фильмов/сериалов!'],
      'categories' => self::$system->conf->menu->top,
    ];
    $tpl = $this->getTemplateName('page', __CLASS__, __FUNCTION__);

    exit($this->render($tpl, $vars, true));
  }

  function rating($id, $anchor = null)
  {
    if (!$anchor) {
      $categories = new CategoriesListVoc(self::$system);
      $category = $categories->item($id);
    }
    else {
      $categories = new AnchorsListVoc(self::$system);
      $category = $categories->item($anchor);
    }

    $vars = [
      'page' => [
        'title' => 'Рейтинги в разделе ' . $category['name'],
        'styles' => '', 'scripts' => '', 'scripts_top' => '',
      ],
    ];

    $block_vars = [
      'source' => "/api/rating/$id" . ($anchor ? "/{$anchor}" : ''),
    ];
    $tpl = $this->getTemplateName('block', __CLASS__, 'list');
    $vars['page']['content'] = $this->render($tpl, $block_vars);

    $tpl = $this->getTemplateName('page', __CLASS__, __FUNCTION__);
    exit($this->render($tpl, $vars, true));
  }
  
  public function apiRating($id, $anchor = null)
  {
    $movies = new MovieModel(self::$system);
    $items = $movies->ratingList($id, $anchor,
      $_REQUEST['draw'], $_REQUEST['columns'], $_REQUEST['order'], $_REQUEST['start'], $_REQUEST['length'],
      null);
    header('Content-Type: application/json; charset=UTF-8', true, 200);
    exit(json_encode($items, JSON_UNESCAPED_UNICODE));
  }
  
  private function renderMovie($movie)
  {
    $tpl = $this->getTemplateName('modal', __CLASS__, 'content');
    return $this->render($tpl, [ 'movie' => $movie ]);
  }

  public function movie($id)
  {
    $movie = MovieModel::load($id, self::$system);
    $vars = [
      'page' => [
        'title' => $movie->title,
        'content' => $this->renderMovie($movie),
      ],
      'categories' => self::$system->conf->menu->top,
    ];
    $tpl = $this->getTemplateName('page', __CLASS__, __FUNCTION__);

    exit($this->render($tpl, $vars, true));
  }
  
  public function apiMovie($id)
  {
    $movie = MovieModel::load($id, self::$system);
    exit($this->renderMovie($movie));
  }

}
