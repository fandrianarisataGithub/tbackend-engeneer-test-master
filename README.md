# Welfaire backend recruitment test

Welcome to Welfaire's recruitment test for backend engineer position.

This test mainly consists of PHPUnit tests which are testing not-implemented methods. You will have to implement these methods until all tests are passing.

## Setup

This test is shipped with its Docker-based local infrastructure. A Makefile is also provided for the sake of convenience.

### Requirements

- Your OS must be GNU/Linux-based, this is also a hard requirement for this position.
- Docker.
- GNU Make (usually installed).

### Make targets

- `run.test`: build the Docker image and install composer dependencies if needed, and then run the PHPUnit tests.
- `run.test.sales-data-analyzer`: same as `run.test` but only the sales data analyzer test is run.
- `run.test.php-code-processor`: same as `run.test` but only the PHP code processor test is run.
- `run.shell`: run a shell in a container based on the Docker image. It could be necessary for debugging purpose. To run the tests via this shell, simply type `./vendor/bin/phpunit .`.
- `clean`: remove the Docker image and all generated files (in vendor/ & .tmp/ directories).

## Exercises

### Sales data analyzer

The goal of this exercise is to implement a method which takes a data file's name as input parameter and must return an associative array representing some analysis results about the file's data.

#### Input file

##### Generation

The input file will be automatically generated the first time you run the test.

##### Format

The input file follows a plain text format with one record per line.  
Each record is a `|` separated list of field / value pairs.  
Each field / value pair follows this format `<field name>:<value>`.  
The value must be parsed as a base 10 integer.  

For instance, here are the first 4 lines of the input file:

```
$ head -4 .tmp/salesData.txt 
storeId:206|productId:518|price:11623|clientId:375|orderId:442
storeId:267|productId:570|price:16219|clientId:528|orderId:584
storeId:238|productId:579|price:13463|clientId:384|orderId:411
storeId:290|productId:580|price:19658|clientId:554|orderId:599
```

##### Content

The records stored in this file represent the sales (one per record) made by a chain of stores.  
Each sale record has a store Id, a product Id, a client Id, an order Id and a price stored in (EUR) cents.  
An order is a bunch of products bought by a client in a store.  
Beware that order identifiers are not globally unique, they are only unique for a single store.

#### Analysis

You have to implement the `SalesDataAnalyzer::analyze()` method which must analyze the file whose name is passed as argument.  
The result of the analysis must be returned as an associative array containing the following keys:
- `topStoresByRevenue`: the top 3 stores by revenue, each item must be an associative array with `storeId` and `revenue`<strong>*</strong> as keys.
- `topStoresBySaleCount`: the top 3 stores by sale count, each item must be an associative array with `storeId` and `count` as keys.
- `topStoresByAverageOrderAmount`: the top 3 stores by average order amount, each item must be an associative array with `storeId` and `averageOrderAmount`<strong>*</strong> as keys.
- `topProductsByRevenue`: the top 3 products by revenue, each item must be an associative array with `productId` and `revenue`<strong>*</strong> as keys.
- `topProductsBySaleCount`: the top 3 products by sale count, each item must be an associative array with `productId` and `count` as keys.

<strong>*</strong>: Contrary to the prices stored in the data file, the revenues and amounts must be represented in EUR (with their decimal part), not EUR cents.

If things are not clear just have a look at the expected results in [the PHPUnit test](tests/SalesDataAnalyzerTest.php#L28).

#### Implementation requirements

The implementation must be efficient in terms of memory & time consumption. The memory consumption must also be scalable with a sublinear complexity.  
If the implementation happens to be too greedy and exceeded any of the memory & time thresholds the test will fail.

### PHP code processor

The goal of this exercise is to implement a PHP code processor as a method receiving the PHP code to process via string argument and which must return the processed PHP code.  

#### Description of the expected processing

The purpose of this processor is to make the existing constructor of a class private and ensure that a static method named `create` exists in order to construct and return an instance of the class by calling the private constructor.  

As expected, the `create` method must have the same parameters as the constructor.

See the different [test cases](tests/PhpCodeProcessorTest.php#L14) for a detailed view of the expected behaviors of this processor.

#### Requirements

The processor implementation must rely on [Nikita Popov's PHP parser library](https://github.com/nikic/PHP-Parser).
