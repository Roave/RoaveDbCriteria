<?php
namespace RoaveTest\DbCriteria;

use PHPUnit_Framework_TestCase;
use Roave\DbCriteria\QueryExpressionVisitor;
use Doctrine\Common\Collections\Criteria;

class QueryExpressionVisitorTest extends PHPUnit_Framework_TestCase
{
    public function testSomething()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('ID', 123));
        $visitor = new QueryExpressionVisitor('test');
        $test = $visitor->dispatch($criteria->getWhereExpression());
        //var_dump($test->getPredicates());
        $this->assertTrue(true);
    }
}
