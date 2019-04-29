<?php

namespace Drupal\calculator_formatter\Services;

/**
 * Class WCEvalMathStack.
 *
 * Based on An open source eCommerce plugin for WordPress.
 * http://www.woocommerce.com/
 */
class WCEvalMathStack {

  /**
   * Stack array.
   *
   * @var array
   */
  public $stack = [];

  /**
   * Stack counter.
   *
   * @var int
   */
  public $count = 0;

  /**
   * Push value into stack.
   *
   * @param mixed $val
   *   The value that is being pushed to stack.
   */
  public function push($val) {
    $this->stack[$this->count] = $val;
    $this->count++;
  }

  /**
   * Pop value from stack.
   *
   * @return mixed
   *   Pop value from stack or NULL.
   */
  public function pop() {
    if ($this->count > 0) {
      $this->count--;
      return $this->stack[$this->count];
    }
    return NULL;
  }

  /**
   * Get last value from stack.
   *
   * @param int $n
   *   Offset.
   *
   * @return mixed
   *   Returns value or null.
   */
  public function last($n = 1) {
    $key = $this->count - $n;
    return array_key_exists($key, $this->stack) ? $this->stack[$key] : NULL;
  }

}
