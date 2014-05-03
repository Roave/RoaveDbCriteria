<?php
namespace RoaveTest\DbCriteria;

use PHPUnit_Framework_TestCase;
use Roave\DbCriteria\QueryExpressionVisitor;
use Doctrine\Common\Collections\Criteria;
use Zend\Db\Sql\Predicate\PredicateSet;

class QueryExpressionVisitorTest extends PHPUnit_Framework_TestCase
{
    public function testWhereClauseHasExpectedOperator()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('foo', 123));
        $visitor   = new QueryExpressionVisitor();
        $predicate = $visitor->dispatch($criteria->getWhereExpression());
        $this->assertEquals($predicate::OP_EQ, $predicate->getOperator());
    }

    public function testCompositeExpressionProducesProperPredicateSet()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('foo', 123));
        $criteria->andWhere(Criteria::expr()->eq('bar', '321'));

        $visitor   = new QueryExpressionVisitor();

        $predicateSet = $visitor->dispatch($criteria->getWhereExpression());
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\PredicateSet', $predicateSet);
        foreach ($predicateSet as $predicateChild) {
            $this->assertEquals(PredicateSet::OP_AND, $predicateChild[0]);
            $this->assertInstanceOf('Zend\Db\Sql\Predicate\PredicateInterface', $predicateChild[1]);
        }
    }

    public function testCompositeOrExpressionProducesProperPredicateSet()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('foo', 123));
        $criteria->orWhere(Criteria::expr()->eq('bar', '321'));

        $visitor   = new QueryExpressionVisitor();

        $predicateSet = $visitor->dispatch($criteria->getWhereExpression());
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\PredicateSet', $predicateSet);
        foreach ($predicateSet as $predicateChild) {
            $this->assertEquals(PredicateSet::OP_OR, $predicateChild[0]);
            $this->assertInstanceOf('Zend\Db\Sql\Predicate\PredicateInterface', $predicateChild[1]);
        }
    }

    public function testExpressionInWithEmptyArray()
    {
        $visitor   = new QueryExpressionVisitor();
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('foo', array()));

        $predicate = $visitor->dispatch($criteria->getWhereExpression());
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Expression', $predicate);
        $this->assertSame('false', $predicate->getExpression());
    }

    public function testExpressionNotInWithEmptyArray()
    {
        $visitor   = new QueryExpressionVisitor();
        $criteria = Criteria::create()
            ->where(Criteria::expr()->notIn('foo', array()));

        $predicate = $visitor->dispatch($criteria->getWhereExpression());
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Expression', $predicate);
        $this->assertSame('true', $predicate->getExpression());
    }

    public function testProducesInPredicate()
    {
        $visitor   = new QueryExpressionVisitor();
        $criteria  = Criteria::create()->where(Criteria::expr()->in('foo', array('bar', 'baz')));
        $predicate = $visitor->dispatch($criteria->getWhereExpression());

        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Expression', $predicate);
    }
}
