<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/6/6
 * Time: 15:49
 */
namespace app\admin\model;

use think\Db;
use think\Model;

class AchievementModel extends PublicyangModel
{
    public function __construct(){
        parent::__construct();
    }

    protected $table = 'too_member_cashnum_examine';




}