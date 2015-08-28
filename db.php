<?php
/**
* 数据库操作类
* 
* @package hu60t
* @version 0.1.3
* @author 老虎会游泳 <hu60.cn@gmail.com>
* @copyright LGPLv3
* 
* 用于快速建立一个配置好了的PDO数据库对象，减少打字。
* 并且用它还可以实现Mysql/SQLite兼容
* 而且它还支持读写分离

* 0.1.2
* 现在不仅如此。
* 目前的它还能够让PDO的预处理功能变得万分易用！
* 它已不再是一个数据库连接类，而演变为数据库操作类。
* 
* <code>
* $db=new $db;
* $rs=$db->select('uid', 'user', 'WHERE name=? AND pass=?', $name, $pass);
* var_dump($rs->fetch());
* $db->insert('user', 'name,pass', $name, $pass);
* *上一行代码还可以这样表示：
* $db->insert('user', array('name'=>$name,'pass'=>$pass));
* </code>
* 
* 觉得方便吗？快来使用吧！
* 
*/
class db
{
static $TYPE=DB_TYPE;
static $EMULATE_PREPARES = false;
static $FILE_PATH=DB_PATH;
static $PCONNECT=DB_PCONNECT;
static $HOST=DB_HOST;
static $PORT=DB_PORT;
static $HOST_RO=DB_HOSTRO;
static $PORT_RO=DB_PORTRO;
static $NAME=DB_NAME;
static $USER=DB_USER;
static $PASS=DB_PASS;
static $A=DB_A;
const ass=PDO::FETCH_ASSOC;
const num=PDO::FETCH_NUM;
const both=PDO::FETCH_BOTH;
const obj=PDO::FETCH_OBJ;
const bound=PDO::FETCH_BOUND;
const lazy=PDO::FETCH_LAZY;
const col=PDO::FETCH_COLUMN;
static $DEFAULT_FETCH_MODE=PDO::FETCH_ASSOC;
/**
* 默认的PDO错误处理方式
* 
* 可选的常量有：
* PDO::ERRMODE_SILENT
*    只设置错误代码
* PDO::ERRMODE_WARNING
*    除了设置错误代码以外， PDO 还将发出一条传统的 E_WARNING 消息。
* PDO::ERRMODE_EXCEPTION
*    除了设置错误代码以外， PDO 还将抛出一个 PDOException，并设置其属性，以反映错误代码和错误信息。
*/
static $DEFAULT_ERRMODE=PDO::ERRMODE_EXCEPTION;

/*SQLite选项*/
  
/**
* 强制磁盘同步
* 
* 可选值：
* FULL
*    完全磁盘同步。断电或死机不会损坏数据库，但是很慢（很多时间用在等待磁盘同步）
* NORMAL
*    普通。大部分情况下断电或死机不会损坏数据库，比OFF慢，
* OFF
*    不强制磁盘同步，由系统把更改写到文件。断电或死机后很容易损坏数据库，但是插入或更新速度比FULL提升50倍啊！
*/
static $SQLITE_SYNC='OFF';

/*MYSQL选项*/
  
/**
* 默认字符集
*/
static $DEFAULT_CHARSET='utf8';
/*以下是类内部使用的属性*/
protected $pdo;
protected $rs;
/**
* 自动添加表名前缀
*/
public $auto_db_a=true;
/*统计查询数*/
public $querynum=0;
protected static $db;
protected static $db_ro;
/**
* 返回PDO连接对象
*/
static function conn($read_only=false) {
 if(self::$TYPE=='sqlite'){
  $db=&self::$db;
  if($db) return $db;
  $db=new PDO(self::$TYPE.':'.self::$FILE_PATH);
  $db->exec('PRAGMA synchronous='.self::$SQLITE_SYNC);
 } else {
if(($read_only || self::$HOST=='') && self::$HOST_RO!='')
 {$db=&self::$db_ro;
 $db_host=self::$HOST_RO;
 $db_port=self::$PORT_RO;}
elseif(self::$HOST!='')
 {$db=&self::$db;
 $db_host=self::$HOST;
 $db_port=self::$PORT;}
else throw new Exception('数据库配置错误：self::$HOST和self::$HOST_RO都为空！');
if($db)
 return $db;
if($db_port!='') $port=';port='.$db_port;
else $port='';
$opt = array(
  PDO::ATTR_PERSISTENT=>self::$PCONNECT,
  ); 
$db=new PDO(self::$TYPE.':dbname='.self::$NAME.';host='.$db_host.$port,self::$USER,self::$PASS,$opt);
$db->exec('SET NAMES '.self::$DEFAULT_CHARSET); //设置默认编码
}
$db->setAttribute(PDO::ATTR_ERRMODE, self::$DEFAULT_ERRMODE); //设置以报错形式
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, self::$DEFAULT_FETCH_MODE); //设置fetch时返回数据形式
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, self::$EMULATE_PREPARES); //设置是否启用模拟预处理，强烈建议false
return $db;
}
/**
* 为表名加前缀
*/
static function a($name) {
$name=explode(',',$name);
foreach($name as &$n) {
$n=trim($n);
if($n[0]==='`' or strpos($n,'.')!==false) continue;
$n='`'.self::$A.$n.'`';
 }
return implode(',',$name);
}
/*以下是DB类的非静态部分*/
  
