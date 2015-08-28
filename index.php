<?php
error_reporting(0);

function sizecount($size)
{
$kb = 1024;
$mb = 1024 * $kb;
$gb = 1024 * $mb;
$tb = 1024 * $gb;
$pb = 1024 * $tb;
if($size < $kb){
return $size." B";
}elseif($size < $mb){ 
return round($size/$kb,2)."KB";
}elseif($size < $gb){ 
return round($size/$mb,2)."MB";
}elseif($size < $tb){
return round($size/$gb,2)."GB";
}elseif($size < $pb){
return round($size/$tb,2)."TB";
}else{
return round($size/$pb,2)."PB";
}
}


include('db.php');
define('DB_PCONNECT',false);
define('DB_USER','');
define('DB_HOST','');
define('DB_NAME','');
define('DB_PASS','');
define('DB_PORT','');//留空
define('DB_HOSTRO','');//留空
define('DB_PORTRO','');//留空
define('DB_TYPE','sqlite');
define('DB_PATH','db3.db');
define('DB_A','');
$DB=new db;

//判断数据库文件是否存在
if (!file_exists("db3.db"))
{
$sql='
CREATE TABLE "opera"(
[id] integer PRIMARY KEY AUTOINCREMENT
,[cishu] text
,[daxiao] text
);
';
$DB->exec($sql);
$DB->insert('opera',array('cishu'=>'0','daxiao'=>'0'));
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
if (function_exists("curl_init")) {
$row=$DB->select('*','opera')->fetchALL();
$row=$row['0'];
$cs=$row['cishu'];
$dx=sizecount($row['daxiao']);
$ym=$_SERVER['SERVER_NAME'].$_SERVER["PHP_SELF"];
$text='
<!DOCTYPE HTML>
<html>
<body>
<h1>Opera Mini Mirror Server</h1>
<p>欢迎使用Opera Mini的镜像服务器，您只需要有一个支持变更服务器的Opera Mini就可以使用本程序！</p>
<p>
我们的服务器地址是：
<br />
<input type="text" value="http://'.$ym.'" size="30" /><br>
<input type="text" value="http://s.q18idc.com/" size="30" /><br>
<input type="text" value="http://s1.q18idc.com/" size="30" /><br>
<input type="text" value="http://s2.q18idc.com/" size="30" /><br>
<br />
在使用时，建议您在这个地址后面增添一个英文问号“?”。
<br/><br/>
您也可以直接下载一个已经修改好的Opera Mini<br>
Android版：<br>
<a href="./opm.q18idc.com.3.apk">点击下载7.6.4版 (建议Android 4.4及以上或DPI 350以上用户使用)</a><br>
<a href="./opm.q18idc.com.2.apk">点击下载7.5.1版 (建议Android 4.3以下，DPI 350以下用户或使用)</a><br>
<a href="./opm.q18idc.com.apk">点击下载7.6版</a><br>
Java版：<br>
<a href="./opm.q18idc.com.jar">点击下载</a><br>
</p>
<p>
访问Facebook截图<br>
<a href="java.png">Java版截图</a> <a href="android.png">Android版截图</a>
</p>
<p>
温馨提示：<br>
<ul>
<li>中国移动用户必须使用cmnet接入点，否则将无法连接到英特网。</li>
</ul>
</p>
<p>
使用须知：
<br />
<ul>
<li>本服务是非营利性质的，禁止用于商业用途！</li>
<li>我们会尽量维持本服务的稳定可靠，但是我们仍然有权单方面的终止本服务。</li>
<li>从原则上讲，您发送的数据是加密的，我们保证不查看，不解密用户在本服务器上发送和接收的数据。</li>
<li>请不要将本服务用于恶意或者非法用途，您将承担您使用本服务带来的所有后果。</li>
<li>请尽量不要用本服务浏览一些包含大量大型图片的网站页面，或者下载较大的文件，这会对本服务带来较大的压力。这取决于您的自觉，正如上面所说，我们不会查看您的数据，也不会干预您的浏览。</li>
<li>本服务采用上行数据一次性发送，下行数据即时发送的策略以获得相对更高的用户体验，因此使用本服务上传文件不是一个好的决定，上传大量数据将会变得很容易失败。</li>
</ul>
</p>
<p>
本服务已处理 '.$cs.' 次请求，单向交换流量 '.$dx.' 。
</p>
</body>
</html>
';
if (!$_GET["test"] != null) {
echo $text;
}else{
echo $text;
}
}else{
echo '安装失败，您的虚拟主机不支持curl';
}
}else{
$curlInterface = curl_init();
$headers[] = 'Connection: Keep-Alive';
$headers[] = 'content-type: application/xml';
$headers[] = 'User-Agent: Java0';
curl_setopt_array($curlInterface, array(
		CURLOPT_URL => 'http://server4.operamini.com',
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_POST => 1,
		CURLOPT_POSTFIELDS => @file_get_contents('php://input'))
);
$result = curl_exec($curlInterface);
curl_close($curlInterface);
header('Content-Type: application/octet-stream');
header('Cache-Control: private, no-cache');
$strlen=strlen($result);//统计数据流大小
$row1=$DB->select('*','opera')->fetchALL();
$row1=$row1['0'];
$DB->update('opera',array('cishu'=>$row1['cishu'] + 1 ,'daxiao'=>$row1['daxiao'] + $strlen));//添加到数据库
echo $result;
}
?>
