<?php

class Tabledata_Model extends Model
{

    private
        $_allowable_fields = array(),
        $_joins = array(),
        $_wheres = array(),
        $_escape_identifiers = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function fetchAllowable()
    {
        if (!empty($this->_allowable_fields)) {

            try {
//                var_dump($this->_compileSelect());exit;
                $query = $this->_db->prepare($this->_compileSelect());
                $query->execute();
                return $query->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $ex) {
                controller()->addError($ex->getMessage() . " [TD105]");
            }
        } else {
            controller()->addError("No allowable data fields could be retrieved. [TD102]");
        }
        return false;
    }

    protected function _addAllowableField($field = null, $no_prefix = false)
    {
        if (!empty($field) && is_string($field)) {
            if (!$no_prefix && strpos($field, ".") === false) {
                $field = "main." . $field;
            }
            $this->_allowable_fields[] = $this->_escapeIdentifier($field);
        }
        return $this;
    }

    protected function _addAllowableFields($fields = null)
    {
        if (!empty($fields) && is_array($fields)) {
            foreach (array_values($fields) as $field) {
                $this->_addAllowableField($field);
            }
        }
        return $this;
    }

    protected function _disableIdentifierEscaping()
    {
        $this->_escape_identifiers = false;
        return $this;
    }

    protected function _enableIdentifierEscaping()
    {
        $this->_escape_identifiers = true;
        return $this;
    }

    protected function _addJoin($table, $tableAlias = null, $condition = null, $type = "left")
    {
        $this->_joins[] = array(
            "table" => $this->_escapeIdentifier($table),
            "condition" => $this->_escapeIdentifier($condition),
            "type" => $type,
            "alias" => $this->_escapeIdentifier((!empty($tableAlias) ? $tableAlias : $table))
        );
        return $this;
    }

    protected function _compileSelect()
    {
        $query = "\nSELECT\n";
        $query .= $this->_getSelectList();
        $query .= "FROM {$this->_escapeIdentifier($this->_table_name)}\n";
        $query .= "    AS {$this->_escapeIdentifier('main')}\n\n";

        if (!empty($this->_joins)) {
            foreach ($this->_joins as $join) {
                $query .= strtoupper($join['type']) . " JOIN {$join['table']}\n";
                $query .= "    AS {$join['alias']}\n";
                if (!empty($join['condition']) && is_string($join['condition'])) {
                    $query .= "    ON {$join['condition']}\n\n";
                }
            }
        }

        if (!empty($this->_wheres)) {
            $query .= "WHERE (\n";
            $first = true;
            foreach ($this->_wheres as $where) {
                $query .= "    " . ($first ? "   " : "AND") . " {$where}\n";
                $first = false;
            }
        }

        return $query . ";";
    }

    protected function _escapeIdentifier($identifier)
    {
        if (!$this->_escape_identifiers || empty($identifier) || is_numeric($identifier) || !preg_match('/([^\d])/',
                $identifier)
        ) {
            return $identifier;
        }
        if (strpos($identifier, '=') !== false) {
            $return_parts = "";
            foreach (explode('=', $identifier) as $part) {
                $return_parts [] = $this->_escapeIdentifier($part);
            }
            return join(" = ", $return_parts);
        }
        if (strpos($identifier, ',') !== false) {
            $return_parts = "";
            foreach (explode(',', $identifier) as $part) {
                $return_parts [] = $this->_escapeIdentifier($part);
            }
            return join(", ", $return_parts);
        }
        $identifier_parts = explode('.', trim($identifier));
        foreach ($identifier_parts as $i => $identifier_part) {
            $sub_parts = explode(" ", $identifier_part);
            $alias_clean = $sub_parts[0]; //remove issues with things like colname as Something
            unset($sub_parts[0]); //so that we can implode the rest on later
            if (preg_match('/(\s*\*\s*)$/', $alias_clean)) {
                $identifier_parts[$i] = '*' . (!empty($sub_parts) ? ' ' . join(" ", $sub_parts) : '');
                continue;
            }
            $backtick_count = substr_count($alias_clean, '`');
            if ($backtick_count % 2 === 1) { //odd number of backticks? lets clean that otherwise assume it was escaped correctly.
                $identifier_parts[$i] = '`' . str_replace('`', '',
                        $alias_clean) . '`' . (!empty($sub_parts) ? ' ' . join(" ", $sub_parts) : '');
            } elseif ($backtick_count === 0) {
                $identifier_parts[$i] = '`' . $alias_clean . '`' . (!empty($sub_parts) ? ' ' . join(" ",
                            $sub_parts) : '');
            }
        }
        return join(".", $identifier_parts);
    }

    private function _getSelectList()
    {
        $list_string = "";
        $first = true;
        foreach ($this->_allowable_fields as $field) {
            $list_string .= ($first ? "" : ",\n") . "    {$field}";
            $first = false;
        }
        return $list_string . "\n\n";
    }
}