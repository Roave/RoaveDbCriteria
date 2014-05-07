<?php
namespace RoaveTest\DbCriteria;

use PHPUnit_Framework_TestCase;
use Roave\DbCriteria\QueryExpressionVisitor;
use Doctrine\Common\Collections\Criteria;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Select;

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

    public function testProducesNullWithEqNullCriteria()
    {
        $visitor   = new QueryExpressionVisitor();
        $criteria  = Criteria::create()->where(Criteria::expr()->eq('foo', null));
        $predicate = $visitor->dispatch($criteria->getWhereExpression());

        $this->assertInstanceOf('Zend\Db\Sql\Predicate\IsNull', $predicate);
    }

    public function testProducesNotNullWithNeqNullCriteria()
    {
        $visitor   = new QueryExpressionVisitor();
        $criteria  = Criteria::create()->where(Criteria::expr()->neq('foo', null));
        $predicate = $visitor->dispatch($criteria->getWhereExpression());

        $this->assertInstanceOf('Zend\Db\Sql\Predicate\IsNotNull', $predicate);
    }

    public function testCriteriaWithMaxResultsAppliesLimitToSelect()
    {
        $expected = 10;
        $select = new Select;
        $criteria  = Criteria::create()->setMaxResults($expected);
        QueryExpressionVisitor::apply($select, $criteria);

        $actual = $select->getRawState(Select::LIMIT);
        $this->assertEquals($expected, $actual);
    }

    public function testCriteriaWithFirstResultAppliesOffsetToSelect()
    {
        $expected = 10;
        $select = new Select;
        $criteria  = Criteria::create()->setFirstResult($expected);
        QueryExpressionVisitor::apply($select, $criteria);

        $actual = $select->getRawState(Select::OFFSET);
        $this->assertEquals($expected, $actual);
    }

    public function testCriteriaWithOrderByAppliesOrderToSelect()
    {
        $select = new Select;
        $criteria  = Criteria::create()->orderBy(array(
            'field1' => Criteria::ASC,
            'field2' => Criteria::DESC,
        ));

        QueryExpressionVisitor::apply($select, $criteria);

        $order = $select->getRawState(Select::ORDER);
        $expected = array(
            'field1' => Select::ORDER_ASCENDING,
            'field2' => Select::ORDER_DESCENDING,
        );
        $this->assertEquals($expected, $order);
    }

    public function testProducesInPredicate()
    {
        $visitor   = new QueryExpressionVisitor();
        $criteria  = Criteria::create()->where(Criteria::expr()->in('foo', array('bar', 'baz')));
        $predicate = $visitor->dispatch($criteria->getWhereExpression());

        $this->assertInstanceOf('Zend\Db\Sql\Predicate\In', $predicate);
    }

    public function testProducesNotInPredicate()
    {
        $visitor   = new QueryExpressionVisitor();
        $criteria  = Criteria::create()->where(Criteria::expr()->notIn('foo', array('bar', 'baz')));
        $predicate = $visitor->dispatch($criteria->getWhereExpression());

        $this->assertInstanceOf('Zend\Db\Sql\Predicate\NotIn', $predicate);
    }
}
