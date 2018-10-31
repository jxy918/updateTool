#!/usr/bin/env php
<?php
header("Content-type: text/html; charset=utf-8");
error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);
//define('BASE_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

//内外网SVN配置路径
$svn_path_conf = array(
    //斗地主，根据项目来配置对应的路径，source_path，target_path 指的是客户端svn目录路径，例如：需要更新测试环境， 那么，source_path指的是开发svn客户端目录，target_path指的是测试svn客户端目录
    'ddz'=>array(
        'source_path' => 'xxxx',
        'target_path' => 'xxxx'
    ),
);

//内网svn账号密码
$source_auth = '--no-auth-cache --username svn --password svn';
//外网svn账号密码
$target_auth = '';

//读取文件更新列表
$file_path = "file.txt";
$list = array();
if(file_exists($file_path)){
    $str = file_get_contents($file_path);//将整个文件内容读入到一个字符串中
    $list = explode("\r\n", trim($str));
	$list = array_filter($list);
}
if(empty($list)) {
    exit("列表为空!\n");
}

//执行命令
$cmd = isset($argv[1]) ? $argv[1] : '--help';
//执行命令参数， 根据参数去找svn对应的路径
$param = isset($argv[2]) ? trim($argv[2]) : 0;
$conf = $svn_path_conf[$param];

if($cmd != '--help' && !isset($conf)) {
    exit("更新参数不能为空！\n");
}
$source_path = $conf['source_path'];
$target_path = $conf['target_path'];

if($cmd != '--help' && (empty($source_path) || empty($target_path))) {
    exit("SVN更新路径有误！\n");
}

switch($cmd) {
    case 'update':
        update($source_path, $target_path, $list, $source_auth, $target_auth);
        break;
    case 'rollback':
        rollback($target_path, $list, $target_auth);
        break;
    case 'diff':
        diff($target_path, $list, $target_auth);
        break;
	case 'test':
        test($source_path, $target_path, $list, $source_auth, $target_auth);
        break;
    default:
        help();
        break;
}

function showList($list, $cmd = '') {
    showLog("\033[1;33m[更新列表:{$cmd}]\033[0m\n" . print_r($list, true)."\n");
}

/**
 * 更新
 */
function update($source_path, $target_path, $list, $source_auth = '', $target_auth = '')
{
	showList($list,'update');
	
	//执行svn目录，同步以下代码
	shell_exec("cd {$source_path} && svn cleanup");
	$output = "\033[1;33m[内网SVN更新]\033[0m\n";
	foreach($list as $k=>$v) {
		$path = $source_path . $v;
		//if(file_exists($path) || is_dir($path)) {
            $output .= ($k+1) . ', ' .$path . "   ";
            $output .= shell_exec("cd {$source_path} && svn update {$path} {$source_auth}");
       // }
	}
	showLog($output);

	//循环复制代码更新
	$output = "\033[1;33m[复制内网SVN文件到外网SVN路径下]\033[0m\n";
	foreach($list as $ke=>$val) {
		$path = $source_path . $val;
		$t_path = $target_path.$val;
		$t_path = substr($t_path, 0, strrpos($t_path, "/") + 1);
		$output .= ($ke+1) . ", \033[0;32m原路径:\033[0m{$path}\033[0;32m目标路径:\033[0m{$t_path}\n";
		if(!is_dir($t_path)) {
			mkdir($t_path, 0777, true);
		}
		if(is_file($path) || is_dir($path)) {
            $output .= shell_exec("cp -r -f {$path} {$t_path}");
        }
	}
	showLog($output);

	//提交svn更新代码
	$str = shell_exec("cd {$target_path}  && svn status | grep '?' | awk '{print $2}'");
	$arr = explode("\n", $str);
	$arr = array_filter($arr);
	$add_list = array();
	foreach($list as $va) {
		if(in_array($va, $arr)) {
			$add_list[] = $va;
		}
	}
	$output = "\033[1;33m[外网SVN执行新增文件]\033[0m\n\n";
	foreach($add_list as $kel => $v) {
		$output .= ($kel + 1) . ", $v\n";
		echo $target_path.$v;
		if(is_dir($target_path.$v)) {
		    echo "svn add --no-ignore --force {$target_path}{$v}";
            $output .= shell_exec("cd {$target_path} && svn add --no-ignore --force {$target_path}{$v}");
        } else {
            $output .= shell_exec("cd {$target_path} && svn add {$target_path}{$v}");
        }
	}
	showLog($output);

	//外网svn提交
	$output = "\033[1;33m[外网SVN执行提交更新文件]\033[0m\n\n";
    $output .= "\033[0;32m更新文件状态:\e[0m\n";
	$output .= shell_exec("cd {$target_path} && svn status");
	$output .= "\n";
	$m = print_r($list, true);
    $output .= "\033[0;32m更新文件结果:\e[0m\n";
	$output .= shell_exec("cd  {$target_path} && svn commit -m '{$m}'");
	showLog($output);
}


/**
 * 非svn环境更新,此命令更新测试环境
 */
