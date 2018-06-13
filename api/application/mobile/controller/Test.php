<?php
namespace app\mobile\controller;
use think\Controller;

class Test extends Controller
{
	public function test()
	{
		echo authcode('edb0spx[c]uUKbS82VREEdlSWuCECQx[a]fX4b[c]NqrW[a]','DECODE');
	}

}