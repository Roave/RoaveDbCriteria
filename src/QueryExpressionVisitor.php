<?php
namespace Roave\DbCriteria;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Zend\Db\Sql\Predicate\Operator;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Predicate\Like;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\NotIn;
use Zend\Db\Sql\Predicate\IsNull;
use Zend\Db\Sql\Predicate\IsNotNull;

class QueryExpressionVisitor extends ExpressionVisitor
{
    protected static $operatorMap = array(
        Comparison::EQ  => Operator::OP_EQ,
        Comparison::IS  => Operator::OP_EQ,
        Comparison::NEQ => Operator::OP_NE,
        Comparison::LT  => Operator::OP_LT,
        Comparison::LTE => Operator::OP_LTE,
        Comparison::GT  => Operator::OP_GT,
        Comparison::GTE => Operator::OP_GTE,
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
                // @todo $value should be an array?
                $predicate = new In($field, $value);
                break;

            case Comparison::NIN:
                // @todo $value should be an array?
                $predicate = new NotIn($field, $value);
                break;
            case Comparison::EQ:
            case Comparison::IS:
                if ($value === null) {
                    return new IsNull($field);
                }
                break;
            case Comparison::NEQ:
                if ($value === null) {
                    return new IsNotNull($field);
                }

            case Comparison::CONTAINS:
                return new Like($field, '%' . $value . '%');

            default:
                $zendDbOperator = self::converyComparisonOperator($operator);
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

        foreach ($exp->getExpressionList() as $child) {
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
}
