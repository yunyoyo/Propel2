<?php

/*
 *	$Id: SortableBehaviorTest.php 1356 2009-12-11 16:36:55Z francois $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Tests for SortableBehavior class
 *
 * @author		Massimiliano Arione
 * @version		$Revision$
 * @package		generator.engine.behavior
 */
class SortableBehaviorObjectBuilderModifierTest extends BookstoreTestBase
{
	protected function setUp()
	{
		parent::setUp();
		Table11Peer::doDeleteAll();
		$t1 = new Table11();
		$t1->setRank(1);
		$t1->setTitle('row1');
		$t1->save();
		$t2 = new Table11();
		$t2->setRank(4);
		$t2->setTitle('row4');
		$t2->save();
		$t3 = new Table11();
		$t3->setRank(2);
		$t3->setTitle('row2');
		$t3->save();
		$t4 = new Table11();
		$t4->setRank(3);
		$t4->setTitle('row3');
		$t4->save();
	}
	
	protected function getFixturesArray()
	{
		$c = new Criteria();
		$c->addAscendingOrderByColumn(Table11Peer::RANK_COL);
		$ts = Table11Peer::doSelect($c);
		$ret = array();
		foreach ($ts as $t) {
			$ret[$t->getRank()] = $t->getTitle();
		}
		return $ret;
	}
	
	public function testPreInsert()
	{
		Table11Peer::doDeleteAll();
		$t1 = new Table11();
		$t1->save();
		$this->assertEquals($t1->getRank(), 1, 'Sortable inserts new line in first position if no row present');
		$t2 = new Table11();
		$t2->setTitle('row2');
		$t2->save();
		$this->assertEquals($t2->getRank(), 2, 'Sortable inserts new line in last position');
	}
	
	public function testPreDelete()
	{
		$max = Table11Peer::getMaxPosition();
		$t3 = Table11Peer::retrieveByPosition(3);
		$t3->delete();
		$this->assertEquals($max - 1, Table11Peer::getMaxPosition(), 'Sortable rearrange subsequent rows on delete');
		$c = new Criteria();
		$c->add(Table11Peer::TITLE, 'row4');
		$t4 = Table11Peer::doSelectOne($c);
		$this->assertEquals(3, $t4->getRank(), 'Sortable rearrange subsequent rows on delete');
	}
	
	public function testIsFirst()
	{
		$first = Table11Peer::retrieveByPosition(1);
		$middle = Table11Peer::retrieveByPosition(2);
		$last = Table11Peer::retrieveByPosition(4);
		$this->assertTrue($first->isFirst(), 'isFirst() returns true for the first in the rank');
		$this->assertFalse($middle->isFirst(), 'isFirst() returns false for a middle rank');
		$this->assertFalse($last->isFirst(), 'isFirst() returns false for the last in the rank');
	}

	public function testIsLast()
	{
		$first = Table11Peer::retrieveByPosition(1);
		$middle = Table11Peer::retrieveByPosition(2);
		$last = Table11Peer::retrieveByPosition(4);
		$this->assertFalse($first->isLast(), 'isLast() returns false for the first in the rank');
		$this->assertFalse($middle->isLast(), 'isLast() returns false for a middle rank');
		$this->assertTrue($last->isLast(), 'isLast() returns true for the last in the rank');
	}

	public function testGetNext()
	{
		$t = Table11Peer::retrieveByPosition(3);
		$this->assertEquals(4, $t->getNext()->getRank(), 'getNext() returns the next object in rank');
		
		$t = Table11Peer::retrieveByPosition(4);
		$this->assertNull($t->getNext(), 'getNext() returns null for the last object');
	}

	public function testGetPrevious()
	{
		$t = Table11Peer::retrieveByPosition(3);
		$this->assertEquals(2, $t->getPrevious()->getRank(), 'getPrevious() returns the previous object in rank');

		$t = Table11Peer::retrieveByPosition(1);
		$this->assertNull($t->getPrevious(), 'getPrevious() returns null for the first object');
	}
	
	public function testInsertAtRank()
	{
		$t = new Table11();
		$t->setTitle('new');
		$t->insertAtRank(2);
		$this->assertEquals(2, $t->getRank(), 'insertAtRank() sets the position');
		$expected = array(1 => 'row1', 2 => 'new', 3 => 'row2', 4 => 'row3', 5 => 'row4');
		$this->assertEquals($expected, $this->getFixturesArray(), 'insertAtRank() shifts the entire suite');
	}

	/**
	 * @expectedException PropelException
	 */
	public function testInsertAtNegativeRank()
	{
		$t = new Table11();
		$t->insertAtRank(0);
	}

