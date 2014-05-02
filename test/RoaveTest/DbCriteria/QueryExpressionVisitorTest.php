<?php
namespace RoaveTest\DbCriteria;

use PHPUnit_Framework_TestCase;
use Roave\DbCriteria\QueryExpressionVisitor;
use Doctrine\Common\Collections\Criteria;

class QueryExpressionVisitorTest extends PHPUnit_Framework_TestCase
{
    public function testWhereClauseHasExpectedOperator()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('ID', 123));
        $visitor   = new QueryExpressionVisitor();
        $predicate     = $visitor->dispatch($criteria->getWhereExpression());
        $this->assertEquals($predicate::OP_EQ, $predicate->getOperator());
    }
}
