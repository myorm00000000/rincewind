<?php

/**
 * This file contains the SQL result iterator definition.
 *
 * @author Matthias Loitsch <developer@ma.tthias.com>
 * @copyright Copyright (c) 2010, Matthias Loitsch
 * @package Dao
 **/

/**
 * Loading the abstract Dao Class
 */
include dirname(__FILE__) . '/DaoResultIterator.php';

/**
 * The SQL result iterator is the implementation of the DaoResultIterator.
 * It allows managing the result of a sql query.
 *
 * @author Matthias Loitsch <developer@ma.tthias.com>
 * @copyright Copyright (c) 2010, Matthias Loitsch
 * @package Dao
 **/
class SqlResultIterator extends DaoResultIterator {

	/**
	 * @var result
	 */
	private $result = false;

	/**
	 * @var Dao
	 */
	private $dao = false;


	/**
	 * Contains the data of the current set.
	 *
	 * @var array
	 */
	private $currentData;


	/**
	 * The number of rows
	 *
	 * @var int
	 */
	private $length = 0;


	/**
	 * @param result $result
	 * @param Dao $dao
	 */
	public function __construct($result, $dao) {
		$this->result = $result;
		$this->length = $result->numRows();
		$this->dao = $dao;
		$this->next();
	}

	/**
	 * Sets the pointer to row 1.
	 * @return SqlResultIterator Returns itself for chaining.
	 */
	public function rewind() {
		if ($this->length > 0) {
			$this->result->reset();
			$this->currentKey = 0;
			$this->next();
		}
		return $this;
	}


	/**
	 * Return the current DataObject.
	 * If getAsArray() has been called, returns an array instead of the DataObject.
	 *
	 * @return DataObject|array
	 */
	public function current() {
		$dataObject = $this->dao->getObjectFromDatabaseData($this->currentData);
		return $this->returnDataObjectsAsArray ? $dataObject->getArray() : $dataObject;
	}

	/**
	 * Set the pointer to the next row, and fetches the data to return in current.
	 * @return SqlResultIterator Returns itself for chaining.
	 */
	public function next() {
		$this->currentKey ++;
		$this->currentData = $this->result->fetchArray();
		return $this;
	}

	/**
	 * Check if the pointer is still valid.
	 *
	 * @return bool
	 */
	public function valid() {
		return ($this->currentData != false);
	}

	/**
	 * Return the number of rows.
	 *
	 * @return int
	 */
	public function count() {
		return $this->length;
	}

}


?>