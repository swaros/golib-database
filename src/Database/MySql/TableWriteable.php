<?php

namespace golibdatabase\Database\MySql;

use Exception;
use golib\Types\PropsFactory;
use golib\Types\Types;
use golibdatabase\Database\Model\LoggingEntryPoint;
use golibdatabase\Database\MySql as Database;
use InvalidArgumentException;
use mysqli_result;

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
    private array $ignoredProps = array(
        '__origin',
        '__uuid',
    );

    /**
     * contains field-names that should not used
     * for where-statements in updates
     * @var array
     */
    private array $ignoreOnUpdateWhere = array();

    /**
     *
     * @var InsertStatement|null
     */
    private ?InsertStatement $insertHandler = NULL;

    /**
     * sets the on-duplicate-key updates for the insertHandler
     * @var boolean
     */
    private bool $onDuplicateKeyOnInsert = false;

    /**
     *
     * @var UpdateStatement|null
     */
    private ?UpdateStatement $update = NULL;

    /**
     * stores all loaded items
     * to determinate all changes later
     * @var PropsFactory[]
     */
    private array $clones = array();

    /**
     * if true INSERT ON DUPLICATE KEY UPDATE will be used
     * instead of update. so there is no longer a check
     * depending on previous values
     * @var boolean
     */
    private bool $updateByInsert = false;

    /**
     * contains all fired statements
     * that was successfully executed
     * @var array
     */
    private array $statements = array();

    /**
     * contains all fired statements
     * that was executed but dos not affect any rows
     * @var array
     */
    private array $nonAffectingStatements = array();

    /**
     * flag for new registered items
     * @var boolean
     */
    private bool $newItems = false;
    private array $newItemsStorage = array();

    /**
     * contains all fieldNames that
     * are unsigned. this means any update
     * of values must check if the amount always
     * equal higher then zero
     * @var array
     */
    private array $unsignedFields = array();

    /**
     * handles new items
     * @param PropsFactory $item
     * @param bool $loadedFromDb
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
    public function setDuplicateKeyHandling(bool $onOffBool)
    {
        $this->onDuplicateKeyOnInsert = (bool)$onOffBool;
    }

    /**
     * adds an fieldName that have to be
     * excluded on update statements as where statement.
     * this means NOT the field himself will not be updated. this affects
     * the update check only.so the item value must no longer match to
     * the stored value
     * @param string $fieldName
     */
    public function setIgnoredFieldOnUpdate(string $fieldName)
    {
        $this->ignoreOnUpdateWhere[] = $fieldName;
    }

    /**
     * add fieldName that will be ignored by database
     * updates
     * @param string $field
     */
    public function registerIgnoredField(string $field)
    {
        $this->ignoredProps[] = $field;
    }

    /**
     * get the updated object by the id
     * that comes from updateHandler
     * @param string|null $uid
     * @return Diff\Row|PropsFactory|null
     */
    private function getUpdatedPropById(string|null $uid): Diff\Row|PropsFactory|null
    {
        $props = $this->getUpdater()->getLastUpdated();

        if ($uid == NULL || empty($props)) {
            return NULL;
        }

        if (isset($props[$uid])) {

            return $props[$uid];
        }
        return NULL;
    }

    /**
     * add a fieldName that can not contains numbers
     * lower the zero.if so, the update or insert will
     * be aborted.
     * @param string $field
     */
    public function registerUnsignedField(string $field)
    {
        $this->unsignedFields[] = $field;
    }

    /**
     * start diff calculation
     * and validation on diffs
     * @return bool
     */
    private function calculateDiff(): bool
    {
        $this->getUpdater()->clearDiff();
        $success = true;
        foreach ($this->clones as $clone) {
            if ($this->getDiff($clone)) {
                $success = $success && $this->validateOrigin($clone);
            }
        }
        return $success;
    }

    /**
     * save changed properties back to database
     * @param Database $db
     * @return bool
     * @throws Exception
     */
    public function save(Database $db): bool
    {
        $valid = $this->calculateDiff();
        if ($valid == false) {
            $this->triggerError("diff calculation was invalid. abort", E_USER_NOTICE);
            return false;
        }
        $sqlArr = NULL;
        $success = true;
        if ($this->updateByInsert && $this->insertHandler != NULL) {
            $sqlArr = $this->insertHandler->createInsertStatement();
            $success = $success && $this->execStatement($db, $sqlArr);
        } elseif (!$this->updateByInsert) {
            $sqlArr = $this->getUpdater()->getUpdates();
            foreach ($sqlArr as $uuid => $statement) { // the uuid is important to get the updated object
                // exec statements get also the object by uui to updates his context
                $success = $success && $this->execStatement($db, $statement, $uuid);
            }
        }

        // get out if some statements failed
        if (!$success) {
            return false;
        }

        if ($this->newItems && $this->insertHandler != NULL) {
            foreach ($this->newItemsStorage as $newItem) {
                $success = $success && $this->applyToInsert($newItem);
            }
            $success = $success && $this->execStatement($db,
                    $this->insertHandler->createInsertStatement());
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
        $this->insertHandler = NULL; // remove the insertHandler so all cached insert are gone
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
        } else {
            $this->triggerError(
                "error while executing ["
                . $sql . "] error?("
                . $result->getError()
                . ') or affectedRows==0?('
                . $db->getlastAffectedRows()
                . ')'
                , E_USER_WARNING
            );
        }
        $this->nonAffectingStatements[] = $sql;
        return false;
    }

    /**
     * updates PropsFactory object clone so
     * on next diff calculation there should no diff found
     * @param string|null $uuid
     */
    private function updateCloneByUid(string|null $uuid)
    {
        $clone = $this->getUpdatedPropById($uuid);

        if ($uuid == NULL || $clone == NULL) {
            return;
        }

        foreach ($clone as $clonedProp => $clonedValue) {
            if (!in_array($clonedProp, $this->ignoredProps)) {
                if (is_object($clone->$clonedProp)) {
                    $clone->$clonedProp = clone $clone->__origin->$clonedProp; // TODO: no reference on objects
                } else {
                    $clone->$clonedProp = $clone->__origin->$clonedProp; // TODO: no reference on objects
                }
            }
        }
        $this->log(LoggingEntryPoint::DEBUG, "clone created from", $uuid, "clone", $clone);
    }

    /**
     * @param Database $db
     * @return bool|mysqli_result
     * @throws Exception
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
    public function getStatements(): array
    {
        return $this->statements;
    }

    /**
     * all statements that was used to update
     * database
     * @return array
     */
    public function getNonAffectingStatements(): array
    {
        return $this->nonAffectingStatements;
    }

    /**
     * get the update builder
     * @return UpdateStatement
     */
    private function getUpdater()
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
     * @param PropsFactory $template
     * @return InsertStatement
     */
    private function getInsert(PropsFactory $template): InsertStatement
    {
        if ($this->insertHandler == NULL) {
            $this->insertHandler = new InsertStatement(
                $this->getTableName(),
                $this->onDuplicateKeyOnInsert
            );
            $this->insertHandler->addFieldsByProps($template);
        }
        return $this->insertHandler;
    }

    /**
     * calculates the different from
     * a cloned Props object to find
     * the fields that have changed
     * and needs a update
     * @param PropsFactory $clone
     * @return bool
     */
    private function getDiff(PropsFactory $clone)
    {
        $compare = $clone->__origin;
        $diffFound = false;
        foreach ($clone as $propName => $propValue) {
            if (!in_array($propName, $this->ignoredProps) && !$this->matching(
                    $compare->$propName,
                    $propValue)) {
                $diffFound = true;
            }
        }
        if ($diffFound && $this->updateByInsert) {
            $this->onInsert($compare);
            $this->applyToInsert($compare);
        } elseif ($diffFound && !$this->updateByInsert) {
            $this->onUpdate($compare);

            $this->getUpdater()->updateDiff($clone, $compare,
                $this->ignoredProps);
        }
        return $diffFound;
    }

    /**
     * validate against unsigned for the
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
     * @param mixed $resetValue
     * @return boolean
     */
    private function validUnsigned(PropsFactory $compare, string $fieldName, $resetValue)
    {
        if (!in_array($fieldName, $this->ignoredProps) && in_array($fieldName, $this->unsignedFields)) {
            if ($resetValue >= 0 && $compare->$fieldName < 0) {
                $compare->$fieldName = $resetValue;
                return false;
            }
        }
        return true;
    }

    /**
     * checks if the two values matching.
     * this check did not check against
     * different types, because the only
     * thing what matters is the value, not
     * the type in this case.
     * @param mixed $valueLeft
     * @param mixed $valueRight
     * @return bool
     */
    private function matching($valueLeft, $valueRight): bool
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
     * register property for inserting.
     * returns false if a check against
     * the unsigned fields is not positive the
     * make sure no negative amounts was inserted
     * @param PropsFactory $new
     * @return bool
     */
    private function applyToInsert(PropsFactory $new): bool
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
     * that will be updated
     * @param PropsFactory $item
     */
    protected function onUpdate(PropsFactory &$item)
    {

    }

    /**
     * overwrite for own handling on items
     * that will be inserted
     * @param PropsFactory $item
     */
    protected function onInsert(PropsFactory &$item)
    {

    }

}
