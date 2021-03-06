<?php
namespace Czim\CmsModels\Test\ModelInformation\Analyzer\Database;

use Czim\CmsModels\ModelInformation\Analyzer\Database\MysqlDatabaseAnalyzer;

/**
 * Class MysqlDatabaseAnalyzerTest
 *
 * @group analysis
 */
class MysqlDatabaseAnalyzerTest extends AbstractDatabaseAnalyzerTestCase
{

    /**
     * {@inheritdoc}
     */
    protected function setDatabaseConnectionConfig($app)
    {
        $this->setDatabaseConnectionConfigForMysql($app);
    }

    /**
     * @test
     */
    function it_returns_column_information_for_a_table()
    {
        $analyzer = new MysqlDatabaseAnalyzer;
        $columns  = $analyzer->getColumns('test_columns');

        static::assertInternalType('array', $columns);
        static::assertCount(11, $columns);

        $keyed = array_combine(array_pluck($columns, 'name'), $columns);

        static::assertEquals(
            [
                'id', 'enum', 'string', 'nullable_string', 'text', 'bool',
                'integer', 'decimal', 'date', 'datetime', 'timestamp',
            ],
            array_keys($keyed)
        );

        static::assertEquals(
            [
                'name'     => 'id',
                'type'     => 'int',
                'length'   => 10,
                'values'   => false,
                'unsigned' => true,
                'nullable' => false,
            ],
            $keyed['id']
        );
        static::assertEquals(
            [
                'name'     => 'enum',
                'type'     => 'enum',
                'length'   => null,
                'values'   => ['a', 'b'],
                'unsigned' => false,
                'nullable' => false,
            ],
            $keyed['enum']
        );
        static::assertEquals(
            [
                'name'     => 'string',
                'type'     => 'varchar',
                'length'   => 128,
                'values'   => false,
                'unsigned' => false,
                'nullable' => false,
            ],
            $keyed['string']
        );
        static::assertEquals(
            [
                'name'     => 'nullable_string',
                'type'     => 'varchar',
                'length'   => 255,
                'values'   => false,
                'unsigned' => false,
                'nullable' => true,
            ],
            $keyed['nullable_string']
        );
        static::assertEquals(
            [
                'name'     => 'text',
                'type'     => 'text',
                'length'   => null,
                'values'   => false,
                'unsigned' => false,
                'nullable' => true,
            ],
            $keyed['text']
        );
        static::assertEquals(
            [
                'name'     => 'bool',
                'type'     => 'tinyint',
                'length'   => 1,
                'values'   => false,
                'unsigned' => false,
                'nullable' => false,
            ],
            $keyed['bool']
        );
        static::assertEquals(
            [
                'name'     => 'integer',
                'type'     => 'int',
                'length'   => 11,
                'values'   => false,
                'unsigned' => false,
                'nullable' => true,
            ],
            $keyed['integer']
        );
        static::assertEquals(
            [
                'name'     => 'decimal',
                'type'     => 'decimal',
                'length'   => null,
                'values'   => false,
                'unsigned' => false,
                'nullable' => false,
            ],
            $keyed['decimal']
        );
        static::assertEquals(
            [
                'name'     => 'date',
                'type'     => 'date',
                'length'   => null,
                'values'   => false,
                'unsigned' => false,
                'nullable' => true,
            ],
            $keyed['date']
        );
        static::assertEquals(
            [
                'name'     => 'datetime',
                'type'     => 'datetime',
                'length'   => null,
                'values'   => false,
                'unsigned' => false,
                'nullable' => true,
            ],
            $keyed['datetime']
        );
        static::assertEquals(
            [
                'name'     => 'timestamp',
                'type'     => 'timestamp',
                'length'   => null,
                'values'   => false,
                'unsigned' => false,
                'nullable' => true,
            ],
            $keyed['timestamp']
        );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function it_throws_an_exception_if_an_invalid_table_name_is_given()
    {
        $analyzer = new MysqlDatabaseAnalyzer;
        $analyzer->getColumns('\'; little bobby --drop tables');
    }

}
