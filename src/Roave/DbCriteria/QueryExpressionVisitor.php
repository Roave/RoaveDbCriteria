<?php
namespace Roave\DbCriteria;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Zend\Db\Sql\Predicate\Operator;
use Zend\Db\Sql\Where;

class QueryExpressionVisitor extends ExpressionVisitor
{
    protected $operatorMap = array(
        Comparison::EQ  => Operator::OP_EQ,
        Comparison::IS  => Operator::OP_EQ,
        Comparison::NEQ => Operator::NE,
        Comparison::LT  => Operator::LT,
        Comparison::LTE => Operator::LTE,
        Comparison::GT  => Operator::GT,
        Comparison::GTE => Operator::GTE,
    );

    public function walkComparison(Comparison $comparison)
    {

        $where = new Where;

        $field = $comparison->getField();
        $value = $this->walkValue($comparison->getValue());
        $operator = $comparison->getOperator();

        switch ($operator) {
            case Comparison::IN:
                return $where->in();

            case Comparison::NIN:
                return $where->notIn();

            case Comparison::CONTAINS:
                return $where->like();

            case Comparison::EQ:
            case Comparison::IS:
            case Comparison::NEQ:
            case Comparison::GT:
            case Comparison::GTE:
            case Comparison::LT:
            case Comparison::LTE:
                $zendDbOperator = $this->getZendDbOperator($operator);
                $where->addPredicate(
                    new Operator($field, $zendDbOperator, $value),
                    Operator::OP_AND // @todo, probably needs to be dynamic
                );


            default:
                throw new \RuntimeException('Unknown comparison operator: ' . $comparison->getOperator());
        }

    }

    protected function getZendDbOperator($operator)
    {
        return $this->comparisonOperators[$comparison];
    }

    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    public function walkCompositeExpression(CompositeExpression $exp)
    {
        $expressionList = array();

        foreach ($exp->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }
    }
}
