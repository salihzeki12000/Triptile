<?php

namespace Drupal\russian_trains_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

abstract class SqlBaseTranslations extends SqlBase  {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($row->getSourceProperty('lang') == 'cn') {
      $row->setSourceProperty('lang', 'zh-hans');
    }
    else if ($row->getSourceProperty('lang') == 'jp') {
      $row->setSourceProperty('lang', 'ja');
    }
    return parent::prepareRow($row);
  }

}