function test($source_path, $target_path, $list, $source_auth = '', $target_auth = '')
{
	showList($list,'test');
	
	//执行svn目录，同步以下代码
	shell_exec("cd {$source_path} && svn cleanup");
	$output = "\033[1;33m[内网SVN更新]\033[0m\n";
	foreach($list as $k=>$v) {
		$path = $source_path . $v;
		//if(file_exists($path) || is_dir($path)) {
            $output .= ($k+1) . ', ' .$path . "   ";
            $output .= shell_exec("cd {$source_path} && svn update {$path} {$source_auth}");
       // }
	}
	showLog($output);

	//循环复制代码更新
	$output = "\033[1;33m[复制内网SVN文件到测试路径下]\033[0m\n";
	foreach($list as $ke=>$val) {
		$path = $source_path . $val;
		$t_path = $target_path.$val;
		$t_path = substr($t_path, 0, strrpos($t_path, "/") + 1);
		$output .= ($ke+1) . ", \033[0;32m原路径:\033[0m{$path}\033[0;32m目标路径:\033[0m{$t_path}\n";
		if(!is_dir($t_path)) {
			mkdir($t_path, 0777, true);
		}
		if(is_file($path) || is_dir($path)) {
            $output .= shell_exec("cp -r -f {$path} {$t_path}");
        }
	}
	showLog($output);
}

/**uth}
 * 回滚
 */
function rollback($target_path, $list, $target_auth = '')
{
    showList($list, 'rollback');
    shell_exec("cd {$target_path} && svn cleanup");
    $output = "\033[1;33m[回滚操作]\033[0m\n";
    foreach ($list as $k=> $v) {
        $output .= ($k+1) .', ';
        $version = shell_exec("cd {$target_path} && svn log {$v} {$target_auth} |grep '|' | awk '{print $1}' | head -n 2 |xargs | sed 's/ /:'/g | sed 's/r//'g");
        $version = trim($version);	
        $output .= "\e[0;32m回滚文件:\e[0m{$target_path}{$v}, 版本:{$version}\n";
		if(strpos($version, ':') === false) {
			//新增文件文件回滚,删除
			$del = shell_exec("cd {$target_path} && svn del {$target_path}{$v}");
			$output .= "\e[1;34m回滚删除:\e[0m  \n{$del}\n";
		} else {
			$diff = shell_exec("cd {$target_path} && svn diff -r {$version} {$target_path}{$v} {$target_auth}");
			$output .= "\e[1;34m差异比对:\e[0m  \n{$diff}\n";
			$merge = shell_exec("cd {$target_path} && svn merge -r {$version} {$target_path}{$v} {$target_auth}");
			$output .= "\e[1;34m回滚操作:\e[0m{$merge}\n";
		}
    }
    $m = print_r($list, true);
    $commit = shell_exec("cd {$target_path} && svn commit -m '回滚{$m}'");
    $output .= "\033[1;33m[提交SVN结果]\033[0m\n{$commit}";
	showLog($output);
}

/**
 * 回滚查看差异
 */
function diff($target_path, $list, $target_auth = '')
{
    showList($list, 'diff');
    shell_exec("cd {$target_path} && svn cleanup");
    $output = "\033[1;33m[回滚版本差异查看]\033[0m\n";
    foreach ($list as $k=>$v) {
        $output .= ($k+1) . ', ';
        $version = shell_exec("cd {$target_path} && svn log {$v} {$target_auth} |grep '|' | awk '{print $1}' | head -n 2 |xargs | sed 's/ /:'/g | sed 's/r//'g");
        $version = trim($version);
		if(strpos($version, ':') === false) {
			$output .= "\033[0;32m新增文件:\e[0m{$target_path}{$v}, 版本:{$version}\n";
		} else {
			$output .= "\033[0;32m差异文件:\e[0m{$target_path}{$v}, 版本:{$version}\n";
			$diff = shell_exec("cd {$target_path} && svn diff -r {$version} {$target_path}{$v} {$target_auth}");
			$output .= "\033[1;34m差异比对:\e[0m  \n{$diff}\n";
		}
    }
   	showLog($output);
}

/**
 * 帮助说明
 */
function help() {
    $help = "\033[1;33mDescription:\033[0m\n";
    $help .= "  此命令管理SVN从内网更新到外网工具\n\n";
    $help .= "\033[1;33mUsage:\033[0m\n";
    $help .= "  php updateTools.php {command} [arguments] [options]\n\n";
    $help .= "\033[1;33mCommands:\e[0m\n";
    $help .= "  \033[0;32mupdate\e[0m         更新\n";
    $help .= "  \033[0;32mdiff\e[0m           比较当前版本和上一版本差异\n";
    $help .= "  \033[0;32mrollback\e[0m       回滚当前版本到上一版本\n";
	$help .= "  \033[0;32mtest\e[0m		 更新非svn环境，从内网svn上拷贝对应文件到测试目录\n";

    $help .= "\033[1;33mArguments:\e[0m\n";
    $help .= "  \033[0;32mddz\e[0m          斗地主\n";

    $help .= "\033[1;33mOptions:\e[0m\n";
    $help .= "  \033[0;32m--help\e[0m         查看帮助\n";
    echo $help."\n";
}

function showLog($content) {
	echo $content."\n";
	$filename = './log/update_' . date("Y-m-d") . '.log';
	file_put_contents($filename,  $content."\n", FILE_APPEND);
}