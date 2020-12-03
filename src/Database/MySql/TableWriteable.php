<?php

namespace golibdatabase\Database\MySql;

use Exception;
use golib\Types\PropsFactory;
use golib\Types\Types;
use golibdatabase\Database\MySql as Database;
use InvalidArgumentException;

/**
 * Description of TableWriteable
 *
 * @author tziegler
 */
abstract class TableWriteable extends Table
{

    /**
     * Properties that have to ignored
     * by building updates
     * @var array
     */
    private $ignoredProps = array(
        '__origin',
        '__uuid',
    );

    /**
     * contains firldnames that should not used
     * for wherestatementesn inupdates
     * @var array
     */
    private $ignoreOnUpdateWhere = array();

    /**
     *
     * @var InsertStatement
     */
    private $inserthandler = NULL;

    /**
     * sets the on-duplicate-key updates for the inserthandler
     * @var boolean
     */
    private $onDuplicateKeyOnInsert = false;

    /**
     *
     * @var UpdateStatement
     */
    private $update = NULL;

    /**
     * stores all loaded items
     * to determiate alll changes later
     * @var array[PropsFactory]
     */
    private $clones = array();

    /**
     * if true INSERT ON DUPLICATE KEY UPDATE will be used
     * instead of update. so there is no longer a check
     * depending on previus values
     * @var boolean
     */
    private $updateByInsert = false;

    /**
     * contains all fired statementens
     * that was sucessfully executed
     * @var array
     */
    private $statements = array();

    /**
     * contains all fired statementens
     * that was executed but dos not affect any rows
     * @var array
     */
    private $nonAffectingStatements = array();

    /**
     * flag for new registered items
     * @var boolean
     */
    private $newItems = false;
    private $newItemsStorage = array();

    /**
     * contains all fieldnames that
     * are unsigned. this means any update
     * of values must check if the amount always
     * equalor higher then zero
     * @var array
     */
    private $unsignedFields = array();

    /**
     * handles new items
     * @param PropsFactory $item
     * @return PropsFactory
     */
    protected function newItem(PropsFactory $item, $loadedFromDb = true)
    {
        if ($loadedFromDb) {
            $uid = uniqid();
            $item->__uuid = $uid;
            $clone = clone $item;
            $clone->__origin = &$item;
            $this->clones[$uid] = $clone;
        } else {
            $this->newItems = true;
            $this->getInsert($item);
            $this->newItemsStorage[] = &$item;
        }
        return $item;
    }

    /**
     * THIS SHOULD BE A EXCEPTION FOR SPECIAL CASES
     * enables or disables the onDuplicateKey handling
     * for the next inserts.
     *
     * it is in any case better working without these
     * so use this with care
     *
     * @param bool $onOffBool
     */
    public function setDuplicateKeyHandling($onOffBool)
    {
        $this->onDuplicateKeyOnInsert = (bool)$onOffBool;
    }

    /**
     * adds an fieldname that have to be
     * excluded on update statements as where statetment.
     * this means NOT the field himself willlnot be updated. this affects
     * the update check only.so the item value must no longer match to
     * the stored value
     * @param string $fieldname
     */
    public function setIgnoredFieldOnUpdate($fieldname)
    {
        $this->ignoreOnUpdateWhere[] = $fieldname;
    }

    /**
     * add fieldname that will be ignored by database
     * updates
     * @param string $field
     */
    public function registerIgnoredField($field)
    {
        $this->ignoredProps[] = $field;
    }

    /**
     * get the updated object by the id
     * that come from updatehandler
     * @param string $uid
     * @return PropsFactory
     */
    private function getUpdatedPropById($uid)
    {
        $props = $this->getUpater()->getLastUpdated();

        if ($uid == NULL || empty($props)) {
            return NULL;
        }

        if (isset($props[$uid])) {

            return $props[$uid];
        }
        return NULL;
    }

    /**
     * add a fieldname that can not conatins numbers
     * lower the zero.if so, the update or insert will
     * be aborted.
     * @param string $field
     */
    public function registerUnsignedField($field)
    {
        $this->unsignedFields[] = $field;
    }

