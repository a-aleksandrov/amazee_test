<?php

namespace Drupal\calculator_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\calculator_formatter\Services\WCEvalMath;

/**
 * Plugin implementation of the 'Calculator' formatter.
 *
 * @FieldFormatter(
 *   id = "calculator_formatter",
 *   label = @Translation("Calculator"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_long",
 *   }
 * )
 */
class CalculatorFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The WCEvalMath service.
   *
   * @var \Drupal\calculator_formatter\Services\WCEvalMath
   */
  protected $evalMath;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\calculator_formatter\Services\WCEvalMath $eval_math
   *   The WCEvalMath service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, WCEvalMath $eval_math) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->evalMath = $eval_math;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('calculator_formatter.wc_eval_math')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'calculator_formatter',
        '#type' => 'container',
        '#attributes' => [
          'class' => ['calculator-formatter-wrapper'],
        ],
        '#attached' => [
          'library' => [
            'calculator_formatter/calculator_formatter',
          ],
        ],
        '#result' => $this->evalMath->evaluate($item->value),
        '#expression' => $item->value,
      ];
    }

    return $elements;
  }

}
