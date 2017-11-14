<?php

namespace Drupal\master;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\Condition as SqlCondition;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Entity\Query\Sql\Condition;

/**
 * Implements entity query conditions for SQL databases.
 */
class EntityWhereCondition extends Condition {

  /**
   * The SQL entity query object this condition belongs to.
   *
   * @var \Drupal\Core\Entity\Query\Sql\Query
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public function compile($conditionContainer) {

    // If this is not the top level condition group then the sql query is
    // added to the $conditionContainer object by this function itself. The
    // SQL query object is only necessary to pass to Query::addField() so it
    // can join tables as necessary. On the other hand, conditions need to be
    // added to the $conditionContainer object to keep grouping.
    $sql_query = $conditionContainer instanceof SelectInterface ? $conditionContainer : $conditionContainer->sqlQuery;
    $tables = $this->query->getTables($sql_query);
    foreach ($this->conditions as $condition) {
      if (empty($condition['field']) && isset($condition['template'])) {
        $type = strtoupper($this->conjunction) == 'OR' ? 'LEFT' : 'INNER';
        $template = $condition['template'];
        foreach ($condition['fields'] as $key => $field_name) {
          $field = $tables->addField($field_name, $type, $condition['langcode']);
          $template = str_replace($key, $field, $template);
        }
        $conditionContainer->where($template, $condition['args']);
      }
      else if ($condition['field'] instanceof ConditionInterface) {
        $sql_condition = new SqlCondition($condition['field']->getConjunction());
        // Add the SQL query to the object before calling this method again.
        $sql_condition->sqlQuery = $sql_query;
        $condition['field']->compile($sql_condition);
        $conditionContainer->condition($sql_condition);
      }
      else {
        $type = strtoupper($this->conjunction) == 'OR' || $condition['operator'] == 'IS NULL' ? 'LEFT' : 'INNER';
        $field = $tables->addField($condition['field'], $type, $condition['langcode']);
        $condition['real_field'] = $field;
        static::translateCondition($condition, $sql_query, $tables->isFieldCaseSensitive($condition['field']));

        // Add the translated conditions back to the condition container.
        if (isset($condition['where']) && isset($condition['where_args'])) {
          $conditionContainer->where($condition['where'], $condition['where_args']);
        }
        else {
          $conditionContainer->condition($field, $condition['value'], $condition['operator']);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function where($template, $fields = [], $args = [], $langcode = NULL) {
    $this->conditions[] = array(
      'template' => $template,
      'fields' => $fields,
      'args' => $args,
      'langcode' => $langcode,
    );

    return $this;
  }

}
