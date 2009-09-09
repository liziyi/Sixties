<?php
/**
 * This file is part of Bb36, a set of PHP classes by Clochix.net
 *
 * This file contains Database management classes
 *
 * Copyright (C) 2009  Clochix.net
 *
 * Bb36 is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Bb36 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Bb36; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * PHP Version 5
 *
 * @category   Library
 * @package    Bb36
 * @subpackage Hub
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @link       https://labo.clochix.net/projects/show/sixties
 */

/**
 * Include common classes
 */
require_once 'BbCommon.php';

/**
 * BbData : base class for database access
 *
 * @category   Library
 * @package    Bb36
 * @subpackage Common
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
Class BbData extends BbBase
{
    const SELECT = 'select';
    const INSERT = 'insert';
    const UPDATE = 'update';
    const DELETE = 'delete';

}
/**
 * BbDatabase : Database factory
 *
 * @category   Library
 * @package    Bb36
 * @subpackage Common
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class BbDatabase
{
    /**
     * @var BbLogger the logger instance
     */
    protected static $instance;

    /**
     * Constructor
     *
     * Constructor is protected, we use a factory
     *
     * @return void
     */
    protected function __construct()
    {
    }
    /**
     * Factory : get a database connection
     *
     * @param string $dsn      the database DSN
     * @param string $user     database user
     * @param string $password database password
     *
     * @return BbLogger
     */
    public static function get($dsn = null, $user = null, $password = null)
    {
        if (empty(self::$instance)) {
            try {
                self::$instance = new PDO($dsn, $user, $password);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (Exception $e) {
                throw new Exception("Internal error when connecting to repository : " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
/**
 * BbDataTable : base class for all Data tables
 *
 * @category   Library
 * @package    Bb36
 * @subpackage Common
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class BbDataTable extends BbData
{
    /**
     * @var string name of the table
     */
    public static $tablename = '';
    /**
     * @var string name of the {@link DataRow} class
     */
    public static $class     = '';
    /**
     * @var array columns of the tables
     */
    public static $columns   = array();
    /**
     * @var array relations to other tables
     */
    public static $relations = array();
    /**
     * @var string primary key column
     */
    public static $key       = '';
    /**
     * Get a {@link DataRow} by it's key value
     *
     * @param string $class row class
     * @param string $val   key value
     *
     * @return BbDataRow
     */
    public static function findByKey($class, $val)
    {
        $self = new ReflectionClass($class);
        return self::find($class, array(new BbCriteria($self->getProperty('key')->getValue(), '=', $val)));
    }
    /**
     * Get {@link DataRow}s by criterias
     *
     * @param string $class     row class
     * @param array  $criterias array of {@link BbCriteria}
     *
     * @return array
     */
    public static function find($class, $criterias = array())
    {
        return self::executeRequest($class, BbData::SELECT, $criterias);
    }
    /**
     * Delete {@link DataRow}(s)
     *
     * @param string $class row class name
     * @param mixed  $val   key value or array of {@link BbCriteria}
     *
     * @return boolean
     */
    public static function delete($class, $val)
    {
        $self = new ReflectionClass($class);
        if (is_array($val)) {
            return self::executeRequest($class, BbData::DELETE, $val);
        } else {
            return self::executeRequest($class, BbData::DELETE, array(new BbCriteria($self->getProperty('key')->getValue(), '=', $val)));
        }
    }
    /**
     * Execute a request
     *
     * @param string $class     row class name
     * @param string $type      one of BbData constants
     * @param array  $criterias array of {@link BbCriterias}
     *
     * @return mixed array on select, boolean on delete
     */
    protected static function executeRequest($class, $type, $criterias = array())
    {
        $self    = new ReflectionClass($class);
        $dbh     = BbDatabase::get();
        $logger  = BbLogger::get();
        $table   = $self->getProperty('tablename')->getValue();
        $columns = $self->getProperty('columns')->getValue();

        try {
            $request = '';
            switch ($type) {
            case BbData::SELECT:
                $request = sprintf('SELECT * FROM %s ', $table);
                break;
            case BbData::DELETE:
                $request = sprintf('DELETE FROM %s ', $table);
                break;
            default:
                $logger->log("Unknown request type $type", BbLogger::ERROR, 'BbCrudTable');
                throw new Exception("Internal error");
            }

            // Add criterias
            $request .= BbCriteria::toRequest($criterias);

            $stmt = $dbh->prepare($request);

            // Bind criterias
            $crits = array();
            foreach ($criterias as $criteria) {
                $crits[] = $criteria->getVal();
                $stmt->bindValue(':w' . $criteria->getCol(), (string)$criteria->getVal(), PDO::PARAM_STR);
            }

            $logger->log($request . ' ( ' . implode(', ', $crits) . ' ) ', BbLogger::DEBUG, 'BbCrudTable');

            // Execute request
            switch ($type) {
            case BbData::SELECT:
                $res = array();
                if ($stmt->execute()) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $res[] = self::load($class, $row);
                    }
                }
                return $res;
                break;
            case BbData::DELETE:
                return $stmt->execute();
                break;
            }
        } catch (Exception $e) {
            $logger->log("Error : " . $e->getMessage(), BbLogger::ERROR, 'BbCrudTable');
            throw new Exception("Internal error");
        }
        return true;
    }
    /**
     * Load a BbDataRow from an array
     *
     * @param string $class  row class name
     * @param array  $params array of parameters
     *
     * @return BbDataRow
     */
    public function load($class, $params = array())
    {
        $self        = new ReflectionClass($class);
        $dbh         = BbDatabase::get();
        $logger      = BbLogger::get();
        $objectClass = $self->getProperty('class')->getValue();

        $object  = new $objectClass($params);
        $id      = $object->getKeyValue();
        // Load related objects
        $relations = $self->getProperty('relations')->getValue();
        foreach ($relations as $via => $related) {
            $viaClass = new ReflectionClass($via);
            $relClass = new ReflectionClass($related['related']);
            $request  = sprintf(
                "select a.* from %s a, %s b where a.%s = b.%sId and b.%sId = :id",
                $relClass->getProperty('tablename')->getValue(),
                $viaClass->getProperty('tablename')->getValue(),
                $relClass->getProperty('key')->getValue(),
                $related['callback'],
                $objectClass
            );
            $stmt = $dbh->prepare($request);
            $id   = (string)$object->getKeyValue();
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            $logger->log($request . $id, BbLogger::DEBUG, 'BbCrudTable');
            if ($stmt->execute()) {
                $nb = 0;
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $callback = 'add' . $related['callback'];
                    $object->$callback($row);
                    $nb++;
                }
                $logger->log("Rows affected : $nb", BbLogger::DEBUG, 'BbCrudTable');
            }
        }
        return $object;
    }

}
/**
 * BbDataRow : base class for all Data rows
 *
 * @category   Library
 * @package    Bb36
 * @subpackage Common
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class BbDataRow extends BbData
{
    const SELECT = 'select';
    const INSERT = 'insert';
    const UPDATE = 'update';
    const DELETE = 'delete';

    /**
     * @var PDO database handler
     */
    protected $dbh;
    /**
     * @var ReflectionClass link to the {@link DataTable}
     */
    protected $table;
    /**
     * @var array hashtable of columns name and values
     */
    protected $columns;
    /**
     * @var array hastable of relations to other tables
     */
    protected $related = array();
    /**
     * @var array
     */
    private $_classes = array();

    private static $_depth = 0;

    /**
     * Constructor
     *
     * @param array $params hashtable of parameters
     */
    public function __construct($params)
    {
        parent::__construct();

        // Get the current database instance
        $this->dbh = BbDatabase::get(null);
        $this->table = new ReflectionClass(get_class($this) . 'Table');
        $columns = $this->table->getProperty('columns')->getValue();
        $related = $this->table->getProperty('relations')->getValue();
        foreach ($columns as $col) {
            $this->columns[$col] = $params[$col];
        }
        foreach ($related as $rel) {
            $name = strtolower($rel['callback']) . 's';
            $this->related[$name] = (is_array($params[$name]) ? $params[$name] : array());
            $this->_classes[$rel['callback']] = array();
        }
    }

    /**
     * Manage dynamic methods for relations
     *
     * addXxx : add a relation to an object of class Xxx
     * getXxx : get all relations to objects of class Xxx
     *
     * @param string $name name of the method
     * @param array  $args arguments
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        $prefix = substr($name, 0, 3);
        $class  = substr($name, 3);
        if (!isset($this->_classes[$class])) {
            throw new Exception("Method $name not defined");
        }
        switch ($prefix) {
        case 'add':
            // add a relation to the row
            if (is_object($args[0])) {
                $this->related[strtolower($class) . 's'][] = $args[0];
            } else if (is_array($args[0])) {
                $this->related[strtolower($class) . 's'][] = new $class($args[0]);
            } else {
                throw new Exception("Bad arguments for method $name");
            }
            break;
        case 'get':
            // get all relations
            return $this->related[strtolower($class) . 's'];
            break;
        }
    }
    /**
     * Get all columns and the values
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }
    /**
     * Get a column value
     *
     * @param string $col columns name
     *
     * @return mixed
     */
    public function get($col)
    {
        return $this->columns[$col];
    }
    /**
     * Set a column value
     *
     * @param string $col columns name
     * @param string $val columns value
     *
     * @return BbDataRow this
     */
    public function set($col, $val)
    {
        $this->columns[$col] = $val;
        return $this;
    }
    /**
     * Get ne name of the key of the row
     *
     * @return string
     */
    public function getKey()
    {
        return $this->table->getProperty('key')->getValue();
    }
    /**
     * Get table key value
     *
     * @return string
     */
    public function getKeyValue()
    {
        return $this->columns[$this->table->getProperty('key')->getValue()];
    }
    /**
     * Save the row
     *
     * @return BbDataRow this
     */
    public function save()
    {
        $class = get_class($this);
        // If no ID, it's a creation, else we must request to see if the row exists
        $id = $this->getKeyValue();
        if ($id === null) {
            $mode = BbDataRow::INSERT;
        } else {
            $res = BbDataTable::findByKey($class . 'Table', $id);
            $mode = (count($res) == 0 ? BbDataRow::INSERT : BbDataRow::UPDATE);
        }

        try {
            if (self::$_depth == 0) {
                $this->dbh->beginTransaction();
            }
            self::$_depth++;

            // Save row
            $this->executeRequest($mode);

            // related rows
            // @TODO : find a way to prevent looping
            $related = $this->table->getProperty('relations')->getValue();
            // If it's an update, remove links first
            if ($mode == BbDataRow::UPDATE) {
                foreach ($related as $link => $rel) {
                    BbDataTable::delete($link, array(new BbCriteria($class . 'Id', '=', $id)));
                }
            }
            // then create new links
            foreach ($related as $link => $rel) {
                $this->manyToMany($id, substr($link, 0, -5), $this->related[strtolower($rel['callback']) . 's']);
            }

            if (self::$_depth == 1) {
                $this->dbh->commit();
            }
        } catch (Exception $e) {
            $this->log("Error saving row entry : " . $e->getMessage(), BbLogger::ERROR, 'BbDataRow');
            if (self::$_depth == 1) {
                $this->dbh->rollBack();
            }
            throw new Exception("Error saving row entry : " . $e->getMessage());
        }
        self::$_depth--;
        return $this;
    }
    /**
     * Execute a request
     *
     * @param string $type one of the class constants
     *
     * @return BbDataRow|array
     */
    protected function executeRequest($type)
    {
        try {
            $request = '';
            $columns = array_keys($this->columns);
            $table   = $this->table->getProperty('tablename')->getValue();
            $key     = $this->table->getProperty('key')->getValue();
            switch ($type) {
            case BbData::INSERT:
                $request = sprintf('INSERT INTO %s (%s) values (:%s)', $table, implode(',', $columns), implode(',:', $columns));
                break;
            case BbData::UPDATE:
                $set = array();
                foreach ($this->columns as $col => $value) {
                    if ($col != $key) {
                        $set[] = " $col = :$col ";
                    }
                }
                $request = sprintf('UPDATE %s SET %s WHERE %s = :%s', $table, implode(', ', $set), $key, $key);
                break;
            case BbData::DELETE:
                $request = sprintf('DELETE FROM %s WHERE %s = :%s', $table, $key, $key);
                break;
            default:
                $this->log("Unknown request type $type", BbLogger::ERROR, 'BbDataRow');
                throw new Exception("Internal error");
            }

            $this->log($request, BbLogger::DEBUG, 'BbDataRow');

            $stmt = $this->dbh->prepare($request);

            // Bind values
            if ($type == self::INSERT || $type == self::UPDATE) {
                foreach ($this->columns as $col => $value) {
                    if ($col == $key && $value == null) {
                        $stmt->bindValue(':' . $col, null, PDO::PARAM_NULL);
                    } else {
                        $stmt->bindValue(':' . $col, (string)$value, PDO::PARAM_STR);
                    }
                }
            }
            // Bind criterias
            if ($type == self::DELETE || $type == self::UPDATE) {
                $stmt->bindValue(':' . $key, (string)$this->getKeyValue(), PDO::PARAM_STR);
            }

            // Execute request
            $stmt->execute();
            return $this;
        } catch (Exception $e) {
            $this->log("Error : " . $e->getMessage(), BbLogger::ERROR, 'BbDataRow');
            throw new Exception("Internal error");
        }
        return $this;
    }
    /**
     * Create one to many links
     *
     * @param mixed  $sourceId id of the source object
     * @param string $link     name of the link table
     * @param array  $objects  array of objects
     *
     * @return AtomEntry $this
     */
    protected function manyToMany($sourceId, $link, $objects)
    {
        foreach ($objects as $obj) {
            $criterias = array();
            $colums    = $obj->getColumns();
            foreach ($colums as $key => $colum) {
                if ($key != $obj->getKey()) {
                    $criterias[] = new BbCriteria($key, '=', $colum);
                }
            }
            $class = get_class($obj);
            $res   = call_user_func($class . 'Table::find', $class . 'Table', $criterias);
            if (count($res) != 1) {
                $obj->save();
                $objId = $this->dbh->lastInsertId();
                $obj->set($obj->getKey(), $objId);
            } else {
                $tmp   = $res[0];
                $objId = $tmp->getKeyValue();
            }
            $link = new $link(array(get_class($this) . 'Id' => $sourceId, $class . 'Id' => $objId));
            $link->save();
        }

        return $this;
    }
}

