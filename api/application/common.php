<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use think\Route;
Route::domain('index','admin');
Route::domain('index','index');
Route::domain('index','huidu');
Route::domain('wechats.zhonghuilv.net','admin');
Route::domain('wechats.zhonghuilv.net','huidu');
/**
*查询景点下门票是否今日可定
*@param spotInfo array 景点信息
 */
function getTodayOrder($spotInfo){
    foreach($spotInfo as $k=>$v){
      $ticketInfo = db('shop_spot_ticket')->field('today')->where('spot_id',$v['spot_id'])->where('status',1)->select();
                //dump($ticketInfo);
        foreach($ticketInfo as $kk=>$vv){
            if($vv['today'] == 1){
                $spotInfo[$k]['today'] = 1;
            }else{
                $spotInfo[$k]['today'] = 0;
                }
            }
        }
        return $spotInfo;   
}
/**
 * 产品编号 共6位,不足6位前面被0
 * @param $id
 * @param $prefixId
 * @return string
 */
function get_num($id, $prefixId)
{
    $arr = array(
        'A' => '01',
        'B' => '02',
        'C' => '05',
        'D' => '03',
        'E' => '08',
        'G' => '13',
        'H' => '14',
        'I' => '15',
        'J' => '16',
        'K' => '17',
        'L' => '18',
        'M' => '19',
        'N' => '20',
        'O' => '21',
        'P' => '22',
        'Q' => '23',
        'R' => '24',
        'S' => '25',
        'T' => '26',
        'app' => '27'
    );
    $str = $id;
    return array_search($prefixId, $arr) . str_pad($str, 6, "0", STR_PAD_LEFT);
}

function request_post($url = '', $param = '') {
    if (empty($url) || empty($param)) {
        return false;
    }
    $postUrl = $url;
    $curlPost = $param;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//运行curl
    curl_close($ch);
    return $data;
}

/**
 * 获取订单状态
 * @AuthorHTL
 * @DateTime  2016-10-04T15:40:16+0800
 * @param     [type]                   $id [description]
 * @return    [type]                       [description]
 */
// -- status 0 未支付
// -- status 1 已支付
// -- status 2 处理中
// -- status 3 已取消
// -- status 4 已退款
// -- status 5 已核销
// -- status 6 已完成
function order_status($id,$member = false)
{
    $status = '';
    switch ($id) {
        case 0:
            $status =  $member ? '未付款' : '未支付';
            break;
        case 1:
            $status =  $member ? '已付款' : '已支付';
            break;
        case 2:
            $status =  $member ? '处理中' : '处理中';
            break;
        case 3:
            $status =  $member ? '已取消' : '已取消';
            break;
        case 4:
            $status =  $member ? '已退款' : '已退款';
            break;
        case 5:
            $status =  $member ? '已使用' : '已核销';
            break;
        case 6:
            $status =  $member ? '已完成' : '已完成';
            break;                  
        default:
            $status = '已过期';
            break;
    }
    return $status;
}


/**
 * 导出EXCEL表格数据
 * @AuthorHTL
 * @DateTime  2016-08-19T13:38:46+0800
 * @param     string                   $expTitle     表格标题
 * @param     array                   $expCellName  标题字段
 * @param     array                   $expTableData 表格数据
 * @return    [type]                                 [description]
 */
function exportExcel($expTitle = '数据表格导出',$expCellName,$expTableData){
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称

    $fileName = $expTitle;
    $cellNum  = count($expCellName);
    $dataNum  = count($expTableData);

    \think\Loader::import('PHPExcel', EXTEND_PATH);
    $objPHPExcel = new PHPExcel();
    $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
    
    $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle);
    for($i=0;$i<$cellNum;$i++){
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
    }
    // Miscellaneous glyphs, UTF-8
    for($i=0;$i<$dataNum;$i++){
        for($j=0;$j<$cellNum;$j++){
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
        }
    }
    //ob_end_clean();
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
    header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    exit;
}
/**
 * 导入EXCEL表格数据
 * @AuthorHTL
 * @DateTime  2016-08-19T13:39:41+0800
 * @param     [type]                   $files [description]
 * @param     [type]                   $path  [description]
 * @return    [type]                          [description]
 */
