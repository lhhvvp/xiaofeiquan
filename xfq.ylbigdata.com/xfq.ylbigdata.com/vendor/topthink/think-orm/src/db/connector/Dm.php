<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\db\connector;

use PDO;
use think\db\BaseQuery;
use think\db\Connection;
use think\db\PDOConnection;
use think\db\ConnectionInterface;

/**
 * Dm数据库驱动
 */
// class Dm extends Connection
class Dm extends PDOConnection
{
    /**
     * 解析pdo连接的dsn信息
     * @access protected
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn(array $config): string
    {
        $dsn = 'dm:dbname=';

        if (!empty($config['hostname'])) {
            //  Oracle Instant Client
            $dsn .= '//' . $config['hostname'] . ($config['hostport'] ? ':' . $config['hostport'] : '') . '/';
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
        list($tableName) = explode(' ', $tableName);
        /*$sql         = "SELECT 
            a.column_name, 
            data_type, 
            DECODE(nullable, 'Y', 0, 1) as notnull, 
            data_default, 
            DECODE(A .column_name,b.column_name,1,0) as pk 
        FROM 
            all_tab_columns a, 
            (
                SELECT column_name 
                FROM all_constraints c
                INNER JOIN all_cons_columns col ON c.constraint_name = col.constraint_name 
                WHERE c.constraint_type = 'P' AND c.table_name = '" . strtoupper($tableName) . "'
            ) b 
        WHERE 
            table_name = '" . strtoupper($tableName) . "' AND a.column_name = b.column_name";*/
        $sql = "select B.COLUMN_NAME,B.DATA_TYPE,B.NULLABLE,A.COMMENTS,B.DATA_DEFAULT  from user_col_comments A LEFT JOIN user_tab_columns B ON A.COLUMN_NAME = B.COLUMN_NAME where A.TABLE_NAME = '" . strtoupper($tableName) . "' AND B.TABLE_NAME = '" . strtoupper($tableName) . "'";
        // $pdo    = $this->query($sql, [], false, true);
        // $result = $pdo->fetchAll(PDO::FETCH_ASSOC);

        $pdo    = $this->getPDOStatement($sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];

        if ($result) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val);
                $pk = $val['column_name']=='ID' ? 1 : '';
                $info[$val['column_name']] = [
                    'name'    => $val['column_name'],
                    'type'    => $val['data_type'],
                    'notnull' => $val['nullable'] == 'Y' ? "yes" : 'no',
                    'default' => $val['data_default'],
                    'primary' => $pk,
                    'autoinc' => $pk,
                    'comment' => $val['comments'],
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
        $sql    = 'select table_name from all_tables';
        $pdo    = $this->getPDOStatement($sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];

        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }

        return $info;
    }

    /**
     * 获取最近插入的ID（如果使用自增列，需去掉此方法）
     * @access public
     * @param BaseQuery $query    查询对象
     * @param string    $sequence 自增序列名
     * @return mixed
     */
    public function getLastInsID(BaseQuery $query, string $sequence = null)
    {
        if(!is_null($sequence)) {
            $pdo    = $this->linkID->query("select {$sequence}.currval as id from dual");
            $result = $pdo->fetchColumn();
        }
        return $result ?? null;
    }

    /**
     * SQL性能分析
     * @access protected
     * @param string $sql
     * @return array
     */
    protected function getExplain(string $sql)
    {
        return [];
    }

    protected function supportSavepoint(): bool
    {
        return true;
    }
}