<?php

namespace app\index\controller;
use think\Controller;
use think\Model;
use think\Db;
use think\Session;
class Invoice extends Controller
{
    public function index()
    {
      return $this->fetch();
    }
   public function Invoicing()
   {

   }
       
}
