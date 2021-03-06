<?php

/**
 * @file
 * Contains \Devour\Tests\Table\TableTest.
 */

namespace Devour\Tests\Table;

use Devour\Table\Table;
use Devour\Tests\DevourTestCase;

/**
 * @covers \Devour\Table\Table
 */
class TableTest extends DevourTestCase {

  protected $table;
  protected $rows;

  public function setUp() {
    $this->table = new Table();
    $this->rows = [
      ['a1', 'b1', 'c1'],
      ['a2', 'b2', 'c2'],
      ['a3', 'b3', 'c3'],
    ];
  }

  public function testTable() {
    // Test fields.
    $this->table->setField('field 1', 1234);
    $this->assertSame(1234, $this->table->getField('field 1'));
    $this->assertNull($this->table->getField('field does not exist'));

    // Test adding.
    foreach ($this->rows as $row) {
      $this->table->getNewRow()->setData($row);
    }

    // Test Countable interface.
    $this->assertSame(count($this->rows), count($this->table));

    // Test ArrayAccess interface.
    foreach ($this->rows as $delta => $row) {
      $this->assertSame($row, $this->table[$delta]->getData());
    }

    // Test shift.
    $this->assertSame($this->rows[0], $this->table->shift()->getData());

    // Test pop.
    $this->assertSame($this->rows[2], $this->table->pop()->getData());
  }

  public function testTableIteration() {
    foreach ($this->rows as $row) {
      $this->table->getNewRow()->setData($row);
    }

    // Test Iterable interface.
    foreach ($this->table as $row) {
      $this->assertSame(array_shift($this->rows), $row->getData());
    }

    if (!defined('HHVM_VERSION')) {
      $this->assertTrue($this->table->isEmpty());
    }
  }

}
