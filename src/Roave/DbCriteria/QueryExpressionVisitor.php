<?php
namespace Roave\DbCriteria;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Zend\Db\Sql\Where;

class QueryExpressionVisitor extends ExpressionVisitor
{
    public function walkCompositeExpression(CompositeExpression $exp)
    {
        $expressionList = array();

        foreach ($exp->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }
    }

    public function walkComparison(Comparison $comparison)
    {

        $where = new Where;

        switch ($comparison->getOperator()) {
            case Comparison::IN:
                return $where->in();

            case Comparison::NIN:
                return $where->notIn();

            case Comparison::EQ:
            case Comparison::IS:
                return $where->equalTo();

            case Comparison::NEQ:
                return $where->notEqualTo();

            case Comparison::CONTAINS:
                return $where->like();

            case Comparison::GT:
                return $where->greaterThan();

            case Comparison::GTE:
                return $where->greaterThanOrEqualTo();

            case Comparison::LT:
                return $where->lessThan();

            case Comparison::LTE:
                return $where->lessThanOrEqualTo();

            default:
                throw new \RuntimeException('Unknown comparison operator: ' . $comparison->getOperator());
        }

    }

}
