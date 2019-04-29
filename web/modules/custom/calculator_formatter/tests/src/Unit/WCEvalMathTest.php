<?php

namespace Drupal\Tests\calculator_formatter\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\calculator_formatter\Services\WCEvalMath;
use Drupal\calculator_formatter\Services\WCEvalMathStack;

/**
 * Simple test to ensure that expression are properly processed.
 *
 * @coversDefaultClass \Drupal\calculator_formatter\Services\WCEvalMath
 * @group calculator_formatter
 */
class WCEvalMathTest extends UnitTestCase {

  /**
   * The WCEvalMath service.
   *
   * @var \Drupal\calculator_formatter\Services\WCEvalMath
   */
  private $evalMath;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->evalMath = new WCEvalMath(
      new WCEvalMathStack(),
      $this->getMockBuilder(Messenger::class)->disableOriginalConstructor()->getMock(),
      $this->getMockBuilder(TranslationInterface::class)->getMock()
    );
  }

  /**
   * Ensure that illegal characters causes errors.
   *
   * @param string $input
   *   The input string.
   * @param bool $expected
   *   Expected result.
   *
   * @dataProvider evaluateDataProvider
   * @covers ::evaluate
   */
  public function testEvaluate($input, $expected) {
    $result = $this->evalMath->evaluate($input);
    $this->assertEquals($result, $expected);
  }

  /**
   * Provides test cases for testEvaluate.
   */
  public function evaluateDataProvider() {
    return [
      ['z+2*10-15+12', FALSE],
      ['10 .+ 20 - (30 + 15) * 5', FALSE],
    ];
  }

  /**
   * Ensure that infix to postfix notation converts properly.
   *
   * @param string $input
   *   The input string.
   * @param bool $expected
   *   Expected result.
   *
   * @dataProvider nfxDataProvider
   * @covers ::nfx
   */
  public function testNfx($input, $expected) {
    $result = $this->evalMath->nfx($input);
    $this->assertEquals($result, $expected);
  }

  /**
   * Provides test cases for testNfx.
   */
  public function nfxDataProvider() {
    return [
      [
        '2+2*10-15+12',
        ['2', '2', '10', '*', '+', '15', '-', '12', '+'],
      ],
      [
        '10 + 20 - 30 + 15 * 5',
        ['10', '20', '+', '30', '-', '15', '5', '*', '+'],
      ],
      [
        '2*2+10-12+5',
        ['2', '2', '*', '10', '+', '12', '-', '5', '+'],
      ],
    ];
  }

  /**
   * Ensure that postfix notation are properly evaluating.
   *
   * @param string $input
   *   The input string.
   * @param bool $expected
   *   Expected result.
   *
   * @dataProvider pfxDataProvider
   * @covers ::pfx
   */
  public function testPfx($input, $expected) {
    $result = $this->evalMath->pfx($input);
    $this->assertEquals($result, $expected);
  }

  /**
   * Provides test cases for testPfx.
   */
  public function pfxDataProvider() {
    return [
      [['2', '2', '10', '*', '+', '15', '-', '12', '+'], '19'],
      [['10', '20', '+', '30', '-', '15', '5', '*', '+'], '75'],
      [['2', '2', '*', '10', '+', '12', '-', '5', '+'], '7'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    unset($this->evalMath);
  }

}
