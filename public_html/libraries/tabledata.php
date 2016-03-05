<?php

class Tabledata_Model extends Model
{

    private
        $_allowable_fields = array(),
        $_joins = array(),
        $_wheres = array(),
        $_limit = null,
        $_offset = null,
        $_order_bys = array(),
        $_escape_identifiers = true,
        $_available_conditions = array(
        'like',
        'not like',
        '=',
        '!=',
        '>',
        '<',
        '>=',
        '<='
    );

    protected
        $_searchable = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function countRows()
    {

        try {
            $temp = $this->_allowable_fields;
            $this->_allowable_fields = array('COUNT(*) as "total"');
            $query = $this->_db->prepare($this->_compileSelect());
            $this->_bindWheres($query);
            $query->execute();
            $count = $query->fetch(PDO::FETCH_ASSOC);
            $this->_allowable_fields = $temp;
            return (int)$count['total'];
        } catch (PDOException $ex) {
            controller()->addError($ex->getMessage() . " [TD105]");
        }
        return false;
    }

    public function fetchAllowable()
    {
        if (!empty($this->_allowable_fields)) {

            try {
                $query = $this->_db->prepare($this->_compileSelect());
                $this->_bindWheres($query);
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

    public function addOrderBy($col, $direction = "asc")
    {
        if (empty($col) || !is_string($col)) {
            return $this;
        }

        $direction = strtolower($direction) !== "desc" ? "ASC" : "DESC";
        $this->_order_bys[] = $this->_escapeIdentifier($this->_normalizeColumnName($col)) . ' ' . $direction;
        return $this;
    }

    public function addWhere($col, $condition, $matchTo, $operator = "AND")
    {
        if (empty($col) || empty($condition) || empty($operator)) {
            return $this;
        }

        if (!is_string($operator) || !in_array(strtolower($condition), $this->_available_conditions)) {
            return $this;
        }

        $where = "";
        if (!empty($this->_wheres)) {
            $operator = strtoupper($operator);
            $where .= $operator === "AND" ? "AND " : "{$operator} ";
        }

        $where .= $this->_escapeIdentifier($this->_normalizeColumnName($col));
        $where .= ' ' . strtoupper($condition) . ' ';

        $this->_wheres[] = array(
            "query" => $where,
            "data" => $matchTo
        );

        return $this;
    }

    public function setLimit(
        $limit
    ) {
        if (empty($limit)) {
            return $this;
        }
        if (is_int($limit)) {
            $this->_limit = $limit;
        }
        return $this;
    }

    public function setOffset($offset)
    {
        if (empty($offset)) {
            return $this;
        }
        if (is_int($offset)) {
            $this->_offset = $offset;
        }
        return $this;
    }

    public function searchQuery($query_str, $comparison = "OR")
    {
        if (empty($this->_searchable)) {
            return $this;
        }

        $comparison = trim($comparison);
        if (!empty($comparison) && is_string($comparison)) {
            $comparison = strtoupper($comparison);
            if ($comparison !== "AND" && $comparison !== "OR") {
                $comparison = "OR";
            }
        }

        $search_parts = explode('|', $query_str); //multiple searches
        foreach ($search_parts as $search_str) {
            if (empty($search_str)) {
                continue;
            }

            $and_pos = strpos($search_str, "&&");
            if ($and_pos !== false && $and_pos > 0) {
                foreach (explode("&&", $search_str) as $str) {
                    $this->searchQuery($str, "AND");
                }
                continue;
            }

            $original_search_str = $search_str;
            $search_condition = $this->_determineSearchCondition($search_str, ':');
            $wildcards = strpos($search_condition, "LIKE") !== false;

            $str_parts = explode(':', $search_str); //specific column
            $str_beginning = $str_parts[0];
            $lower_part0 = strtolower($str_beginning);
            $str_ending = substr_replace($search_str, "", strpos($search_str, $str_beginning . ':'),
                strlen($str_beginning) + 1);

            if (count($str_parts) > 1 && array_key_exists($lower_part0, $this->_searchable) && !empty($str_ending)) {
                $matchTo = $wildcards ? '%' . $str_ending . '%' : $str_ending;
                $this->addWhere($this->_searchable[$lower_part0], $search_condition, $matchTo, $comparison);
                continue;
            }

            $matchTo = $wildcards ? '%' . $original_search_str . '%' : $original_search_str;

            if ($search_condition === "NOT LIKE") {
                $comparison = "AND";
            }

            foreach ($this->_searchable as $col => $translation) {
                $column = empty($translation) ? $col : $translation;
                if ($comparison === "OR") {
                    $this->addWhere($column, $search_condition, $matchTo, $comparison);
                } else {
                    //we're excluding any row that contains a certain string. however, things like a null value in a
                    //column's field will give unwanted results, so we need to allow them by overriding the normal
                    //addWhere() method. its up to the _compileSelect() method to add in the ending ')' to not break
                    //the query and return 0 records.
                    $column = $this->_escapeIdentifier($this->_normalizeColumnName($column));

                    $this->_wheres[] = array(
                        "query" => (empty($this->_wheres) ? '' : $comparison . ' ') . '(' . $column . " IS NULL OR " . $column . ' ' . $search_condition . ' ',
                        "data" => $matchTo
                    );
                }
            }

        }
        return $this;
    }

    protected function _determineSearchCondition(&$search_str, $conversion = ':')
    {
        $search_condition_tokens = array(
            ':' => "LIKE",
            ">=" => ">=",
            "<=" => "<=",
            "!=" => "!=",
            '>' => '>',
            '<' => '<',
            '=' => '='
        );
        $condition_opposites = array(
            "LIKE" => "NOT LIKE",
            '<' => '>',
            '>' => '<',
            "<=" => '>',
            ">=" => '<',
            '=' => "!=",
            "!=" => '=',
        );
        $search_condition = "LIKE";
        foreach ($search_condition_tokens as $token => $condition) {
            $token_pos = strpos($search_str, $token);
            if ($token_pos === false || $token_pos === 0) {
                continue;
            }
            $search_str = preg_replace('/(\s*' . $token . '\s*)/', $conversion, $search_str);
            $search_condition = $search_condition_tokens[$token];
            break;
        }

        if (substr($search_str, 0, 1) !== '!') {
            return $search_condition;
        }

        $search_str = substr($search_str, 1);
        if (array_key_exists($search_condition, $condition_opposites)) {
            return $condition_opposites[$search_condition];
        }

        return $search_condition;
    }

    protected function _addAllowableField($field = null, $no_prefix = false)
    {
        if (empty($field) || !is_string($field)) {
            return $this;
        }

        $this->_allowable_fields[] = $this->_escapeIdentifier($this->_normalizeColumnName($field, $no_prefix));

        return $this;
    }

    protected function _addAllowableFields($fields = null)
    {
        if (empty($fields) || !is_array($fields)) {
            return $this;
        }

        foreach (array_values($fields) as $field) {
            $this->_addAllowableField($field);
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
            $where_count = 0;
            foreach ($this->_wheres as $where) {
                $query .= "    {$where['query']}:w" . $where_count++;
                $check = strpos($where['query'], '(');
                if ($check !== false && $check < 5) {
                    $query .= ")\n";
                } else {
                    $query .= "\n";
                }
            }
            $query .= ")\n\n";
        }

        if (!empty($this->_order_bys)) {
            $query .= "ORDER BY\n";
            $query .= "    " . join(",\n    ", $this->_order_bys) . "\n\n";
        }

        if (!empty($this->_limit)) {
            $query .= "LIMIT {$this->_limit}\n";
            if (!empty($this->_offset)) {
                $query .= "    OFFSET {$this->_offset}";
            }
            $query .= "\n\n";
        }

        return $query . ';';
    }

    protected function _escapeIdentifier($identifier)
    {
        if (!$this->_escape_identifiers || empty($identifier) || is_numeric($identifier) || !preg_match('/([^\d])/',
                $identifier)
        ) {
            return $identifier;
        }
        if (strtoupper(substr($identifier, 0, 6)) === "CONCAT") {
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
            $sub_parts = explode(' ', $identifier_part);
            $alias_clean = $sub_parts[0]; //remove issues with things like colname as Something
            unset($sub_parts[0]); //so that we can implode the rest on later
            if (preg_match('/(\s*\*\s*)$/', $alias_clean)) {
                $identifier_parts[$i] = '*' . (!empty($sub_parts) ? ' ' . join(' ', $sub_parts) : '');
                continue;
            }
            $backtick_count = substr_count($alias_clean, '`');
            if ($backtick_count % 2 === 1) { //odd number of backticks? lets clean that otherwise assume it was escaped correctly.
                $identifier_parts[$i] = '`' . str_replace('`', '',
                        $alias_clean) . '`' . (!empty($sub_parts) ? ' ' . join(' ', $sub_parts) : '');
            } elseif ($backtick_count === 0) {
                $identifier_parts[$i] = '`' . $alias_clean . '`' . (!empty($sub_parts) ? ' ' . join(' ',
                            $sub_parts) : '');
            }
        }
        return join('.', $identifier_parts);
    }

    private function _bindWheres(PDOStatement $query)
    {
        if (!empty($this->_wheres)) {
            $where_count = 0;
            foreach ($this->_wheres as $where) {
                $query->bindParam(":w" . $where_count++, $where['data'], $this->_getPdoCasting($where['data']));
            }
        }
        return $query;
    }

    protected function _normalizeColumnName($col = null, $no_prefix = false)
    {

        if (empty($col) || !is_string($col)) {
            return $col;
        }

        if (strpos($col, ':') === 0) {
            $col = substr($col, 1);
        } elseif (!$no_prefix && strpos($col, '.') === false) {
            $col = "main." . $col;
        }
        return $col;
    }

    private function _getPdoCasting($var)
    {
        if (is_string($var)) {
            return PDO::PARAM_STR;
        } elseif (is_null($var)) {
            return PDO::PARAM_NULL;
        } elseif (is_numeric($var)) {
            return PDO::PARAM_INT;
        } elseif (is_bool($var)) {
            return PDO::PARAM_BOOL;
        }
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