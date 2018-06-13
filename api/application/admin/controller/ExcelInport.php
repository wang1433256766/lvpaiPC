<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/5/29
 * Time: 07:43
 */
namespace app\admin\controller;
use PHPExcel_IOFactory;
use PHPExcel;
use PHPExcel_Style_Alignment;
use think\Session;
use PHPExcel_Style_Border;

class ExcelInport extends Publicyang
{

    public function __construct(){
        parent::__construct();
    }


    /**
     * 订单导出
     * */
    public function orderInport($data){

    }
    public function writeExcel($data='',$title_info='',$filename='',$sta = ''){

        $objPHPExcel = new PHPExcel();
        if(empty($data)){
             return false;
        }
        if($sta=='channellist'){
            $tj = $data['tj'];
            $data = $data['rows'];
        }
        $user = Session::get('username');
        //设置文档属性
        $objPHPExcel->getProperties()->setCreator($user) //作者
        ->setLastModifiedBy($user) //最后修改者
        ->setTitle($filename) //标题
        ->setSubject($filename) //主题
        ->setDescription($filename) //描述
        ->setKeywords("office 2007 phpexcel php") //关键字
        ->setCategory("导出"); //类别
        //设置标题信息
        foreach ($title_info as $k => $v) {
            $column =  $this->stringFromColumnIndex($k);
            $width = isset($v['width']) ? $v['width'] : '';
            $height = isset($v['height']) ? $v['height'] : '';
            if(!empty($width)){
                //设置宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension($column)->setWidth($width);
            }
            if(!empty($height)){
                // 设置行高
                $objPHPExcel->getActiveSheet()->getRowDimension($k)->setRowHeight($height);
            }
            // 设置水平居中
            $objPHPExcel->getActiveSheet()->getStyle($column)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //标题
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($column . '2', iconv('utf-8', 'utf-8',$v['title']));
        }

        //最后一个标题在哪栏
        $title_length = count($title_info) - 1;
        $end_column = $this->stringFromColumnIndex($title_length);

        // 字体大小
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);

        //标题栏合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $end_column . '1');
        //设置样式 粗字体
        $objPHPExcel->getActiveSheet()->getStyle('A1:' . $end_column . '1')->getFont()->setBold(true);
        //填充标题数据
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', iconv('utf-8', 'utf-8',$filename));
        //填充内容
        $num = 3;
        if($sta=='order'){
            $amount = 0;
            $return_amount = 0;
        }
        if(!empty($data) && is_array($data)){
            foreach ($data as $index => $cont) {
                if($sta=='order'){
                    $amount += $cont['order_total'];
                    $return_amount += $cont['refund_price'];
                }
                foreach ($title_info as $k => $title) {
                    $field = $title['field'];
                    $cont_column = $this->stringFromColumnIndex($k);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cont_column . $num, iconv('utf-8', 'utf-8', $cont[$field]));
                }
                $num ++;
            }
            switch ($sta){
                case 'order':
                    $num_S = $num+1;
                    $objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($num_S), iconv('utf-8', 'utf-8',
                        '退款金额:'.$return_amount));
                    $objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($num_S), iconv('utf-8', 'utf-8', '消费金额:'.($amount-$return_amount)));
                    $objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($num_S), iconv('utf-8', 'utf-8', '订单金额:'.$amount));
                    $objPHPExcel->getActiveSheet()->mergeCells("A{$num_S}:B{$num_S}");
                    $objPHPExcel->getActiveSheet()->mergeCells("C{$num_S}:D{$num_S}");
                    $objPHPExcel->getActiveSheet()->mergeCells("E{$num_S}:F{$num_S}");
                    break;
                case 'channellist':
                    $num_S = $num+1;
                    $objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($num_S), iconv('utf-8', 'utf-8',
                        '支付总额:'.$tj['payment']));
                    $objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($num_S), iconv('utf-8', 'utf-8', '订单总价:'.$tj['total_all']));
                    $objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($num_S), iconv('utf-8', 'utf-8', '退款总额:'.$tj['refund_price']));
                    $objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($num_S), iconv('utf-8', 'utf-8', '佣金:'.$tj['promote_fee']));
                    $objPHPExcel->getActiveSheet(0)->setCellValue('H' . ($num_S), iconv('utf-8', 'utf-8', '订单数:'.$tj['num']));
                    $objPHPExcel->getActiveSheet(0)->setCellValue('I' . ($num_S), iconv('utf-8', 'utf-8', '已核销:'.$tj['ok']));
                    $objPHPExcel->getActiveSheet()->mergeCells("A{$num_S}:B{$num_S}");
                    $objPHPExcel->getActiveSheet()->mergeCells("C{$num_S}:D{$num_S}");
                    $objPHPExcel->getActiveSheet()->mergeCells("E{$num_S}:F{$num_S}");
                    break;
                default:
                    break;
            }


        }
//        echo '<pre>';
//        var_dump(count($data));
//        var_dump($num);die;
//        设置样式
        $objPHPExcel->getActiveSheet()->getStyle('A1:' . $end_column . ($num-1))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A1:' . $end_column . ($num-1))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

        //Rename sheet
        $objPHPExcel->getActiveSheet()->setTitle( iconv('utf-8', 'utf-8',$filename));

        //输出excel
        $objPHPExcel->setActiveSheetIndex(0);
        $timestamp = '';
        $ex = '2007';
//        var_dump(11);die;
        if($ex == '2007') { //导出excel2007文档
//            ob_end_clean();
//            header('pragma:public');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.$timestamp.'.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
            exit;
        } else {  //导出excel2003文档
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.$timestamp.'.xls"');
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
        }
    }

    /**
     * stringFromColumnIndex($i); // 从0开始
     * 将列的数字序号转成字母使用,代码如下:
     *columnIndexFromString('AA');
     * Enter description here ...
     * @param unknown_type $pColumnIndex
     */
    private function stringFromColumnIndex($pColumnIndex = 0)
    {
        //  Using a lookup cache adds a slight memory overhead, but boosts speed
        //  caching using a static within the method is faster than a class static,
        //      though it's additional memory overhead
        static $_indexCache = array();

        if (!isset($_indexCache[$pColumnIndex])) {
            // Determine column string
            if ($pColumnIndex < 26) {
                $_indexCache[$pColumnIndex] = chr(65 + $pColumnIndex);
            } elseif ($pColumnIndex < 702) {
                $_indexCache[$pColumnIndex] = chr(64 + ($pColumnIndex / 26)) . chr(65 + $pColumnIndex % 26);
            } else {
                $_indexCache[$pColumnIndex] = chr(64 + (($pColumnIndex - 26) / 676)) . chr(65 + ((($pColumnIndex - 26) % 676) / 26)) . chr(65 + $pColumnIndex % 26);
            }
        }
        return $_indexCache[$pColumnIndex];
    }


}