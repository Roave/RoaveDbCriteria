# RoaveDbCriteria

[![Build Status](https://travis-ci.org/Roave/RoaveDbCriteria.svg?branch=master)](https://travis-ci.org/Roave/RoaveDbCriteria)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Roave/RoaveDbCriteria/badges/quality-score.png?s=fd048e8ddcea635af89106a4f340e585249ed256)](https://scrutinizer-ci.com/g/Roave/RoaveDbCriteria/)
[![Code Coverage](https://scrutinizer-ci.com/g/Roave/RoaveDbCriteria/badges/coverage.png?s=8eb4ae26ff6b163c3bf2392446c8c17d994cdb5a)](https://scrutinizer-ci.com/g/Roave/RoaveDbCriteria/)

v0.0.1

## Example Usage

```php
<?php
use Roave\DbCriteria\QueryExpressionVisitor;
use Doctrine\Common\Collections\Criteria;

class ContactMapper
{
    protected $visitor;
    protected $tableGateway;

    public function find(Criteria $citeria)
    {
        $select = $this->tableGateway->getSql()->select()
                       // You can apply your own Zend\Db\Sql query conditions
                       ->where(array('user_id' => $this->activeUser->id));

        // Then apply the criteria to the query
        $where  = $this->visitor->dispatch($criteria->getWhereExpression());
        $select->where($where);

        // Finally, apply limit, offset, order, and execute the select query
        QueryExpressionVisitor::apply($select, $criteria);

        return $this->tableGateway->selectWith($select);
    }
}

```


```php
<?php
use Doctrine\Common\Collections\Criteria;

$criteria = Criteria::create()
                ->where(Criteria::expr()->eq('first_name', 'Evan'))
                ->andWhere(Criteria::expr()->eq('last_name', 'Coury'));

$contacts = $contactMapper->find($criteria);
```

In practice, you can create domain-specific criteria objects and plenty of other cool things.
