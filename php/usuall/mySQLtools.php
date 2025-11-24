<?php

class myConn {
    public $conn;
    private $lastError = null;
    private $preparedStatements = []; // 用于缓存预处理语句

    function connect($servername, $username, $password, $dbname) {
        try {
            $this->conn = new mysqli($servername, $username, $password, $dbname);
            
            // 设置字符集为UTF-8，避免字符编码问题
            $this->conn->set_charset("utf8mb4");
            
            if ($this->conn->connect_error) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => $this->conn->connect_errno,
                    "errorMsg" => $this->conn->connect_error,
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "connect",
                    "servername" => $servername,
                    "dbname" => $dbname,
                    "code" => "DB_CONNECT_ERROR"
                ];
                return $this->lastError;
            }
            
            return [
                "status" => true,
                "message" => "连接成功",
                "timestamp" => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "connect",
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "servername" => $servername,
                "dbname" => $dbname,
                "code" => "DB_CONNECT_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
    
    // 执行查询并返回详细错误信息
    function query($sql) {
        try {
            if (!$this->conn) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => -1,
                    "errorMsg" => "数据库未连接",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "query",
                    "sql" => $sql,
                    "code" => "DB_NOT_CONNECTED"
                ];
                return $this->lastError;
            }
            
            $result = $this->conn->query($sql);
            
            if ($result === false) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => $this->conn->errno,
                    "errorMsg" => $this->conn->error,
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "query",
                    "sql" => $sql,
                    "affectedRows" => null,
                    "insertId" => null,
                    "code" => "DB_QUERY_ERROR"
                ];
                return $this->lastError;
            }
            
            // 查询成功
            return [
                "status" => true,
                "result" => $result,
                "affectedRows" => $this->conn->affected_rows,
                "insertId" => $this->conn->insert_id,
                "timestamp" => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "query",
                "sql" => $sql,
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "code" => "DB_QUERY_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
    
    // 执行预处理语句，防止SQL注入
    private function executePreparedStatement($sql, $params = [], $types = '') {
        try {
            if (!$this->conn) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => -1,
                    "errorMsg" => "数据库未连接",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "prepared statement",
                    "sql" => $sql,
                    "code" => "DB_NOT_CONNECTED"
                ];
                return $this->lastError;
            }
            
            // 生成语句缓存键
            $cacheKey = md5($sql);
            
            // 检查是否已有缓存的预处理语句
            if (!isset($this->preparedStatements[$cacheKey])) {
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    $this->lastError = [
                        "status" => false,
                        "errorNo" => $this->conn->errno,
                        "errorMsg" => $this->conn->error,
                        "timestamp" => date('Y-m-d H:i:s'),
                        "operation" => "prepare statement",
                        "sql" => $sql,
                        "code" => "DB_PREPARE_ERROR"
                    ];
                    return $this->lastError;
                }
                $this->preparedStatements[$cacheKey] = $stmt;
            } else {
                $stmt = $this->preparedStatements[$cacheKey];
                // 重置语句，以便再次使用
                $stmt->reset();
            }
            
            // 如果有参数，绑定参数
            if (!empty($params)) {
                // 如果没有提供类型，自动检测
                if (empty($types)) {
                    $types = '';
                    foreach ($params as $param) {
                        if (is_int($param)) {
                            $types .= 'i';
                        } elseif (is_float($param)) {
                            $types .= 'd';
                        } else {
                            $types .= 's';
                        }
                    }
                }
                
                // 绑定参数
                $bindParams = array_merge([$types], $params);
                $bindRefs = [];
                foreach ($bindParams as $key => $value) {
                    $bindRefs[$key] = &$bindParams[$key];
                }
                
                call_user_func_array([$stmt, 'bind_param'], $bindRefs);
            }
            
            // 执行语句
            if (!$stmt->execute()) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => $stmt->errno,
                    "errorMsg" => $stmt->error,
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "execute statement",
                    "sql" => $sql,
                    "code" => "DB_EXECUTE_ERROR"
                ];
                return $this->lastError;
            }
            
            return [
                "status" => true,
                "stmt" => $stmt,
                "affectedRows" => $stmt->affected_rows,
                "insertId" => $stmt->insert_id,
                "timestamp" => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "prepared statement",
                "sql" => $sql,
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "code" => "DB_STATEMENT_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
    
    // 插入数据 - 使用预处理语句防止SQL注入
    function insert($table, $data) {
        try {
            if (!$this->conn) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => -1,
                    "errorMsg" => "数据库未连接",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "insert",
                    "table" => $table,
                    "code" => "DB_NOT_CONNECTED"
                ];
                return $this->lastError;
            }
            
            // 验证表名，防止表名注入
            $table = $this->sanitizeTableName($table);
            
            // 准备字段名和占位符
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            
            // 构建SQL语句
            $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            // 执行预处理语句
            $result = $this->executePreparedStatement($sql, array_values($data));
            
            if (!$result['status']) {
                return $result;
            }
            
            return [
                "status" => true,
                "message" => "数据插入成功",
                "insertId" => $result['insertId'],
                "affectedRows" => $result['affectedRows'],
                "timestamp" => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "insert",
                "table" => $table,
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "code" => "DB_INSERT_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
    
    // 批量插入数据 - 性能优化版本
    function batchInsert($table, $dataArray) {
        try {
            if (!$this->conn) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => -1,
                    "errorMsg" => "数据库未连接",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "batch insert",
                    "table" => $table,
                    "code" => "DB_NOT_CONNECTED"
                ];
                return $this->lastError;
            }
            
            // 验证表名
            $table = $this->sanitizeTableName($table);
            
            if (empty($dataArray)) {
                return [
                    "status" => true,
                    "message" => "没有数据需要插入",
                    "affectedRows" => 0,
                    "timestamp" => date('Y-m-d H:i:s')
                ];
            }
            
            // 开始事务以提高性能
            $this->conn->begin_transaction();
            
            try {
                $fields = array_keys($dataArray[0]);
                $placeholders = array_fill(0, count($fields), '?');
                $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                
                $totalRows = 0;
                
                foreach ($dataArray as $data) {
                    $result = $this->executePreparedStatement($sql, array_values($data));
                    if (!$result['status']) {
                        throw new Exception($result['errorMsg'], $result['errorNo']);
                    }
                    $totalRows += $result['affectedRows'];
                }
                
                // 提交事务
                $this->conn->commit();
                
                return [
                    "status" => true,
                    "message" => "批量数据插入成功",
                    "affectedRows" => $totalRows,
                    "timestamp" => date('Y-m-d H:i:s')
                ];
            } catch (Exception $e) {
                // 回滚事务
                $this->conn->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "batch insert",
                "table" => $table,
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "code" => "DB_BATCH_INSERT_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
    
    // 查询数据 - 支持条件、排序、分页等
    function select($table, $options = []) {
        try {
            if (!$this->conn) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => -1,
                    "errorMsg" => "数据库未连接",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "select",
                    "table" => $table,
                    "code" => "DB_NOT_CONNECTED"
                ];
                return $this->lastError;
            }
            
            // 验证表名
            $table = $this->sanitizeTableName($table);
            
            // 默认选项
            $defaultOptions = [
                'fields' => '*',
                'where' => [],
                'order' => '',
                'limit' => 0,
                'offset' => 0,
                'join' => '',
                'group' => '',
                'having' => ''
            ];
            
            $options = array_merge($defaultOptions, $options);
            
            // 构建SQL语句
            $sql = "SELECT {$options['fields']} FROM {$table}";
            
            // 处理JOIN
            if (!empty($options['join'])) {
                $sql .= " {$options['join']}";
            }
            
            // 处理WHERE条件
            $params = [];
            $types = '';
            if (!empty($options['where'])) {
                $whereClauses = [];
                foreach ($options['where'] as $field => $value) {
                    if (is_array($value)) {
                        // 支持复杂条件，如 ['>', 5], ['LIKE', '%test%']
                        $operator = $value[0];
                        $val = $value[1];
                        $whereClauses[] = "{$field} {$operator} ?";
                        $params[] = $val;
                        
                        // 设置类型
                        if (is_int($val)) $types .= 'i';
                        elseif (is_float($val)) $types .= 'd';
                        else $types .= 's';
                    } else {
                        // 简单相等条件
                        $whereClauses[] = "{$field} = ?";
                        $params[] = $value;
                        
                        // 设置类型
                        if (is_int($value)) $types .= 'i';
                        elseif (is_float($value)) $types .= 'd';
                        else $types .= 's';
                    }
                }
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
            
            // 处理GROUP BY
            if (!empty($options['group'])) {
                $sql .= " GROUP BY {$options['group']}";
            }
            
            // 处理HAVING
            if (!empty($options['having'])) {
                $sql .= " HAVING {$options['having']}";
            }
            
            // 处理ORDER BY
            if (!empty($options['order'])) {
                $sql .= " ORDER BY {$options['order']}";
            }
            
            // 处理LIMIT和OFFSET
            if ($options['limit'] > 0) {
                $sql .= " LIMIT ?";
                $params[] = $options['limit'];
                $types .= 'i';
                
                if ($options['offset'] > 0) {
                    $sql .= " OFFSET ?";
                    $params[] = $options['offset'];
                    $types .= 'i';
                }
            }
            
            // 执行预处理语句
            $result = $this->executePreparedStatement($sql, $params, $types);
            
            if (!$result['status']) {
                return $result;
            }
            
            // 获取结果集
            $stmt = $result['stmt'];
            $resultSet = $stmt->get_result();
            
            // 将结果转换为关联数组
            $data = [];
            while ($row = $resultSet->fetch_assoc()) {
                $data[] = $row;
            }
            
            // 释放结果集
            $resultSet->free();
            
            return [
                "status" => true,
                "data" => $data,
                "count" => count($data),
                "timestamp" => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "select",
                "table" => $table,
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "code" => "DB_SELECT_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
    
    // 更新数据 - 使用预处理语句防止SQL注入
    function update($table, $data, $where = []) {
        try {
            if (!$this->conn) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => -1,
                    "errorMsg" => "数据库未连接",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "update",
                    "table" => $table,
                    "code" => "DB_NOT_CONNECTED"
                ];
                return $this->lastError;
            }
            
            // 验证表名
            $table = $this->sanitizeTableName($table);
            
            // 准备更新字段和占位符
            $setClauses = [];
            $params = [];
            $types = '';
            
            foreach ($data as $field => $value) {
                $setClauses[] = "{$field} = ?";
                $params[] = $value;
                
                // 设置类型
                if (is_int($value)) $types .= 'i';
                elseif (is_float($value)) $types .= 'd';
                else $types .= 's';
            }
            
            // 构建SQL语句
            $sql = "UPDATE {$table} SET " . implode(', ', $setClauses);
            
            // 处理WHERE条件
            if (!empty($where)) {
                $whereClauses = [];
                foreach ($where as $field => $value) {
                    if (is_array($value)) {
                        // 支持复杂条件
                        $operator = $value[0];
                        $val = $value[1];
                        $whereClauses[] = "{$field} {$operator} ?";
                        $params[] = $val;
                        
                        // 设置类型
                        if (is_int($val)) $types .= 'i';
                        elseif (is_float($val)) $types .= 'd';
                        else $types .= 's';
                    } else {
                        // 简单相等条件
                        $whereClauses[] = "{$field} = ?";
                        $params[] = $value;
                        
                        // 设置类型
                        if (is_int($value)) $types .= 'i';
                        elseif (is_float($value)) $types .= 'd';
                        else $types .= 's';
                    }
                }
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
            
            // 执行预处理语句
            $result = $this->executePreparedStatement($sql, $params, $types);
            
            if (!$result['status']) {
                return $result;
            }
            
            return [
                "status" => true,
                "message" => "数据更新成功",
                "affectedRows" => $result['affectedRows'],
                "timestamp" => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "update",
                "table" => $table,
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "code" => "DB_UPDATE_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
    
    // 删除数据 - 使用预处理语句防止SQL注入
    function delete($table, $where = []) {
        try {
            if (!$this->conn) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => -1,
                    "errorMsg" => "数据库未连接",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "delete",
                    "table" => $table,
                    "code" => "DB_NOT_CONNECTED"
                ];
                return $this->lastError;
            }
            
            // 验证表名
            $table = $this->sanitizeTableName($table);
            
            // 安全检查：不允许没有WHERE条件的删除
            if (empty($where)) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => 42000,
                    "errorMsg" => "不允许没有WHERE条件的删除操作，请使用truncate或添加条件",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "delete",
                    "table" => $table,
                    "code" => "DB_DELETE_WITHOUT_WHERE"
                ];
                return $this->lastError;
            }
            
            // 构建SQL语句
            $sql = "DELETE FROM {$table}";
            
            // 处理WHERE条件
            $params = [];
            $types = '';
            $whereClauses = [];
            
            foreach ($where as $field => $value) {
                if (is_array($value)) {
                    // 支持复杂条件
                    $operator = $value[0];
                    $val = $value[1];
                    $whereClauses[] = "{$field} {$operator} ?";
                    $params[] = $val;
                    
                    // 设置类型
                    if (is_int($val)) $types .= 'i';
                    elseif (is_float($val)) $types .= 'd';
                    else $types .= 's';
                } else {
                    // 简单相等条件
                    $whereClauses[] = "{$field} = ?";
                    $params[] = $value;
                    
                    // 设置类型
                    if (is_int($value)) $types .= 'i';
                    elseif (is_float($value)) $types .= 'd';
                    else $types .= 's';
                }
            }
            
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
            
            // 执行预处理语句
            $result = $this->executePreparedStatement($sql, $params, $types);
            
            if (!$result['status']) {
                return $result;
            }
            
            return [
                "status" => true,
                "message" => "数据删除成功",
                "affectedRows" => $result['affectedRows'],
                "timestamp" => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "delete",
                "table" => $table,
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "code" => "DB_DELETE_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
    
    // 获取表的总行数
    function count($table, $where = []) {
        try {
            if (!$this->conn) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => -1,
                    "errorMsg" => "数据库未连接",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "count",
                    "table" => $table,
                    "code" => "DB_NOT_CONNECTED"
                ];
                return $this->lastError;
            }
            
            // 验证表名
            $table = $this->sanitizeTableName($table);
            
            // 构建SQL语句
            $sql = "SELECT COUNT(*) as count FROM {$table}";
            
            // 处理WHERE条件
            $params = [];
            $types = '';
            if (!empty($where)) {
                $whereClauses = [];
                foreach ($where as $field => $value) {
                    if (is_array($value)) {
                        $operator = $value[0];
                        $val = $value[1];
                        $whereClauses[] = "{$field} {$operator} ?";
                        $params[] = $val;
                        
                        if (is_int($val)) $types .= 'i';
                        elseif (is_float($val)) $types .= 'd';
                        else $types .= 's';
                    } else {
                        $whereClauses[] = "{$field} = ?";
                        $params[] = $value;
                        
                        if (is_int($value)) $types .= 'i';
                        elseif (is_float($value)) $types .= 'd';
                        else $types .= 's';
                    }
                }
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
            
            // 执行预处理语句
            $result = $this->executePreparedStatement($sql, $params, $types);
            
            if (!$result['status']) {
                return $result;
            }
            
            // 获取结果
            $stmt = $result['stmt'];
            $row = $stmt->get_result()->fetch_assoc();
            
            return [
                "status" => true,
                "count" => (int)$row['count'],
                "timestamp" => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "count",
                "table" => $table,
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "code" => "DB_COUNT_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
    
    // 清理表名，防止表名注入
    private function sanitizeTableName($table) {
        // 只允许字母、数字、下划线和表名限定符
        return preg_replace('/[^a-zA-Z0-9_`\.]/', '', $table);
    }
    
    // 获取最后一次错误信息
    function getLastError() {
        return $this->lastError;
    }
    
    // 关闭连接并释放预处理语句
    function close() {
        // 释放所有预处理语句
        foreach ($this->preparedStatements as $stmt) {
            if ($stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
        }
        $this->preparedStatements = [];
        
        // 关闭数据库连接
        if ($this->conn) {
            return $this->conn->close();
        }
        return true;
    }
    
    // 开始事务
    function beginTransaction() {
        try {
            if (!$this->conn) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => -1,
                    "errorMsg" => "数据库未连接",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "begin transaction",
                    "code" => "DB_NOT_CONNECTED"
                ];
                return $this->lastError;
            }
            
            if ($this->conn->begin_transaction()) {
                return [
                    "status" => true,
                    "message" => "事务已开始",
                    "timestamp" => date('Y-m-d H:i:s')
                ];
            } else {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => $this->conn->errno,
                    "errorMsg" => $this->conn->error,
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "begin transaction",
                    "code" => "DB_TRANSACTION_ERROR"
                ];
                return $this->lastError;
            }
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "begin transaction",
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "code" => "DB_TRANSACTION_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
    
    // 提交事务
    function commit() {
        try {
            if (!$this->conn) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => -1,
                    "errorMsg" => "数据库未连接",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "commit",
                    "code" => "DB_NOT_CONNECTED"
                ];
                return $this->lastError;
            }
            
            if ($this->conn->commit()) {
                return [
                    "status" => true,
                    "message" => "事务已提交",
                    "timestamp" => date('Y-m-d H:i:s')
                ];
            } else {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => $this->conn->errno,
                    "errorMsg" => $this->conn->error,
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "commit",
                    "code" => "DB_COMMIT_ERROR"
                ];
                return $this->lastError;
            }
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "commit",
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "code" => "DB_COMMIT_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
    
    // 回滚事务
    function rollback() {
        try {
            if (!$this->conn) {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => -1,
                    "errorMsg" => "数据库未连接",
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "rollback",
                    "code" => "DB_NOT_CONNECTED"
                ];
                return $this->lastError;
            }
            
            if ($this->conn->rollback()) {
                return [
                    "status" => true,
                    "message" => "事务已回滚",
                    "timestamp" => date('Y-m-d H:i:s')
                ];
            } else {
                $this->lastError = [
                    "status" => false,
                    "errorNo" => $this->conn->errno,
                    "errorMsg" => $this->conn->error,
                    "timestamp" => date('Y-m-d H:i:s'),
                    "operation" => "rollback",
                    "code" => "DB_ROLLBACK_ERROR"
                ];
                return $this->lastError;
            }
        } catch (Exception $e) {
            $this->lastError = [
                "status" => false,
                "errorNo" => $e->getCode(),
                "errorMsg" => $e->getMessage(),
                "timestamp" => date('Y-m-d H:i:s'),
                "operation" => "rollback",
                "exception" => get_class($e),
                "trace" => $e->getTraceAsString(),
                "code" => "DB_ROLLBACK_EXCEPTION"
            ];
            return $this->lastError;
        }
    }
}

?>