function importExcel($files,$path){
    $upload = new \Think\Upload();// 实例化上传类
    $upload->maxSize   =     10 * 1024 * 1024 ;// 设置附件上传大小
    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg','xls','xlsx');// 设置附件上传类型
    $upload->savePath  =      $path.'/'; // 设置附件上传目录
    // 上传单个文件
    $info   =   $upload->uploadOne($files['import']);
    if (!$info){
        return $upload->getError();
    }
    $action=true;
    \think\Loader::import('PHPExcel', EXTEND_PATH);
    $file_name=__ROOT__.'/upload/excel/'.$info['savepath'].$info['savename'];
    dump($file_name);
    $objReader = PHPExcel_IOFactory::createReader('Excel5');
    $objPHPExcel = $objReader->load($file_name,$encode='utf-8');
    $sheet = $objPHPExcel->getSheet(0);
    $highestRow = $sheet->getHighestRow(); // 取得总行数
    $highestColumn = $sheet->getHighestColumn(); // 取得总列数
    for($i=3;$i<=$highestRow;$i++){
        $data['id']= $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
        $data['name']= $objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
        $data['tel']= $objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
        $data['sex']= $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
        $data['groups']= $objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue();
        $data['subject']= $objPHPExcel->getActiveSheet()->getCell("F".$i)->getValue();
        $data['xq_name']= $objPHPExcel->getActiveSheet()->getCell("G".$i)->getValue();
        $data['address']= $objPHPExcel->getActiveSheet()->getCell("G".$i)->getValue();
        $data['add_time']= $objPHPExcel->getActiveSheet()->getCell("G".$i)->getValue();
        $b=M('userinfo')->add($data);
        if (!$b){
            $action=false;
            break;
        }       
    }
    return $action;
}

//门票订单状态
function get_status($status) {
    $arr = array(
      0=>'待付款',
      1=>'待出行',
      2=>'处理中',
      3=>'已取消',
      4=>'已退款',
      5=>'已完成',
      6=>'部分退款',
    );
    return $arr[$status];
}

function isCreditNo($vStr){
    $vCity = array(
        '11','12','13','14','15','21','22',
        '23','31','32','33','34','35','36',
        '37','41','42','43','44','45','46',
        '50','51','52','53','54','61','62',
        '63','64','65','71','81','82','91'
    );
    if (!preg_match('/^([\d]{17}[X\d]|[\d]{15})$/', $vStr)) return false;
    if (!in_array(substr($vStr, 0, 2), $vCity)) return false;
    $vStr = preg_replace('/[X]$/i', 'a', $vStr);
    $vLength = strlen($vStr);
    if ($vLength == 18) {
        $vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
    } else {
        $vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
    }
    if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
    if ($vLength == 18) {
        $vSum = 0;
        for ($i = 17 ; $i >= 0 ; $i--) {
            $vSubStr = substr($vStr, 17 - $i, 1);
            $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
        }
        if($vSum % 11 != 1) return false;
    }
    return true;
}

/**
 * 字符串截取，支持中文和其他编码
 * static
 * access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * return string
 */
function msubstr($str,$length, $suffix=true, $start=0, $charset="utf-8") {
    if(function_exists("mb_substr")){
        $slice = mb_substr($str, $start, $length, $charset);
        $strlen = mb_strlen($str,$charset);
    }elseif(function_exists('iconv_substr')){
        $slice = iconv_substr($str,$start,$length,$charset);
        $strlen = iconv_strlen($str,$charset);
    }else{
        $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
        $strlen = count($match[0]);
    }
    if($suffix && $strlen>$length)$slice.='...';
    return $slice;
}