    /**
     * start diff calculation
     * and validation on diffs
     */
    private function calcutateDiff()
    {
        $this->getUpater()->clearDiff();
        $sucess = true;
        foreach ($this->clones as $clone) {
            #$sucess = $sucess && $this->validateOrigin( $clone );
            if ($this->getDiff($clone)) {
                $sucess = $sucess && $this->validateOrigin($clone);
            }
        }
        return $sucess;
    }

    /**
     * save changed properties back to database
     * @param Database $db
     */
    public function save(Database $db)
    {
        $valide = $this->calcutateDiff();
        if ($valide == false) {
            return false;
        }
        $sql = NULL;
        $success = true;
        if ($this->updateByInsert && $this->inserthandler != NULL) {
            $sql = $this->inserthandler->createInsertStatement();
            $success = $success && $this->execStatement($db, $sql);
        } elseif (!$this->updateByInsert) {
            $sqls = $this->getUpater()->getUpdates();
            foreach ($sqls as $uuid => $statement) { // the uuid is important to get the updated object
                // execstatements get also the object by uui to updates his context
                $success = $success && $this->execStatement($db, $statement,
                        $uuid);
            }
        }

        // get out if some statements failed
        if (!$success) {
            return false;
        }

        if ($this->newItems && $this->inserthandler != NULL) {
            foreach ($this->newItemsStorage as $newItem) {
                $success = $success && $this->applyToInsert($newItem);
            }
            $success = $success && $this->execStatement($db,
                    $this->inserthandler->createInsertStatement());
            if ($success) {
                $this->updateInsertsToKnown();
            }
        }
        return $success;
    }

    private function updateInsertsToKnown()
    {
        foreach ($this->newItemsStorage as $newItem) {
            $this->newItem($newItem, TRUE);
        }
        $this->newItems = false;
        $this->newItemsStorage = array();
        $this->inserthandler = NULL; // remove the inserthandler so allcached insert are gone
    }

    /**
     * insert a new Item
     * @param PropsFactory $newItm
     * @throws InvalidArgumentException
     */
    public function insert(PropsFactory $newItm)
    {
        $check = get_class($this->getPropFactory());
        $new = get_class($newItm);
        if ($check != $new) {
            throw new InvalidArgumentException("Expected type [{$check}] not matching to [{$new}]");
        }
        $this->registerItem($newItm);
    }

    /**
     * execute statement
     * @param Database $db
     * @param string $sql
     * @param string|null $uuid
     * @return boolean
     * @throws Exception
     */
    private function execStatement(Database $db, string $sql, null|string $uuid = NULL)
    {
        $result = $db->select($sql);
        if ($result->getError() === NULL && $db->getlastAffectedRows() != 0) {
            $this->statements[] = $sql;
            $this->updateCloneByUid($uuid);
            return true;
        }
        $this->nonAffectingStatements[] = $sql;
        return false;
    }

    /**
     * updates PropsFactory object clone so
     * on next diff calculation there should no diff found
     * @param string $uuid
     */
    private function updateCloneByUid(string $uuid)
    {
        $clone = $this->getUpdatedPropById($uuid);

        if ($uuid == NULL || $clone == NULL) {
            return;
        }

        foreach ($clone as $clonedProp => $clonedValue) {
            if (!in_array($clonedProp, $this->ignoredProps)) {
                if (is_object($clone->$clonedProp)) {
                    $clone->$clonedProp = clone $clone->__origin->$clonedProp; // TODO: no refrence on objects
                } else {
                    $clone->$clonedProp = $clone->__origin->$clonedProp; // TODO: no refrence on objects
                }
            }
        }
    }

    /**
     *
     */
    public function truncateAffected(Database $db)
    {
        $sql = "DELETE FROM " . $this->getTableName() . " WHERE " . $this->getWhere()->getWhereCondition();

        $res = $db->query($sql);
        if ($res) {
            $this->clones = array(); // clear clones
        }
        return $res;
    }

    /**
     * all statements that was used to update
     * database
     * @return array
     */
    public function getStatements()
    {
        return $this->statements;
    }

    /**
     * all statements that was used to update
     * database
     * @return array
     */
    public function getNonAffectingStatements()
    {
        return $this->nonAffectingStatements;
    }