/**
* 取得PDO对象
*/
public function pdo($read_only=false) {
return $this->pdo!==NULL ? $this->pdo : self::conn($read_only);
}
/**
* 生成PDO预处理参数数组
*/
function pdoarray($exlen,$data) {
$array=array();
array_splice($data,0,$exlen);
foreach($data as $d) {
if(!is_array($d)) $array[]=$d;
else $array=array_merge($array,$d);
 }

return $array;
}
/**
* 初始化类
*/
public function __construct($pdo_conn_str=NULL,$user=NULL,$pass=NULL) {
 if($pdo_conn_str!==NULL) {
$db=&$this->pdo;
$db=new PDO($pdo_conn_str,$user,$pass);
$type=strtolower(substr($pdo_conn_str,0,strpos($pdo_conn_str,':')));
if($type==='sqlite') {
$db->exec('PRAGMA synchronous='.self::$SQLITE_SYNC);
  } else {
$db->exec('SET NAMES '.self::$DEFAULT_CHARSET);
  }
$db->setAttribute(PDO::ATTR_ERRMODE, self::$DEFAULT_ERRMODE);
$db->setAttribute(PDO:: ATTR_DEFAULT_FETCH_MODE, self::$DEFAULT_FETCH_MODE);
return $db;
 }
return true;
}
/*
* 执行SQL（内部使用）
*/
protected function sqlexec($read_only,$sql,$data) {
$db=$this->pdo($read_only);
$rs=&$this->rs;
$rs=$db->prepare($sql);
$rs->execute($data);
$this->querynum++;
return $rs;
}
/*
* 自动加表名前缀（类内部使用）
*/
protected function auto_a($table) {
if($this->auto_db_a) $table=self::a($table);
return $table;
}
/**
* 查询
*/
public function select($name,$table,$cond='') {
$table=$this->auto_a($table);
$sql="SELECT $name FROM $table $cond";
$data=func_get_args();
$data=$this->pdoarray(3,$data);
return $this->sqlexec(true,$sql,$data);
}
/**
* 更新数据
* 对原来的hu60t:db类进行修改
* 第二项的值为array('key'=>'value',...)，这样更加方便
* 当然，你可以使用原来的方法~
*/
public function update($table,$data,$cond='') {
/*
*以下代码兼容原db类
*/
if(!is_array($data))
{$table=$this->auto_a($table);
$sql="UPDATE $table SET $data";
$data=func_get_args();
$data=$this->pdoarray(2,$data);
return $this->sqlexec(false,$sql,$data);
}
/*
*以下为新版
*/
$sql = '';
foreach ($data as $k => $v) {
$sql && $sql .= ',';
$value[]=$v;
$sql .= '`'.$k.'`=?';
}
$table=$this->auto_a($table);
$sql="UPDATE $table SET $sql $cond";
$data=func_get_args();
$data=$this->pdoarray(3,$data);
$value=array_merge($value,$data);
return $this->sqlexec(false,$sql,$value);
}
/**
* 删除数据
*/
public function delete($table,$cond='') {
$table=$this->auto_a($table);
$sql="DELETE FROM $table $cond";
$data=func_get_args();
$data=$this->pdoarray(2,$data);
return $this->sqlexec(false,$sql,$data);
}
/**
* 插入数据
* 对原来的hu60t:db类进行修改
* 第二项的值为array('key'=>'value',...)，这样更加方便
* 当然，你可以使用原来的方法
*/
public function insert($table,$data){
/*
*以下代码兼容原db类
*/
if(!is_array($data))
{$table=explode('(',$table);
$table[0]=$this->auto_a($table[0]);
if(strpos($data,'(')===FALSE) {
if($table[1]!='') {
$data="VALUES($value)";
} else {
$table[1]="$data)";
$data='VALUES('.str_repeat('?,',substr_count($data,',')).'?)';
}
}
$sql="INSERT INTO $table[0]($table[1] $data";
$data=func_get_args();
$data=$this->pdoarray(2,$data);
return $this->sqlexec(false,$sql,$data);
}/*
*以下为新版
*/
$sql1 = $sql2 = '';
foreach ($data as $k => $v) {
if ( $sql1 ){
$sql1 .= ',';
$sql2 .= ',';
}
$sql1 .= "`$k`";
$value[]=$v;
$sql2 .= '?';
}
$table=$this->auto_a($table);
$sql="INSERT INTO $table ($sql1) VALUES ($sql2)";
return $this->sqlexec(false,$sql,$value);
}
public function query($sql) {
if(preg_match('/^\s*SELECT\s/is',$sql)) $read_only=true;
else $read_only=false;
$data=func_get_args();
$data=$this->pdoarray(1,$data);
return $this->sqlexec($read_only,$sql,$data);
}

/**
* 执行SQL并返回影响行数
* 
* 该方法不支持自动添加表名前缀，需要自行添加
*/
public function exec($sql) {
if(preg_match('/^\s*SELECT\s/is',$sql)) $read_only=true;
else $read_only=false;
return $this->pdo($read_only)->exec($sql);
}
/**
* 预处理SQL并返回结果集对象
* 
* 该方法不支持自动添加表名前缀，需要自行添加
*/
public function prepare($sql) {
if(preg_match('/^\s*SELECT\s/is',$sql)) $read_only=true;
else $read_only=false;
return $this->pdo($read_only)->prepare($sql);
}
/**
* 返回最后一次插入的id
*/
public function lastInsertId() {
    return $this->pdo()->lastInsertId();
}
/*db类结束*/
}
