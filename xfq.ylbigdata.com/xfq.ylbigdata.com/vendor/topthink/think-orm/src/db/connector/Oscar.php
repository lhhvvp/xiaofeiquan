<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Author: liuyongsheng <liuyongsheng@shentongdata.com>
// +----------------------------------------------------------------------

namespace think\db\connector;

use PDO;
use think\db\BaseQuery;
use think\db\PDOConnection;

/**
 * 神通数据库驱动
 */
class Oscar extends PDOConnection
{
    /**
     * 解析pdo连接的dsn信息
     * @access protected
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn(array $config): string
    {
        $dsn = 'aci:dbname=';

        if (!empty($config['hostname'])) {
            //  Oscar Instant Client
            $dsn .= $config['hostname'] . ($config['hostport'] ? ':' . $config['hostport'] : '') . '/';
        }

        $dsn .= $config['database'];

        if (!empty($config['charset'])) {
            $dsn .= ';charset=' . $config['charset'];
        }
        
        return $dsn;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @param string $tableName
     * @return array
     */
    public function getFields(string $tableName): array
    {
        [$tableName] = explode(' ', $tableName);
        $sql         = "select a.column_name,data_type,DECODE (nullable, 'Y', 0, 1) bnotnull,data_default, DECODE (A .column_name,b.column_name,1,0) pk from all_tab_columns a,(select column_name from all_constraints c, all_cons_columns col where c.constraint_name = col.constraint_name and c.constraint_type = 'P' and c.table_name = '" . strtoupper($tableName) . "' ) b where table_name = '" . strtoupper($tableName) . "' and a.column_name = b.column_name (+)";

        $pdo    = $this->getPDOStatement($sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];

        if ($result) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val);

                $info[$val['column_name']] = [
                    'name'    => $val['column_name'],
                    'type'    => $val['data_type'],
                    'notnull' => $val['bnotnull'],
                    'default' => $val['data_default'],
                    'primary' => $val['pk'],
                    'autoinc' => $val['pk'],
                ];
            }
        }

        return $this->fieldCase($info);
    }

    /**
     * 取得数据库的表信息（暂时实现取得用户表信息）
     * @access   public
     * @param string $dbName
     * @return array
     */
    public function getTables(string $dbName = ''): array
    {
        $sql    = "select table_name from all_tables where table_name not like 'SYS_%' AND table_name not like 'V_SYS_%' AND table_name not like 'V$%' AND table_name not like 'DBMS_%'";
        $pdo    = $this->getPDOStatement($sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];

        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @param BaseQuery $query    查询对象
     * @param string    $sequence 自增序列名
     * @return mixed
     */
    public function getLastInsID(BaseQuery $query, string $sequence = null)
    {
        if($sequence)
        {
            $pdo = $this->linkID->query("select {$sequence}.currval as id from dual");
        }
        else
        {
            $pdo = $this->linkID->query("SELECT last_insert_id()");
        }
        $result = $pdo->fetchColumn();

        return $result;
    }

    protected function supportSavepoint(): bool
    {
        return true;
    }
}
