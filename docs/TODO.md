# TODO

- [ ] confirmation should use transient field ~xxx
- [ ] ~xxx as transient
- [ ]

```php
$scope = $R();

$all = $scope('Foo');
$barOnly = $all->find(['foo' => 'bar']);
$single = $barOnly->find(1);

echo $single['foo'];
$single->set('foo', 'foo')->save();

$scope->commit();
$scope->rollback();

$collection = new Collection('Foo');

$query = $collection();
echo $query['foo'];
$query['foo'] = 'bar';
$query->save();

$query
    ->map(function ($each) {
        if ($each['foo'] === 'bar') {
            $each['foo'] = 'baz';
        }
        return $each;
    })->save();
```

```javascript

var scope = R();

var all = scope('TableFoo');
var barOnly = all.find({ foo: 'bar' });
var single = barOnly.find(1);

console.log(single.get('foo'));

single.set({
    foo: 'bax',
    bar: 'baz'
}).save();

scope('TableFoo')
    .new({ 'foo': 'bar' })
    .duplicate(10)
    .forEach(function (single) {
        single.set()
    });

scope.rollback();
scope.commit();

```
