<?php
namespace RoaveTest\DbCriteria;

use PHPUnit_Framework_TestCase;
use Roave\DbCriteria\QueryExpressionVisitor;
use Doctrine\Common\Collections\Criteria;

class QueryExpressionVisitorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider criteriaQueries
     */
    public function testWhereClauseHasExpectedOperator($criteria, $queries)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('ID', 123));
        $visitor   = new QueryExpressionVisitor('test');
        $where     = $visitor->dispatch($criteria->getWhereExpression());
        $predicate = $where->getPredicates()[0][1];
        $this->assertEquals($predicate::OP_EQ, $predicate->getOperator());
    }

    public function criteriaQueries()
    {
        return array(
            array(
                Criteria::create(),
                'SELECT...'
            ),
        );
    }
}
