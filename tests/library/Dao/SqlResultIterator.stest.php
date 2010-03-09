<?php


	require_once(dirname(dirname(__FILE__)) . '/setup.php');

	require_once(LIBRARY_ROOT_PATH . 'Dao/SqlResultIterator.php');
	require_once(LIBRARY_ROOT_PATH . 'Dao/DaoInterface.php');
	require_once(LIBRARY_ROOT_PATH . 'DatabaseResult/DatabaseResultInterface.php');



	class SqlResultIterator_Test extends Snap_UnitTestCase {

		protected $numRows = 88;

		protected $iterator;
		protected $result;
		protected $dao;

		protected $dataObject = 'abcdefg';

	    public function setUp() {
			$this->result = $this->mock('DatabaseResultInterface')
				->setReturnValue('numRows', $this->numRows)
				->setReturnValue('fetchArray', array())
				->listenTo('fetchArray')
				->construct();
			$this->dao = $this->mock('DaoInterface')
				->setReturnValue('getObjectFromDatabaseData', $this->dataObject)
				->construct();
			$this->iterator = new SqlResultIterator($this->result, $this->dao);
    	}

	    public function tearDown() {
			unset($this->iterator);
	    }

	    public function testCount() {
			return $this->assertEqual($this->iterator->count(), $this->numRows);
	    }

	    public function testKey() {
			return $this->assertEqual($this->iterator->key(), 1);
	    }

		public function testCurrentLeavesKeyTo1() {
			$this->iterator->current();
			return $this->assertEqual($this->iterator->key(), 1);
		}

		public function testNextSetsKeyTo2() {
			$this->iterator->next();
			return $this->assertEqual($this->iterator->key(), 2);
		}

	    public function testRewindSetsKeyTo1() {
			$this->iterator->next();
			$this->iterator->next();
			$this->iterator->next();
			$this->iterator->rewind();
			return $this->assertEqual($this->iterator->key(), 1);
	    }


		public function testCurrentCallsFetchArray() {
			$this->iterator->current();
			return $this->assertCallCount($this->result, 'fetchArray', 1);
		}

	    public function testNextReturnsItself() {
			return $this->assertIdentical($this->iterator->next(), $this->iterator);
	    }

	    public function testRewindReturnsItself() {
			return $this->assertIdentical($this->iterator->rewind(), $this->iterator);
	    }

	}


?>