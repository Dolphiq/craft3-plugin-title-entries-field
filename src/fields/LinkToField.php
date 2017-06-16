<?php

/**
 * Craft linkfield plugin
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\linkfield\fields;

use Craft;
use craft\fields\BaseRelationField;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\base\Field;
use dolphiq\linkfield\elements\LinkFieldEntry;
use craft\base\FieldInterface;


class LinkToField extends BaseRelationField implements FieldInterface
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
        return Craft::t('linkfield', 'Link To Field');
    }


    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return LinkFieldEntry::class;
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

        /** @var Element|null $element */
        /** @var Element $class */
        $class = static::elementType();
        /** @var ElementQuery $query */
        $query = $class::find()
            ->siteId($this->targetSiteId($element));

        // $value will be an array of element IDs if there was a validation error or we're loading a draft/version.
        if (is_array($value)) {
            $query
                ->id(array_values(array_filter($value)))
                ->fixedOrder();
        } else if ($value !== '' && $element && $element->id) {

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

            $query->addSelect('relations.linkFieldLabel');
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


    public function updateLinkFieldRelations(BaseRelationField $field, ElementInterface $source, array $targetIds)
    {
        /** @var Element $source */
        if (!is_array($targetIds)) {
            $targetIds = [];
        }

        // Prevent duplicate target IDs.
        $targetIds = array_unique($targetIds);

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

                foreach ($targetIds as $sortOrder => $targetId) {
                    $newLinkFieldLabel = NULL;

                    //var_dump(Craft::$app->request());

                    $labelField =  $this->handle . '_LinkFieldLabel_' . $targetId;

                    $fieldsArray = $_REQUEST[$source->getFieldParamNamespace()];

                    if($fieldsArray && isset($fieldsArray[$labelField]) && $fieldsArray[$labelField]!='') {
                        $newLinkFieldLabel = $fieldsArray[$labelField];
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
                    'linkFieldLabel',
                    'sortOrder'
                ];

               // var_dump($values);
               // die();
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
        //Craft::$app->getRelations()->saveRelations($this, $element, $targetIds);
        $this->updateLinkFieldRelations($this, $element, $targetIds);

        Field::afterElementSave($element, $isNew);
    }
}