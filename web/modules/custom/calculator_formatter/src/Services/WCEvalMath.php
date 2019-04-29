<?php

namespace Drupal\calculator_formatter\Services;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class WCEvalMath.
 *
 * Supports basic math only (removed eval function).
 *
 * Based on An open source eCommerce plugin for WordPress.
 * http://www.woocommerce.com/
 */
class WCEvalMath {

  use StringTranslationTrait;

  /**
   * The stack service.
   *
   * @var \Drupal\calculator_formatter\Services\WCEvalMathStack
   */
  protected $stack;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(WCEvalMathStack $stack, MessengerInterface $messenger, TranslationInterface $string_translation) {
    $this->stack = $stack;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Variables (and constants).
   *
   * @var array
   */
  public static $v = ['e' => 2.71, 'pi' => 3.14];

  /**
   * User-defined functions.
   *
   * @var array
   */
  public static $f = [];

  /**
   * Constants.
   *
   * @var array
   */
  public static $vb = ['e', 'pi'];

  /**
   * Built-in functions.
   *
   * @var array
   */
  public static $fb = [];

  /**
   * Evaluate maths string.
   *
   * @param string $expr
   *   The math expression.
   *
   * @return mixed
   *   The result of the evaluation of the expression.
   */
  public function evaluate($expr) {
    $expr = trim($expr);
    if (substr($expr, -1, 1) == ';') {
      // Strip semicolons at the end.
      $expr = substr($expr, 0, strlen($expr) - 1);
    }
    // ===============
    // is it a variable assignment?
    if (preg_match('/^\s*([a-z]\w*)\s*=\s*(.+)$/', $expr, $matches)) {
      // Make sure we're not assigning to a constant.
      if (in_array($matches[1], self::$vb)) {
        return $this->logger("cannot assign to constant '$matches[1]'");
      }
      if (($tmp = $this->pfx($this->nfx($matches[2]))) === FALSE) {
        // Get the result and make sure it's good.
        return FALSE;
      }
      // If so, stick it in the variable array.
      self::$v[$matches[1]] = $tmp;
      // And return the resulting value.
      return self::$v[$matches[1]];
      // ===============
      // is it a function assignment?
    }
    elseif (preg_match('/^\s*([a-z]\w*)\s*\(\s*([a-z]\w*(?:\s*,\s*[a-z]\w*)*)\s*\)\s*=\s*(.+)$/', $expr, $matches)) {
      // Get the function name.
      $fnn = $matches[1];
      // Make sure it isn't built in.
      if (in_array($matches[1], self::$fb)) {
        return $this->logger("cannot redefine built-in function '$matches[1]()'");
      }
      // Get the arguments.
      $args = explode(",", preg_replace("/\s+/", "", $matches[2]));
      if (($stack = $this->nfx($matches[3])) === FALSE) {
        // See if it can be converted to postfix.
        return FALSE;
      }
      $stack_size = count($stack);
      // Freeze the state of the non-argument variables.
      for ($i = 0; $i < $stack_size; $i++) {
        $token = $stack[$i];
        if (preg_match('/^[a-z]\w*$/', $token) and !in_array($token, $args)) {
          if (array_key_exists($token, self::$v)) {
            $stack[$i] = self::$v[$token];
          }
          else {
            return $this->logger("undefined variable '$token' in function definition");
          }
        }
      }
      self::$f[$fnn] = ['args' => $args, 'func' => $stack];
      return TRUE;
      // ===============.
    }
    else {
      // Straight up evaluation, woo.
      return $this->pfx($this->nfx($expr));
    }
  }

  /**
   * Convert infix to postfix notation.
   *
   * @param string $expr
   *   The math expression.
   *
   * @return array|bool
   *   Tokens or FALSE if error.
   */
  public function nfx($expr) {

    $index = 0;
    $stack = $this->stack;
    // Postfix form of expression, to be passed to pfx()
    $output = [];
    $expr = trim($expr);

    $ops = ['+', '-', '*', '/', '^', '_'];
    $ops_r = [
      '+' => 0,
      '-' => 0,
      '*' => 0,
      '/' => 0,
      '^' => 1,
    // right-associative operator?
    ];
    $ops_p = [
      '+' => 0,
      '-' => 0,
      '*' => 1,
      '/' => 1,
      '_' => 1,
      '^' => 2,
    // Operator precedence.
    ];

    // We use this in syntax-checking the expression.
    $expecting_op = FALSE;
    // And determining when a - is a negation
    // make sure the characters are all good.
    if (preg_match("/[^\w\s+*^\/()\.,-]/", $expr, $matches)) {
      return $this->logger("illegal character '{$matches[0]}'");
    }

    // 1 Infinite Loop ;)
    while (1) {
      // Get the first character at the current index.
      $op = substr($expr, $index, 1);
      // Find out if we're currently at the beginning of a
      // number/variable/function/parenthesis/operand.
      $ex = preg_match('/^([A-Za-z]\w*\(?|\d+(?:\.\d*)?|\.\d+|\()/', substr($expr, $index), $match);
      // ===============
      // is it a negation instead of a minus?
      if ('-' === $op and !$expecting_op) {
        // Put a negation on the stack.
        $stack->push('_');
        $index++;
      }
      // We have to explicitly deny this, because it's legal on the stack.
      elseif ('_' === $op) {
        // But not in the input expression.
        return $this->logger("illegal character '_'");
        // ===============.
      }
      // Are we putting an operator on the stack?
      elseif ((in_array($op, $ops) or $ex) and $expecting_op) {
        // Are we expecting an operator but have a
        // number/variable/function/opening parenthesis?
        if ($ex) {
          $op = '*';
          // it's an implicit multiplication.
          $index--;
        }
        // Heart of the algorithm:
        while ($stack->count > 0 and ($o2 = $stack->last()) and in_array($o2, $ops) and ($ops_r[$op] ? $ops_p[$op] < $ops_p[$o2] : $ops_p[$op] <= $ops_p[$o2])) {
          // Pop stuff off the stack into the output.
          $output[] = $stack->pop();
        }
        // Many thanks: https://en.wikipedia.org/wiki/Reverse_Polish_notation#The_algorithm_in_detail
        // finally put OUR operator onto the stack.
        $stack->push($op);
        $index++;
        $expecting_op = FALSE;
        // ===============.
      }
      // Ready to close a parenthesis?
      elseif (')' === $op && $expecting_op) {
        // Pop off the stack back to the last (.
        while (($o2 = $stack->pop()) != '(') {
          if (is_null($o2)) {
            return $this->logger("unexpected ')'");
          }
          else {
            $output[] = $o2;
          }
        }
        // Did we just close a function?
        if (preg_match("/^([A-Za-z]\w*)\($/", $stack->last(2), $matches)) {
          // Get the function name.
          $fnn = $matches[1];
          // See how many arguments there were (cleverly stored on the stack,
          // thank you)
          $arg_count = $stack->pop();
          // Pop the function and push onto the output.
          $output[] = $stack->pop();
          // Check the argument count.
          if (in_array($fnn, self::$fb)) {
            if ($arg_count > 1) {
              return $this->logger("too many arguments ($arg_count given, 1 expected)");
            }
          }
          elseif (array_key_exists($fnn, self::$f)) {
            if (count(self::$f[$fnn]['args']) != $arg_count) {
              return $this->logger("wrong number of arguments ($arg_count given, " . count(self::$f[$fnn]['args']) . " expected)");
            }
          }
          // Did we somehow push a non-function on the stack? this should never
          // happen.
          else {
            return $this->logger("internal error");
          }
        }
        $index++;
        // ===============.
      }
      // Did we just finish a function argument?
      elseif (',' === $op and $expecting_op) {
        while (($o2 = $stack->pop()) != '(') {
          if (is_null($o2)) {
            // oops, never had a (.
            return $this->logger("unexpected ','");
          }
          else {
            // Pop the argument expression stuff and push onto the output.
            $output[] = $o2;
          }
        }
        // Make sure there was a function.
        if (!preg_match("/^([A-Za-z]\w*)\($/", $stack->last(2), $matches)) {
          return $this->logger("unexpected ','");
        }
        // Increment the argument count.
        $stack->push($stack->pop() + 1);
        // Put the ( back on, we'll need to pop back to it again.
        $stack->push('(');
        $index++;
        $expecting_op = FALSE;
        // ===============.
      }
      elseif ('(' === $op and !$expecting_op) {
        // That was easy.
        $stack->push('(');
        $index++;
        // ===============.
      }
      // Do we now have a function/variable/number?
      elseif ($ex and !$expecting_op) {
        $expecting_op = TRUE;
        $val = $match[1];
        // May be func, or variable w/ implicit multiplication against
        // parentheses...
        if (preg_match("/^([A-Za-z]\w*)\($/", $val, $matches)) {
          // it's a func.
          if (in_array($matches[1], self::$fb) or array_key_exists($matches[1], self::$f)) {
            $stack->push($val);
            $stack->push(1);
            $stack->push('(');
            $expecting_op = FALSE;
          }
          // it's a var w/ implicit multiplication.
          else {
            $val = $matches[1];
            $output[] = $val;
          }
        }
        // it's a plain old var or num.
        else {
          $output[] = $val;
        }
        $index += strlen($val);
        // ===============.
      }
      // Miscellaneous error checking.
      elseif (')' === $op) {
        return $this->logger("unexpected ')'");
      }
      elseif (in_array($op, $ops) and !$expecting_op) {
        return $this->logger("unexpected operator '$op'");
      }
      // I don't even want to know what you did to get here.
      else {
        return $this->logger("an unexpected error occurred");
      }
      if (strlen($expr) == $index) {
        // Did we end with an operator? bad.
        if (in_array($op, $ops)) {
          return $this->logger("operator '$op' lacks operand");
        }
        else {
          break;
        }
      }
      // Step the index past whitespace (pretty much turns whitespace.
      while (substr($expr, $index, 1) == ' ') {
        // Into implicit multiplication if no operator is there)
        $index++;
      }
    }
    // Pop everything off the stack and push onto output.
    while (!is_null($op = $stack->pop())) {
      if ('(' === $op) {
        // If there are (s on the stack, ()s were unbalanced.
        return $this->logger("expecting ')'");
      }
      $output[] = $op;
    }
    return $output;
  }

  /**
   * Evaluate postfix notation.
   *
   * @param mixed $tokens
   *   Tokens.
   *
   * @return mixed
   *   The result of the evaluation of the expression.
   */
  public function pfx($tokens) {
    if (FALSE == $tokens) {
      return FALSE;
    }
    $stack = $this->stack;

    // Nice and easy.
    foreach ($tokens as $token) {
      // If the token is a binary operator, pop two values off the stack,
      // do the operation, and push the result back on.
      if (in_array($token, ['+', '-', '*', '/', '^'])) {
        if (is_null($op2 = $stack->pop())) {
          return $this->logger("internal error");
        }
        if (is_null($op1 = $stack->pop())) {
          return $this->logger("internal error");
        }
        switch ($token) {
          case '+':
            $stack->push($op1 + $op2);
            break;

          case '-':
            $stack->push($op1 - $op2);
            break;

          case '*':
            $stack->push($op1 * $op2);
            break;

          case '/':
            if (0 == $op2) {
              return $this->logger('division by zero');
            }
            $stack->push($op1 / $op2);
            break;

          case '^':
            $stack->push(pow($op1, $op2));
            break;
        }
        // If the token is a unary operator, pop one value off the stack,
        // do the operation, and push it back on.
      }
      elseif ('_' === $token) {
        $stack->push(-1 * $stack->pop());
        // If the token is a function, pop arguments off the stack, hand them to
        // the function, and push the result back on.
      }
      elseif (!preg_match("/^([a-z]\w*)\($/", $token, $matches)) {
        if (is_numeric($token)) {
          $stack->push($token);
        }
        elseif (array_key_exists($token, self::$v)) {
          $stack->push(self::$v[$token]);
        }
        else {
          return $this->logger("undefined variable '$token'");
        }
      }
    }
    // When we're out of tokens, the stack should have a single element, the
    // final result.
    if (1 != $stack->count) {
      return $this->logger("internal error");
    }
    return $stack->pop();
  }

  /**
   * Logs an error.
   *
   * @param string $msg
   *   The error string.
   *
   * @return bool
   *   False.
   */
  private function logger($msg) {
    $this->messenger->addError($this->t('Service returns an error: @msg', ['@msg' => $msg]));
    return FALSE;
  }

}
