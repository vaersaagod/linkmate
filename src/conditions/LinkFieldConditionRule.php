<?php

namespace vaersaagod\linkmate\conditions;

use Craft;
use craft\fields\conditions\EmptyFieldConditionRule;
use vaersaagod\linkmate\fields\LinkField;
use vaersaagod\linkmate\models\Link;
use yii\db\Expression;
use yii\db\QueryInterface;

/**
 * Class LinkFieldConditionRule
 *
 * "Is empty" / "has a value" rule for LinkMate fields.
 *
 * Matching an element in memory (field layout conditions) goes through `LinkField::isValueEmpty()`,
 * so each link type decides what counts as empty. Element queries (index filters) can't call PHP,
 * and LinkMate stores an empty link as a JSON object rather than NULL, so `modifyQuery()` rebuilds
 * the same check in SQL from the stored `type` and `value` keys.
 *
 * @package vaersaagod\linkmate\conditions
 */
class LinkFieldConditionRule extends EmptyFieldConditionRule
{
    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var LinkField[] $instances */
        $instances = $this->fieldInstances();

        $typeSql = $this->keySql($instances, 'type');
        $valueSql = $this->keySql($instances, 'value');

        // Link types that are empty without a value need one; the rest (e.g. a "no link" type) never are
        $typesNeedingValue = [];
        $typesAlwaysFilled = [];
        foreach ($instances[0]->getAllowedLinkTypes() as $linkTypeName => $linkType) {
            if ($linkType->isEmpty(new Link(['type' => $linkTypeName]))) {
                $typesNeedingValue[] = $linkTypeName;
            } else {
                $typesAlwaysFilled[] = $linkTypeName;
            }
        }

        // MySQL's `->>` returns the string "null" for a JSON null
        $notEmptyCondition = [
            'or',
            ['in', $typeSql, $typesAlwaysFilled],
            [
                'and',
                ['in', $typeSql, $typesNeedingValue],
                ['not in', $valueSql, ['', 'null']],
            ],
        ];

        $query->andWhere(
            $this->operator === self::OPERATOR_EMPTY ? ['not', $notEmptyCondition] : $notEmptyCondition
        );
    }

    /**
     * Returns a null-safe SQL expression for a key in the stored link JSON, across all field instances.
     *
     * Content migrated from Craft 4 can hold the link as a JSON-encoded string rather than an object.
     * Extracting the instance's whole value as text gives the link JSON either way, so the key is read
     * from that text instead of with a nested JSON path.
     *
     * @param LinkField[] $instances
     * @param string $key
     *
     * @return Expression
     */
    private function keySql(array $instances, string $key): Expression
    {
        $db = Craft::$app->getDb();
        $qb = $db->getQueryBuilder();
        $columns = array_map(
            static fn(LinkField $field) => $qb->jsonExtract('elements_sites.content', [$field->layoutElement->uid]),
            $instances
        );
        $linkSql = count($columns) === 1 ? $columns[0] : sprintf('COALESCE(%s)', implode(', ', $columns));

        $keySql = $db->getIsPgsql()
            ? "CASE WHEN $linkSql LIKE '{%' THEN ($linkSql)::jsonb->>'$key' END"
            : "JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID($linkSql), $linkSql, NULL), '$.$key'))";

        return new Expression("COALESCE($keySql, '')");
    }
}