/**
 * BbCrudColumn : description of table column
 *
 * @category   Library
 * @package    Bb36
 * @subpackage Common
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class BbCrudColumn
{
    /**
     * @var mixed value
     */
    protected $value;

    /**
     * Constructor
     *
     * @param mixed $value value of the comumn
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
    /**
     * Get value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Set value
     *
     * @param mixed $value value
     *
     * @return BbCrudColumn this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
    /**
     * Return a string representation of the value
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }
}

/**
 * BbCriteria : query criteria
 *
 * @category   Library
 * @package    Bb36
 * @subpackage Common
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class BbCriteria
{
    protected $col;
    protected $op;
    protected $val;

    /**
     * Constructor
     *
     * @param string $col column name
     * @param string $op  operator
     * @param mixed  $val criteria value
     */
    public function __construct($col, $op, $val)
    {
        $this->col = $col;
        $this->op  = $op;
        $this->val = $val;
    }
    /**
     * Return the column
     *
     * @return string
     */
    public function getCol()
    {
        return $this->col;
    }
    /**
     * Return the value
     *
     * @return mixed
     */
    public function getVal()
    {
        return $this->val;
    }
    /**
     * Return a string representation of the criteria
     *
     * @return string
     */
    public function __toString()
    {
        return "{$this->col} {$this->op} :w{$this->col} ";
    }
    /**
     * Convert an array of criterias to SQL
     *
     * @param array $criterias array of {@link BbCriteria}
     *
     * @return string
     */
    public static function toRequest($criterias)
    {
        $request = '';
        $crits   = array();
        foreach ($criterias as $criteria) {
            $crits[] = (string)$criteria;
        }
        if (count($crits) > 0) {
            $request .= ' WHERE ' . implode(' AND ', $crits);
        }
        return $request;
    }
}