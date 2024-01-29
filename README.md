# isobaric/database
library for PHP PDO: MySQL / SQLServer

## 一、安装

```shell
composer install isobaric/database
```

## 二、使用

### 1. 创建类文件并继承数据库类

```php
class TestTable extends MySQL
{
    protected string $table = 'test_table';

    protected array $connection = [
        'username' => 'root',
        'password' => '123456',
        'database' => 'test_database',
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
    ];
} 
```

### 2. 通用DML
   
```php
// 数据表操作对象
$model = new TestTable();
```

#### (1) INSERT

```php
// 写入一条或多条记录并获取最近一次的记录ID
$model->insertGetId();

// 写入一条或多条记录
$model->insert();
```

#### (2) UPDATE
```php
// 更新记录
$model->update();

// 更新记录
$model->updateRaw();
```

#### (3) SELECT

```php
// 查询一条记录
$model->fetch();

// 查询全部记录
$model->fetchAll();

// 分页查询记录
$model->paginator();

// min()查询
$model->fetchMin();

// max()查询
$model->fetchMax();

// sum()查询
$model->fetchSum();

// avg()查询
$model->fetchAvg();

// count()查询
$model->fetchCount();

// distinct()查询
$model->fetchDistinct();
```

#### (4) DELETE

```php
// 删除记录
$model->delete();

// 清空记录
$model->truncate();
```

#### (4) 表达式条件

```php
// 写入记录并获取ID
$model->insertGetId(['field_a' => 'a', 'field_b' => 'b']);

// 写入多条记录
$model->insert([['field_a' => 'a_a', 'field_b' => 'b_b'], ['field_a' => 'a_a_a', 'field_b' => 'b_b_b_']]);

// 查询记录
$model->select(['field_a', 'field_b'])->where(['field_a' => 'a'])->limit(2)->orderBy('field_b')->fetchAll();

// 更新记录
$model->where(['id', '>', 1])->update(['field_b' => 'B', 'field_a' => 'A']);

// 删除记录
$model->where(['id' => 1])->delete();
```

### 3. MySQL DML

```php
// select ... for update
$model->lockForUpdate();

// lock in share mode
$model->lockForUpdate();

// replace into ...
$model->replaceGetId();

// replace into ...
$model->replace();
```

### 4. SQLServer DML

```php
// top
$model->select()->top()->fetchAll();
```

### 5. 事务
```php
// 事务开启
Database::beginTransaction();

// 事务提交
Database::commit();

// 事务回滚
Database::rollBack();
```

### 通用功能

1. 打印运行的SQL语句（SQL正常运行）
   ```php
   Database::print();
   // 或者
   $model->print();
   
   // 在fetchAll()执行之前将会打印其SQL语句
   $model->fetchAll();
   ```
   
2. 将执行的SQL操作输出为SQL语句（SQL不运行）
   ```php
   // 输出预处理SQL
   $model->toSql->fetchAll();
   
   // 输出完整SQL
   $model->toCompleteSql()->fetchAll();
   ```
   
3. 监听运行的SQL (SQL正常执行)
   ```php
   Database::listen(function ($sql, $prepare, $bindings) {
      // $sql：完整的SQL语句；
      // $prepare: 预处理SQL语句；
      // $bindings: 预处理SQL语句绑定的值；   
   });
   // 或者
   $model->listen(function ($sql, $prepare, $bindings) {
      // $sql：完整的SQL语句；
      // $prepare: 预处理SQL语句；
      // $bindings: 预处理SQL语句绑定的值；
   });
   ```
      