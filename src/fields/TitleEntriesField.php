<?php

/**
 * Craft linkfield plugin
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\titleentriesfield\fields;

use Craft;
use craft\fields\BaseRelationField;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\base\Field;
use dolphiq\titleentriesfield\elements\TitleEntriesFieldEntry;
use craft\base\FieldInterface;
use craft\helpers\Json;

class TitleEntriesField extends BaseRelationField implements FieldInterface
{

    /**
     * @var string Template to use for field rendering
     */
    protected $inputTemplate = 'linkfield/input/elementSelect';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('linkfield', 'Title Entries Field');
    }


    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return TitleEntriesFieldEntry::class;
    }

    /**
     * @inheritdoc
     */
    public function isEmpty($value): bool
    {
        /** @var ElementQueryInterface $value */
        // return $value->count() === 0;

        return false;
    }
    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add an entry');
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        // loading elements..
        // please change query to ll

        if ($value instanceof ElementQueryInterface) {
            return $value;
        }
        // return false;var_dump($value);
        /** @var Element|null $element */
        /** @var Element $class */
        $class = static::elementType();
        /** @var ElementQuery $query */
        $query = $class::find()
            ->siteId($this->targetSiteId($element));


        if(is_array($value)) {
            $is_json = false;
            $new_values = [];
            foreach ($value as $sortOrder => $targetElement) {

                if (is_string($targetElement) && strpos($targetElement, '{') !== false) {
                    $targetElement = Json::decode($targetElement);
                    $new_values[$sortOrder] = $targetElement['id'];
                    $is_json = true;
                }
            }
            if ($is_json) {

            }
        }


        // $value will be an array of element IDs if there was a validation error or we're loading a draft/version.
        if (is_array($value)) {
            $query
                ->id(array_values(array_filter($value)))
                ->fixedOrder();
        } else if (($value === NULL || $value !== '') && $element && $element->id) {

            $query->innerJoin(
                '{{%relations}} relations',
                [
                    'and',
                    '[[relations.targetId]] = [[elements.id]]',
                    [
                        'relations.sourceId' => $element->id,
                        'relations.fieldId' => $this->id,
                    ],
                    [
                        'or',
                        ['relations.sourceSiteId' => null],
                        ['relations.sourceSiteId' => $element->siteId]
                    ]
                ]
            );

            $query->addSelect('relations.linkTitle');
            if ($this->sortable) {
                $query->orderBy(['relations.sortOrder' => SORT_ASC]);
            }

            if (!$this->allowMultipleSources && $this->source) {
                $source = ElementHelper::findSource($class, $this->source);

                // Does the source specify any criteria attributes?
                if (isset($source['criteria'])) {
                    Craft::configure($query, $source['criteria']);
                }
            }
        } else {
            $query->id(false);
        }

        if ($this->allowLimit && $this->limit) {
            $query->limit($this->limit);
        }

        return $query;
    }
    
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var Element $element */
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
        }

        /** @var ElementQuery|array $value */
        $variables = $this->inputTemplateVariables($value, $element);
        return Craft::$app->getView()->renderTemplate($this->inputTemplate, $variables);
    }

    public function updateLinkFieldRelations(BaseRelationField $field, ElementInterface $source, array $targetIds)
    {

        /** @var Element $source */
        if (!is_array($targetIds)) {
            $targetIds = [];

        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Delete the existing relations
            $oldRelationConditions = [
                'and',
                [
                    'fieldId' => $field->id,
                    'sourceId' => $source->id,
                ]
            ];

            if ($field->localizeRelations) {
                $oldRelationConditions[] = [
                    'or',
                    ['sourceSiteId' => null],
                    ['sourceSiteId' => $source->siteId]
                ];
            }

            Craft::$app->getDb()->createCommand()
                ->delete('{{%relations}}', $oldRelationConditions)
                ->execute();

            // Add the new ones
            if (!empty($targetIds)) {
                $values = [];

                if ($field->localizeRelations) {
                    $sourceSiteId = $source->siteId;
                } else {
                    $sourceSiteId = null;
                }

                foreach ($targetIds as $sortOrder => $targetElement) {

                    if (is_numeric($targetElement)) {
                        $targetId = $targetElement;
                        $newLinkFieldLabel= NULL;
                    } else {
                        if (is_string($targetElement)) {
                            $targetElement = Json::decode($targetElement);
                        }

                        $targetId = $targetElement['id'];
                        $newLinkFieldLabel = NULL;
                        if (isset($targetElement['linkFieldLabel'])) {
                            $newLinkFieldLabel = $targetElement['linkFieldLabel'];
                        }
                    }
                    $values[] = [
                        $field->id,
                        $source->id,
                        $sourceSiteId,
                        $targetId,
                        $newLinkFieldLabel,
                        $sortOrder + 1
                    ];
                }

                $columns = [
                    'fieldId',
                    'sourceId',
                    'sourceSiteId',
                    'targetId',
                    'linkTitle',
                    'sortOrder'
                ];

                Craft::$app->getDb()->createCommand()
                    ->batchInsert('{{%relations}}', $columns, $values)
                    ->execute();
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
        /** @var ElementQuery $value */    {

        $value = $element->getFieldValue($this->handle);

        // $id will be set if we're saving new relations
        if ($value->id !== null) {
            $targetIds = $value->id ?: [];
        } else {
            $targetIds = $value->ids();
        }

        /** @var int|int[]|false|null $targetIds */
        $this->updateLinkFieldRelations($this, $element, $targetIds);

        Field::afterElementSave($element, $isNew);
    }
}