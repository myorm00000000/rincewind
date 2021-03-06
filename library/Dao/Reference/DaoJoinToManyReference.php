<?php

/**
 * This file contains the DaoJoinManyReference definition that's used to describe
 * references to many other records in a datasource by joining.
 *
 * @author Matthias Loitsch <developer@ma.tthias.com>
 * @copyright Copyright (c) 2010, Matthias Loitsch
 * @package Dao
 */
/**
 * Including the basic dao reference.
 */
require_class('BasicDaoToManyReference', dirname(__FILE__) . '/BasicDaoToManyReference.php');

/**
 * A DaoToManyReference describes references to many resources by joining.
 *
 * Eg.: You have a table called "events", and a table called "attendees", where
 * the table "attendees" has a column "event_id".
 * In this case, you would add the reference attribute "attendees" to the table
 * events, and set it up as a DaoJoinManyReference.
 *
 * @author Matthias Loitsch <developer@ma.tthias.com>
 * @copyright Copyright (c) 2010, Matthias Loitsch
 * @package Dao
 * @see DaoReference
 * @see DaoToOneReference
 */
class DaoJoinToManyReference extends BasicDaoToManyReference {

  /**
   * Be careful, the order for foreign and local key are inversed.
   *
   * DaoJoinManyReferences can't be exported
   *
   * @param string|Dao $foreignDaoName
   * @param string $foreignKey
   * @param string $localKey
   */
  public function __construct($foreignDaoName, $foreignKey, $localKey = 'id', $filterMap = null) {
    parent::__construct($foreignDaoName, $localKey, $foreignKey, false, false, $filterMap);
  }

  /**
   * Returns a DaoIterator for a specific reference.
   * A DataSource can directly return the DataHash, so it doesn't have to be fetched.
   *
   * @param Record $record
   * @param string $attributeName The attribute it's being accessed on
   * @return DaoIterator
   */
  public function getReferenced($record, $attributeName) {
    if ($data = $record->getDirectly($attributeName)) {
      if (is_array($data)) {
        // If the data hash exists already, just return the Iterator with it.
        return $this->cacheAndReturn($record, $attributeName, $this->getForeignDao()->createIterator($data));
      }
      elseif ($data instanceof Iterator) {
        // The iterator is cached. Just return it.
        return $data;
      }
      else {
        Log::warning(sprintf('The data hash for `%s` was set but incorrect.', $attributeName));
        return null;
      }
    }
    else {
      return $this->cacheAndReturn($record, $attributeName, $this->getForeignDao()->getIterator($this->applyFilter(array($this->getForeignKey() => $record->get($this->getLocalKey())))));
    }
  }

  /**
   * @param mixed $value
   */
  public function exportValue($value) {
    throw new DaoReferenceException('JoinManyReferences should never be exported.');
  }

  /**
   * @param mixed $value
   * @return mixed the coerced value.
   * @throws DaoCoerceException
   */
  public function coerce($value) {
    try {
      if ( ! is_array($value)) throw new Exception();
      $newValue = array();
      foreach ($value as $id) {
        $newValue[] = $this->getForeignDao()->coerceId($id);
      }
      return $newValue;
    }
    catch (Exception $e) {
      throw new DaoCoerceException(array(), "Invalid JoinToManyReference provided.");
    }
  }

}

