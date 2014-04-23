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
                $where->in();


        }

    }

}