	/**
	 * @expectedException PropelException
	 */
	public function testInsertAtOverMaxRank()
	{
		$t = new Table11();
		$t->insertAtRank(5);
	}

	public function testInsertAtBottom()
	{
		$t = new Table11();
		$t->setTitle('new');
		$t->insertAtBottom();
		$this->assertEquals(5, $t->getRank(), 'insertAtBottom() sets the position to the last');
		$expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4', 5 => 'new');
		$this->assertEquals($expected, $this->getFixturesArray(), 'insertAtBottom() does not shift the entire suite');
	}

	public function testInsertAtTop()
	{
		$t = new Table11();
		$t->setTitle('new');
		$t->insertAtTop();
		$this->assertEquals(1, $t->getRank(), 'insertAtTop() sets the position to 1');
		$expected = array(1 => 'new', 2 => 'row1', 3 => 'row2', 4 => 'row3', 5 => 'row4');
		$this->assertEquals($expected, $this->getFixturesArray(), 'insertAtTop() shifts the entire suite');
	}

	public function testMoveToRank()
	{
		$t2 = Table11Peer::retrieveByPosition(2);
		$t2->moveToRank(3);
		$expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move up');
		$t2->moveToRank(1);
		$expected = array(1 => 'row2', 2 => 'row1', 3 => 'row3', 4 => 'row4');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move to the first rank');
		$t2->moveToRank(4);
		$expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move to the last rank');
		$t2->moveToRank(2);
		$expected = array(1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move down');
	}

	/**
	 * @expectedException PropelException
	 */
	public function testMoveToNewObject()
	{
		$t = new Table11();
		$t->moveToRank(2);
	}

	/**
	 * @expectedException PropelException
	 */
	public function testMoveToNegativeRank()
	{
		$t = Table11Peer::retrieveByPosition(2);
		$t->moveToRank(0);
	}

	/**
	 * @expectedException PropelException
	 */
	public function testMoveToOverMaxRank()
	{
		$t = Table11Peer::retrieveByPosition(2);
		$t->moveToRank(5);
	}

	public function testSwapWith()
	{
		$t2 = Table11Peer::retrieveByPosition(2);
		$t4 = Table11Peer::retrieveByPosition(4);
		$t2->swapWith($t4);
		$expected = array(1 => 'row1', 2 => 'row4', 3 => 'row3', 4 => 'row2');
		$this->assertEquals($expected, $this->getFixturesArray(), 'swapWith() swaps ranks of the two objects and leaves the other ranks unchanged');
	}

	public function testMoveUp()
	{
		$t3 = Table11Peer::retrieveByPosition(3);
		$res = $t3->moveUp();
		$this->assertTrue(is_array($res), 'moveUp() returns an array');
		$expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() swaps ranks with the object of higher rank');
		$t3->moveUp();
		$expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() swaps ranks with the object of higher rank');
		$res = $t3->moveUp();
		$this->assertFalse($res, 'moveUp() returns false when called on the object at the top');
		$expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() changes nothing when called on the object at the top');
	}

	public function testMoveDown()
	{
		$t2 = Table11Peer::retrieveByPosition(2);
		$res = $t2->moveDown();
		$this->assertTrue(is_array($res), 'moveDown() returns an array');
		$expected = array(1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() swaps ranks with the object of lower rank');
		$t2->moveDown();
		$expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() swaps ranks with the object of lower rank');
		$res = $t2->moveDown();
		$this->assertFalse($res, 'moveDown() returns false when called on the object at the bottom');
		$expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() changes nothing when called on the object at the bottom');
	}

	public function testMoveToTop()
	{
		$t3 = Table11Peer::retrieveByPosition(3);
		$res = $t3->moveToTop();
		$this->assertEquals(3, $res, 'moveToTop() returns the old position when successful');
		$expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveToTop() moves to the top');
		$res = $t3->moveToTop();
		$this->assertFalse($res, 'moveToTop() returns false when called on the top node');
		$expected = array(1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveToTop() changes nothing when called on the top node');
	}

	public function testMoveToBottom()
	{
		$t2 = Table11Peer::retrieveByPosition(2);
		$res = $t2->moveToBottom();
		$this->assertEquals(2, $res, 'moveToBottom() returns the old position when successful');
		$expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveToBottom() moves to the bottom');
		$res = $t2->moveToBottom();
		$this->assertFalse($res, 'moveToBottom() returns false when called on the bottom node');
		$expected = array(1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2');
		$this->assertEquals($expected, $this->getFixturesArray(), 'moveToBottom() changes nothing when called on the bottom node');
	}

}