<?php

/**
 * This file contains the Mysql Dao definition.
 *
 * @author Matthias Loitsch <developer@ma.tthias.com>
 * @copyright Copyright (c) 2010, Matthias Loitsch
 * @package Dao
 **/



/**
 * Loading the SqlDao
 */
include dirname(dirname(__FILE__)) . '/SqlDao.php';


/**
 * Loading the MysqlResultIterator
 */
include dirname(__FILE__) . '/MysqlResultIterator.php';


/**
 * The MySqlDao implementation of a SqlDao
 *
 * @author Matthias Loitsch <developer@ma.tthias.com>
 * @copyright Copyright (c) 2010, Matthias Loitsch
 * @package Dao
 **/
class MysqlDao extends SqlDao {

  /**
   * Calls the Dao::exportAttributeName and adds quotes.
   * Takes a php attribute name, converts it via import/export attribute mapping, renames it, escapes it, and adds quotes.
   * This is the correct way to insert attribute names in a SQL query.
   *
   * @param string $attributeName
   * @return string
   */
  public function exportAttributeName($attributeName) {
    return '`' . parent::exportAttributeName($attributeName) . '`';
  }

  /**
   * Escapes and quotes a table name.
   *
   * @param string $resourceName
   * @return string The escaped and quoted table name.
   */
  public function exportResourceName($resourceName = null) {
    return '`' . parent::exportResourceName($resourceName) . '`';
  }

  /**
   * Escapes and quotes a string.
   *
   * @param string $text
   * @return string The escaped and quoted string.
   */
  public function exportString($text) {
    return "'" . $this->escapeString($text) . "'";
  }
  
  /**
   * Returns the id of the last inserted record.
   *
   * @return int
   */
  protected function getLastInsertId() {
    $id = $this->db->query("select LAST_INSERT_ID() as id");
    $id = $id->fetchArray();
    return $id['id'];
  }

  /**
   * Creates an iterator for a mysql result.
   *
   * @param mysqli_result $result
   * @return MysqlResultIterator
   */
  protected function createIterator($result) {
    return new MysqlResultIterator($result, $this);
  }


}

