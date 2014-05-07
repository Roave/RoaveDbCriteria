<?php
namespace Roave\DbCriteria;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\Criteria;
use Zend\Db\Sql\Predicate\Expression as PredicateExpression;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\IsNotNull;
use Zend\Db\Sql\Predicate\IsNull;
use Zend\Db\Sql\Predicate\Like;
use Zend\Db\Sql\Predicate\NotIn;
use Zend\Db\Sql\Predicate\Operator;
use Zend\Db\Sql\Predicate\PredicateInterface;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Select;

class QueryExpressionVisitor extends ExpressionVisitor
{
    /**
     * @var string[]
     */
    private static $operatorMap = array(
        Comparison::EQ  => Operator::OP_EQ,
        Comparison::IS  => Operator::OP_EQ,
        Comparison::NEQ => Operator::OP_NE,
        Comparison::LT  => Operator::OP_LT,
        Comparison::LTE => Operator::OP_LTE,
        Comparison::GT  => Operator::OP_GT,
        Comparison::GTE => Operator::OP_GTE,
    );

    /**
     * @var string[]
     */
    private static $orderMap = array(
        Criteria::ASC  => Select::ORDER_ASCENDING,
        Criteria::DESC => Select::ORDER_DESCENDING,
    );

    /**
     * Converts Criteria expression to Query one based on static map.
     *
     * @param string $operator
     *
     * @return string|null
     */
    private static function convertComparisonOperator($operator)
    {
        return isset(self::$operatorMap[$operator]) ? self::$operatorMap[$operator] : null;
    }

    public function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();
        $value = $this->walkValue($comparison->getValue());
        $operator = $comparison->getOperator();
        $predicate = null;

        switch ($operator) {
            case Comparison::IN:
                if (!is_array($value)) {
                    throw new \RuntimeException('Value for expression IN must be an array');
                }

                // empty IN () causes SQL error. Evaluate "in empty array" as always false
                if (empty($value)) {
                    return new PredicateExpression('false');
                }

                return new In($field, $value);

            case Comparison::NIN:
                if (!is_array($value)) {
                    throw new \RuntimeException('Value for expression "not in" (NIN) must be an array');
                }

                // empty NOT IN () causes SQL error. Evaluate "not in empty array" as always true
                if (empty($value)) {
                    return new PredicateExpression('true');
                }

                return new NotIn($field, $value);

            case Comparison::CONTAINS:
                return new Like($field, '%' . $value . '%');
            case Comparison::EQ:
                // intentional fall-through
            case Comparison::IS:
                if ($value === null) {
                    return new IsNull($field);
                }
                // intentional fall-through
            case Comparison::NEQ:
                if ($value === null) {
                    return new IsNotNull($field);
                }
                // intentional fall-through
            default:
                $zendDbOperator = self::convertComparisonOperator($operator);
                if (!$zendDbOperator) {
                    throw new \RuntimeException("Unknown comparison operator: {$operator}");
                }

                return new Operator($field, $zendDbOperator, $value);
        }

    }

    /**
     * {@inheritDoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = array();

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        switch($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                return new PredicateSet($expressionList, PredicateSet::OP_AND);

            case CompositeExpression::TYPE_OR:
                return new PredicateSet($expressionList, PredicateSet::OP_OR);

            default:
                throw new \RuntimeException("Unknown composite " . $expr->getType());
        }
    }

    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    public function dispatch(Expression $expr)
    {
        $predicate = parent::dispatch($expr);
        if (!$predicate instanceof PredicateInterface) {
            throw new \DomainException("Expression passed to this visitor must produce Zend\Db predicate");
        }

        return $predicate;
    }

    /**
     * Apply limit, offset, and order to a select object
     *
     * @param Select   $select   The Select object to modify
     * @param Criteria $criteria The Criteria object to get limit/offset/order info from
     *
     * @return Select
     */
    public static function apply(Select $select, Criteria $criteria)
    {
        $maxResults = $criteria->getMaxResults();
        if ($maxResults !== null) {
            $select->limit($maxResults);
        }

        $firstResult = $criteria->getFirstResult();
        if ($firstResult !== null) {
            $select->offset($firstResult);
        }

        $orderings = $criteria->getOrderings();
        if ($orderings !== null) {
            $zendDbOrder = array();
            foreach ($orderings as $field => $order) {
                $zendDbOrder[$field] = self::convertOrder($order);
            }
            $select->order($zendDbOrder);
        }

        return $select;
    }

    /**
     * Converts Criteria expression to Query one based on static map.
     *
     * @param string $order
     *
     * @return string|null
     */
    private static function convertOrder($order)
    {
        return isset(self::$orderMap[$order]) ? self::$orderMap[$order] : null;
    }
}
