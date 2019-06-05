<?php
//自动化管理crontab工具类
class autoCron
{
	//规则定义和解释
	static $rule = array(
		"rule"			=>"CHR32		@定时策略,符合Linux规范 即 [*] * * * * * 每分每小时每天每月每周,扩展到每秒在最前",
		"classid"		=>"CHR56		@模块名",
		"cli"			=>"CHR56		@脚本名",
		"action"		=>"CHR56		@方法名",
		"argv"			=>"CHR56		@参数",
		"span"			=>"INT*			@有效时间,秒",
		"activated"		=>"CHR2* 		@是否激活 invalid:失效 activated:激活
										准确含义是是否使用框架定时脚本工具接管,当选择激活时,后台设置的定时策略生效并起作用,但不干涉crontab直接加入的定时脚本)",
	);
	//下面是使用时的数据格式
	/*
		[
			{"rule":"* * * * * *","classid":"watermelon","cli":"newspu","action":"spulashou","argv":"-b zest","span":"0","activated":"activated"},
			{"rule":"* * * * * *","classid":"watermelon","cli":"newspu","action":"spulashou","argv":"-b ca","span":"0","activated":"activated"}
		]
	*/
	//下面是配置文件中的数据格式
	/*
	#依次是
	#{定时策略},{模块名},{脚本名},{方法名},{参数},{有效时间},{是否激活}
	#* * * * * *,watermelon,newspu,spulashou,-b zest,0,activated
	*/
	public $config = 'cron.conf';
	public $config_head = <<<CONF
#orange框架自动化脚本配置文件
#{定时策略},{模块名},{脚本名},{方法名},{参数},{有效时间},{是否激活}
#* * * * * *,watermelon,newspu,spulashou,-b zest,0,activated,2019-06-04 17:08:00
#请在下面写入您的配置