    /**
     * get the update builder
     * @return UpdateStatement
     */
    private function getUpater()
    {
        if ($this->update == NULL) {
            $this->update = new UpdateStatement($this);
            $this->update->setIgnoreList($this->ignoredProps);
            $this->update->setFieldsIgnoredOnWhere($this->ignoreOnUpdateWhere);
        }
        return $this->update;
    }

    /**
     * get the insertStatement handler
     * @return InsertStatement
     */
    private function getInsert(PropsFactory $template)
    {
        if ($this->inserthandler == NULL) {
            $this->inserthandler = new InsertStatement($this->getTableName(),
                $this->onDuplicateKeyOnInsert);
            $this->inserthandler->addFieldsByProps($template);
        }
        return $this->inserthandler;
    }

    /**
     *
     * @param PropsFactory $clone
     */
    private function getDiff(PropsFactory $clone)
    {
        $compare = $clone->__origin;
        $diffFound = false;
        foreach ($clone as $propName => $propValue) {
            if (!in_array($propName, $this->ignoredProps) && !$this->matching($compare->$propName,
                    $propValue,
                    $propName)) {
                $diffFound = true;
            }
        }
        if ($diffFound && $this->updateByInsert) {
            $this->onInsert($compare);
            $this->applyToInsert($compare);
        } elseif ($diffFound && !$this->updateByInsert) {
            $this->onUpdate($compare);

            $this->getUpater()->updateDiff($clone, $compare,
                $this->ignoredProps);
        }
        return $diffFound;
    }

    /**
     * validtae against unsigned for the
     * origin props, by checking
     * the cloned props
     * @param PropsFactory $clone
     * @return boolean
     */
    private function validateOrigin(PropsFactory $clone)
    {
        $compare = $clone->__origin;
        foreach ($clone as $propName => $propValue) {
            if (!$this->validUnsigned($compare, $propName, $propValue)) {
                return false;
            }
        }
        return true;
    }

    /**
     * checks if the prop not try to write negative amounts
     * on fields that defined as unsigned.
     * the prop-value will also set to zero
     * @param PropsFactory $prop
     * @return boolean
     */
    private function validateUnsigned(PropsFactory $prop)
    {
        foreach ($prop as $propName => $propValue) {
            if (!$this->validUnsigned($prop, $propName, 0)) {
                return false;
            }
        }
        return true;
    }

    /**
     * checks if the fieldName is unsigned
     * and if the value also not negative
     * @param PropsFactory $compare
     * @param string $fieldName
     * @param int $resetValue
     * @return boolean
     */
    private function validUnsigned(PropsFactory $compare, string $fieldName,
                                   int $resetValue)
    {
        if (!in_array($fieldName, $this->ignoredProps) && in_array($fieldName,
                $this->unsignedFields)) {
            if ($resetValue >= 0 && $compare->$fieldName < 0) {
                $compare->$fieldName = $resetValue;
                return false;
            }
        }
        return true;
    }

    /**
     * checks if the two values matching
     * @param Types $valueLeft
     * @param Types $valueRight
     * @return bool
     */
    private function matching($valueLeft, $valueRight, $propName)
    {
        $checkL = $valueLeft;
        $checkR = $valueRight;
        if ($valueLeft instanceof Types) {
            $checkL = $valueLeft->getValue();
        }
        if ($valueRight instanceof Types) {
            $checkR = $valueRight->getValue();
        }

        return ($checkL == $checkR);
    }

    /**
     * register propertie for inserting.
     * returns false if a check against
     * the unsigned fields is not positive the
     * make sure no negative amounts was inserted
     * @param PropsFactory $new
     */
    private function applyToInsert(PropsFactory $new)
    {
        $valid = $this->validateUnsigned($new);
        if ($valid === false) {
            return false;
        }
        foreach ($new as $propName => $propValue) {
            if (!in_array($propName, $this->ignoredProps)) {
                $this->getInsert($new)->addValues($propName, $propValue);
            }
        }
        $this->getInsert($new)->rowDone();
        return true;
    }

    /**
     * overwrite for own handling on items
     * that willbe updated
     */
    protected function onUpdate(PropsFactory &$item)
    {

    }

    /**
     * overwrite for own handling on items
     * that will be inserted
     */
    protected function onInsert(PropsFactory &$item)
    {

    }

}
