<?php

namespace Drupal\salesforce;

/**
 * Class SelectQuery
 *
 * @package Drupal\salesforce
 */
class SelectQuery {

  /**
   * @var array
   */
  protected $fields = [];

  /**
   * @var array
   */
  protected $order = [];

  /**
   * @var string
   */
  protected $objectType;

  /**
   * @var string
   */
  protected $limit;

  /**
   * @var array
   */
  protected $conditions = [];

  /**
   * Constructor which sets the query object type.
   *
   * @param string $object_type
   *   Salesforce object type to query.
   */
  public function __construct($object_type) {
    $this->objectType = $object_type;
  }

  /**
   * Adds fields for select
   *
   * @param string|array $field
   * @return static
   */
  public function field($field) {
    if (is_array($field)) {
      $this->fields = array_merge($this->fields, $field);
    }
    else {
      $this->fields[] = $field;
    }

    return $this;
  }

  /**
   * Add a condition to the query.
   * Function user is responsible for wrapping of strings into single quotes.
   *
   * @param string $field
   *   Field name.
   * @param mixed $value
   *   Condition value. Note that the caller must enclose strings in in quotes
   *   as required by the SF API.
   * @param string $operator
   *   Conditional operator. One of '=', '!=', '<', '>', 'LIKE, 'IN', 'NOT IN'.
   * @return static
   *
   * @todo Add support of OR
   */
  public function condition($field, $value, $operator = '=') {
    if (is_array($value)) {
      $value = "(" . implode(",", $value) . ")";

      // Set operator to IN if wasn't already changed from the default.
      if ($operator == '=') {
        $operator = 'IN';
      }
    }

    $this->conditions[] = [
      'field' => $field,
      'operator' => $operator,
      'value' => $value,
    ];

    return $this;
  }

  /**
   * Adds a complex condition.
   *
   * @param string $where
   * @return static;
   *
   * @todo Add support of OR
   */
  public function where($where) {
    $this->conditions[] = $where;
    return $this;
  }

  /**
   * Sets limit on this request.
   *
   * @param int $limit
   * @return static
   */
  public function limit($limit) {
    $this->limit = $limit;
    return $this;
  }

  /**
   * Adds a field used in order by statement.
   *
   * @param string $field
   * @return static
   */
  public function orderBy($field) {
    $this->order[] = $field;
    return $this;
  }

  /**
   * Implements PHP's magic toString().
   *
   * Function to convert the query to a string to pass to the SF API.
   *
   * @return string
   *   SOQL query ready to be executed the SF API.
   */
  public function __toString() {

    $query = 'SELECT+';
    $query .= implode(',', $this->fields);
    $query .= "+FROM+" . $this->objectType;

    if (count($this->conditions) > 0) {
      $where = [];
      foreach ($this->conditions as $condition) {
        $where[] = is_array($condition) ? implode('+', $condition) : $condition;
      }
      // @todo Add support of OR
      $query .= '+WHERE+' . implode('+AND+', $where);
    }

    if ($this->order) {
      $query .= "+ORDER BY+";
      $fields = [];
      foreach ($this->order as $field => $direction) {
        $fields[] = $field . ' ' . $direction;
      }
      $query .= implode(',+', $fields);
    }

    if ($this->limit) {
      $query .= "+LIMIT+" . (int) $this->limit;
    }

    return $query;
  }

}
