<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/5/15
 * Time: 14:22
 */

namespace app\admin\model;

use think\Model;

class PublicyangModel extends Model
{
    public function __construct(){
        parent::__construct();
    }
    //查询时间
    public $query_stime = 0;
    public $query_etime = 0;

    /**
     * 获取列表
     *
     * @param int $page 页码
     *        int $limit 每页显示条数
     *        string $where 条件
     *        string $order 排序字段
     *        string $by 排序值
     * @return array
     */
    public function getList($page=1,$limit=200,$where='',$order='id',$by='desc'){
        $condition = 'WHERE 1=1 ';
        if($where != ''){
            $condition .= $where;
        }
        if($limit == 0){
            $limit = 200;
        }
        //总行数
        $query = "SELECT COUNT(1)  AS `numrows`  FROM `{$this->table}` {$condition}";
        $total_arr = $this->query($query);
        $total = $total_arr[0]['numrows'];

        $total_page = ceil($total / $limit);//总页数
        if($page <= 1){
            $page = 1;
        }elseif($page >= $total_page){
            $page = $total_page;
        }
        $offset = ($page - 1 ) * $limit;
        if($offset<0){
            $offset = 0;
        }
        $_RE = array('total'=>$total);
        //查询字段
        $fields = $this->getListFields();
        $sql = "SELECT {$fields} FROM {$this->table} {$condition} ORDER BY {$order} {$by} LIMIT {$offset} , {$limit}";
//       var_dump($sql);die;
        $rs = $this->query($sql);
        if(!$rs){
            $_RE['rows'] = '';
            return $_RE;
        }else{
            $_RE['rows'] = $rs;
        }

        //若存在方法preprocess，将返回数据交给preprocess处理
        if(method_exists($this,'preprocess')){
            $stime = $this->query_stime;
            $etime = $this->query_etime;
            $_RE['rows'] = $this->preprocess($rs,$stime,$etime);
        }

        return $_RE;
    }

    /**
     * 将属性list_fields转为字符串
     */
    protected function getListFields(){
        if(property_exists(get_class($this),'list_fields')){
            $fields = '*';
            if(!empty($this->list_fields)){
                $fields = implode(',',$this->list_fields);
            }
        }else{
            $fields = '*';
        }
        return $fields;
    }

    /**
     * 根据id获取一条数据
     * @param int $id
     * @return array
     */
    public function getOne($id = 0){
        $id = $id+0;
        $rs = $this->where('id = '.$id)->find();
        return $rs;
    }

}