CONF;
	public function __construct(){
		$this->config = 'cron.conf';
		if(!file_exists($this->config)){
			$handle = fopen($this->config, "w");
			fwrite($handle,$this->config_head);
			fclose($handle);
		}
	}
	//入口方法
	public function auto(){
		while (1) {
			//echo date('Y-m-d H:i:s').PHP_EOL;
			$rules = $this->getRules();
			//记录需要执行的列表
			foreach ($rules as $k => $r) {
				//判断时间条件
				if($this->rule($r['rule'],strtotime($r['lasttime']))){
					$this->log($r);
					//执行
					$this->execCron($r['classid'],$r['cli'],$r['action'],$r['argv']);
				}
			}
			//exit;
			sleep(1);
		}
	}
	private function log($rule){
		error_log(date('Y-m-d H:i:s') . '|' . json_encode($rule).PHP_EOL, 3, "autocron.log");
	}
	//执行脚本
	private function execCron($classid,$cli,$action,$argv){
		//先记录当前时间
		$lasttime = time();
		//拼接执行命令
		$shell = PacaConfig::$phpaddr . ' ' . PacaConfig::$webaddr . 'cron.php -m ' . $classid . ' -c ' . $cli . ' -s ' . $action . ' ' . $argv . ' > cronexec.out.log 2>&1 &';
		//执行
		exec($shell);
		//记录最后执行时间
		$this->setRuleLastTime($classid,$cli,$action,$argv,$lasttime);
	}
	//检查是否达到执行规则
	private function rule($rule,$lasttime){
		$time_now = time();
		//$time_now = strtotime('2019-06-05 02:25:00');
		//使用正则匹配是否合法
		$patt = '/^([00-60]+|\*|\*\/[0-9]*[1-9]+[0-9]*) ([0-9]+|\*|\*\/[0-9]*[1-9]+[0-9]*) ([0-9]+|\*|\*\/[0-9]*[1-9]+[0-9]*) ([0-9]+|\*|\*\/[0-9]*[1-9]+[0-9]*) ([0-9]+|\*|\*\/[0-9]*[1-9]+[0-9]*) ([0-9]+|\*|\*\/[0-9]*[1-9]+[0-9]*) ([0-9]+|\*|\*\/[0-9]*[1-9]+[0-9]*)$/';
		//对于精确时间,所取时间需要的字符
		//        秒     分      时     日     月     年     周几
		$exact = [1=>'s',2=>'i',3=>'H',4=>'d',5=>'m',6=>'Y',7=>'w'];
		//对于重复执行的,所取时间需要的字符
		//        秒              分          时        日        月特殊     年        周几
		$repeat = [1=>'second',2=>'minute',3=>'hour',4=>'day',5=>'month',6=>'year',7=>'week'];
		//时间区间,即当前时间时所受约束的父级时间单位,用于模拟频率钟计算
		$clocks = [
			//这个数组是记录每一个级别的时间,对应操作的时间的表达式
			//  取时间区间   时间区间的表达式  本级别时间表达式
			1=>['+1 minute','Y-m-d H:i:00','Y-m-d H:i:s'],
			2=>['+1 hour','Y-m-d H:00:00','Y-m-d H:i:00'],
			3=>['+1 day','Y-m-d 00:00:00','Y-m-d H:00:00'],
			4=>['+1 month','Y-m-1 00:00:00','Y-m-d 00:00:00'],
			5=>['+1 year','Y-1-1 00:00:00','Y-m-1 00:00:00'],
			6=>[],
			7=>['next Monday','Y-m-d 00:00:00','Y-m-d 00:00:00']];
		if(preg_match_all($patt,$rule,$result)){
			for ($i=1; $i <=7 ; $i++) { 
				//情况一 判断是否是指定时间
				if (preg_match_all('/^([00-60]+)$/',$result[$i][0],$result_sun)) {
					//抓取所指定的时间
					$num = intval($result_sun[1][0]);
					//var_dump($num , date($exact[$i],$time_now),$num != date($exact[$i],$time_now));exit;
					//指定时间判断简单,不相等就不满足
					if($num != date($exact[$i],$time_now)){
						return false;
					//满足条件,进行下一次循环
					}else{
						continue;
					}

				//情况二  是否是指定频率,然后获取步数
				}elseif(preg_match_all('/^\*\/([0-9]*[1-9]+[0-9]*)$/',$result[$i][0],$result_sun)){
					//获取步数
					$step = intval($result_sun[1][0]);
				}else{
					//否则就是*,步数为1
					$step = 1;
				}
				//判断是否需要步数钟
				if(isset($clocks[$i]) && $clocks[$i]){
					//上面的正则保证步数是大于1的正整数,这里就不在判断
					//检查跨步之后是否超过当前区间
					//跨步之后时间,这里注意的是,需要格式成本级别的时间格式
					$step_time = strtotime(date($clocks[$i][2],$lasttime). ' +'.$step.' '.$repeat[$i]);
					//当前时间也要进行转换
					$time_now_change = strtotime(date($clocks[$i][2],$time_now));
					//当前的时间区间是
					//夸越父刻度,然后子刻度时间重置为0(时分秒)或1(月日Monday)
					$section_time = strtotime(date($clocks[$i][1],strtotime(date('Y-m-d H:i:s',$lasttime).' '.$clocks[$i][0])));
					//以上三次格式化是步数钟实现的关键

					//if($i == 2){
					//var_dump($i);
					//var_dump(date('Y-m-d H:i:s',$step_time));
					//var_dump(date('Y-m-d H:i:s',$section_time));
					//var_dump(date('Y-m-d H:i:s',$time_now_change));
					//}
					//并未夸度
					if($step_time < $section_time){
						//如果上次执行时间和当前时间在一个可执行维度内,决定权在我,可以立即判断,不再进行父级循环
						//当前时间小于步数,记录
						if($time_now_change<$step_time){
							return false;
						//行使通过权力
						}else{
							return true;
						}
					//否则交给下一级(一般是父级)
					}else{
						continue;
					}
				//否则直接取值相减,一般是年份
				}else{
					//年份的判断要放到最后
					$d_value = date($exact[$i],$time_now) - date($exact[$i],$lasttime);
					//差值小于步数
					if($d_value<$step){
						$end = false;
					//否则继续循环
					}else{
						$end = true;
						continue;
					}
				}
			}
		}else{
			//定时表达非法
			return false;
		}
		//如果走到这里,说明无人否决,也无人判定通过,检查是否有终判(年份判断)
		if(isset($end)) return $end;
		//无阻拦,认为是通过
		return true;
	}
	//保存规则
	private function saveRule($rule=[]){
		//加上头部
		$str = $this->config_head;
		//把数组组成文件内容
		foreach ($rule as $r) {
			$str .= join(',',[$r['rule'],$r['classid'],$r['cli'],$r['action'],$r['argv'],$r['span'],$r['activated'],$r['lasttime']]).PHP_EOL;
		}
		//覆盖写入
		$handle = fopen($this->config, "w");
		fwrite($handle,$str);
		fclose($handle);
		return true;
	}
	//清空规则
	private function clearRule(){
		//覆盖写入
		$handle = fopen($this->config, "w");
		fwrite($handle,$this->config_head);
		fclose($handle);
		return true;
	}
	//记录最后执行时间
	private function setRuleLastTime($classid,$cli,$action,$argv='',$lasttime=0){
		$rule = $this->getRules();
		foreach ($rule as $key => $item) {
			if($item['classid'] == $classid && $item['cli'] == $cli && $item['action'] == $action && $item['argv'] == $argv){
				$rule[$key]['lasttime'] = date('Y-m-d H:i:s',$lasttime);
				return $this->saveRule($rule);
			}
		}
	}
	//查询规则
	public function getRules(){
		$rule = [];
		//打开配置文件
		$handle = fopen($this->config, 'r');
		//取所有的非空,非注释行
		while($line = fgets($handle)){
			//不是空行且非注释
			if(trim($line) && !preg_match('/^#/',trim($line))){
				//切割行
				$rule_line = explode(',', trim($line));
				//保存成友好的数据格式
				$rule[] = [
					"rule"		=> $rule_line[0],
					"classid"	=> $rule_line[1],
					"cli"		=> $rule_line[2],
					"action"	=> $rule_line[3],
					"argv"		=> $rule_line[4],
					"span"		=> $rule_line[5],
					"activated"	=> $rule_line[6],
					"lasttime"	=> $rule_line[7],
				];
			}
		}
		fclose($handle);
		//返回结果
		return $rule;
	}
	//设置规则
	public function setRule($execrule,$classid,$cli,$action,$argv='',$span=null){
		$rule = $this->getRules();
		foreach ($rule as $key => $item) {
			if($item['classid'] == $classid && $item['cli'] == $cli && $item['action'] == $action && $item['argv'] == $argv){
				$rule[$key]['rule'] = $execrule;
				isset($span) && $rule[$key]['span'] = $span;
				return $this->saveRule($rule);
			}
		}
		$rule[]=[
			'rule'=>$execrule,
			'classid'=>$classid,
			'cli'=>$cli,
			'action'=>$action,
			'argv'=>$argv,
			'span'=>isset($span) ? $span : '0',
			'activated'=>'activated',
			'lasttime'=>'',
		];
		return $this->saveRule($rule);
	}
	public function getRule($classid,$cli,$action,$argv=''){
		$rule = $this->getRules();
		foreach ($rule as $key => $item) {
			if($item['classid'] == $classid && $item['cli'] == $cli && $item['action'] == $action && $item['argv'] == $argv){
				return $item;
			}
		}
		return [];
	}
}
?>