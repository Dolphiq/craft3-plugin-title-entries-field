<?php

/**
 * Craft Title Entries Field type plugin
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\titleentriesfield;

use Craft;
use craft\base\Plugin;
use craft\services\Fields;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Event;
use dolphiq\titleentriesfield\fields\TitleEntriesField;
use craft\helpers\Html as HtmlHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Json;


/**
 * LinkFieldPlugin class.
 */
class TitleEntriesFieldPlugin extends \craft\base\Plugin
{
    public static $plugin;

    public $hasCpSettings = false;

    // table schema version
    public $schemaVersion = '1.0.0';

    
    /**
     * init function.
     *
     * @access public
     * @return void
     */
    public function init()
    {
        parent::init();

        self::$plugin = $this;

        parent::init();

       // var_dump( Craft::$app->getFields());

        Craft::$app->getView()->hook('cp.elements.titleElementsFieldElement', function (&$context) {
            if (! isset($context['element'])) {
                return null;
            }
          /** @var Element $element */
          $element = $context['element'];
            if (! isset($context['context'])) {
                $context['context'] = 'index';
            }

            $imgHtml = '';
            $elementSize = 'small';

            $htmlAttributes = array_merge(
            $element->getHtmlAttributes($context['context']),
            [
                'class'        => 'element ' . $elementSize,
                'data-type'    => get_class($element),
                'data-id'      => $element->id,
                'data-site-id' => $element->siteId,
                'data-status'  => $element->getStatus(),
                'data-label'   => (string)$element,
                'data-url'     => $element->getUrl(),
                'data-level'   => $element->level,
            ]);
            if ($context['context'] === 'field') {
                $htmlAttributes['class'] .= ' removable';
            }
            if ($element::hasStatuses()) {
                $htmlAttributes['class'] .= ' hasstatus';
            }

            $html = '<div';
            foreach ($htmlAttributes as $attribute => $value) {
                $html .= ' ' . $attribute . ($value !== null ? '="' . HtmlHelper::encode($value) . '"' : '');
            }
            if (ElementHelper::isElementEditable($element)) {
                $html .= ' data-editable';
            }
            $html .= '>';
            if ($context['context'] === 'field' && isset($context['name'])) {
                $html .= '<input type="hidden" name="' . $context['name'] . '[]" value="' . HtmlHelper::encode(Json::encode(['id' => $element->id, 'linkFieldLabel' => $element->linkFieldLabel])) . '">';

                $html .= '<a class="delete icon" title="' . Craft::t('app', 'Remove') . '"></a> ';
            }
            if ($element::hasStatuses()) {
                $html .= '<span class="status ' . $context['element']->getStatus() . '"></span>';
            }
            $html .= $imgHtml;
            $html .= '<div class="label">';
            $html .= '<span class="title">';

            $label = HtmlHelper::encode($element);

            if ($context['context'] === 'index' && ($cpEditUrl = $element->getCpEditUrl())) {
                $cpEditUrl = HtmlHelper::encode($cpEditUrl);
                $html .= "<a href=\"{$cpEditUrl}\">{$label}</a>";
            } else {
                $html .= $label;
            }
            $html .= '</span>';

            $html .= '<span class="linkFieldLabel" style="padding-left: 15px; font-size:0.8em; font-style:italic">';

            $label = HtmlHelper::encode($element->linkFieldLabel);
            $html .= $label;

            $html .= '</span>';

            $html .= ' <a class="edit icon rename" title="' . Craft::t('app', 'Rename') . '"></a> ';
            $html .= '</div>';
            $html .= '</div>';

            return $html;
        });

        // Register our fields
        Event::on(
            Fields::className(),
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = TitleEntriesField::class;
            }
        );

        Craft::info('dolphiq/titleentriesfield plugin loaded', __METHOD__);
    }
}
