<?php

namespace App\base;

class TwigController
{
  protected static $system;

  protected $loader;
  public $tpl;

  function __construct(\App\System $system)
  {
    self::$system = $system;
    $this->loader = new \Twig\Loader\FilesystemLoader(self::$system->conf->common->engine['tpl_path']);
    $this->tpl = new \Twig\Environment($this->loader, [
      'autoescape' => false,
      'cache' => false, //self::$system->conf->common->engine['cache_path'],
      'auto_reload' => true,
      'debug' => true,
    ]);
    $this->tpl->addExtension(new \Twig\Extension\DebugExtension());
  }

  public function getTemplateName(string $entry, string $section, string $method = ''): string {
    $basename = strtolower(preg_replace(['/^App\\\\(.+)$/', '/^(.+)Controller$/'], ['$1', '$1'], $section));
    $stack = [$entry, $basename, $method];
    $facilities = [];
    $name = '';
    $dl = '';
    foreach ($stack as $item) {
      if (!empty($item)) {
        $name .= $dl . $item;
        $dl .= '-';
        $facilities[] = $name;
      }
    }
    $facilities = array_reverse($facilities);

    foreach ($facilities as $name) {
      $filename = realpath(self::$system->conf->common->engine['tpl_path'] . '/' . $name . '.' .
        self::$system->conf->common->engine['tpl_ext']);
      if (file_exists($filename)) {
        return $name;
      }
    }
    throw new \Exception("Invalid template engine entry: '{$entry}'.");
  }

  public function getMenu(): array
  {
    $menu = [];
    foreach (self::$system->conf->menu as $name => $entry) {
      array_walk($entry, function(&$item) {
        if ($item['link'] == self::$system->path) {
          $item['class'] = ' active';
        }
      });
      $template = $this->getTemplateName('menu', $name);
      $menu[$name] = $this->render($template, ['menu' => $entry]);
    }
    return $menu;
  }

  public function render(string $template, array $vars, bool $final = false)
  {
    if ($final) {
      $vars['menu'] = $this->getMenu();
    }
    $tplName = $template . '.' . self::$system->conf->common->engine['tpl_ext'];
    return($this->tpl->render($tplName, $vars));
  }

}
