<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/5/28
 * Time: 10:04
 */
namespace app\pc\controller;

use PHPExcel_IOFactory;
use PHPExcel;
use think\controller;

class UplaodYang extends Common
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 上传文件
     * Enter description here ...
     * @param unknown_type $filename
     * @param unknown_type $filetype
     * @param unknown_type $tmpfile
     * @param unknown_type $path
     */
    function upload($filename,$filetype,$tmpfile,$path)
    {
        if(empty($path))
        {
            $now=date("Y-m",time());
            $path= 'public/uploads/'.$now;
        }

        if(!is_dir($path))
        {
            $this->createFolder($path);
        }

        /*设置上传路径*/
        $savePath =$path."/";
        /*是否上传成功*/
        if (! copy ( $tmpfile, $savePath . $filename ))
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    function createFolder($path)
    {
        if (!file_exists($path))
        {
            $this->createFolder(dirname($path));
            mkdir($path, 0777);
        }
    }


    /**
     * 读取excel
     * @param $file excel文件路径
     * @return array
     */
    public function readExcel($file=''){
        if(empty($file) && file_exists($file)){
            return false;
        }
        $PHPExcel = new PHPExcel();
        $objPHPExcel = PHPExcel_IOFactory::load($file);
        $cell_collection = $objPHPExcel->getActiveSheet()->getCellCollection();
        $header = array();
        $values = array();
        foreach ($cell_collection as $cell) {
            $column = $objPHPExcel->getActiveSheet()->getCell($cell)->getColumn();
            $row = $objPHPExcel->getActiveSheet()->getCell($cell)->getRow();
            $data_value = $objPHPExcel->getActiveSheet()->getCell($cell)->getValue();
            //第一行为标题
            if ($row == 1) {
                $header[$row][$column] = $data_value;
            } else {
                $values[$row][$column] = $data_value;
            }
        }
        //excel无内容
        if(empty($cell_collection)){
            $data = array();
        }else{
            $data['header'] = $header;
            $data['values'] = $values;
        }
        return $data;
    }




}