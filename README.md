# updateTool

* php开发流程化部署更新脚本话工具，方便更新回滚
* 基于svn更新开发的更新工具

### 一，概述

* 该工具主要用于通过svn服务器方便更新，回滚代码的工具，。
* 建议各个svn仓库都搭建在一台外网服务器上，避免跨网络，导致工作量。

 
### 二，示例图

![更新流程](images/demo1.jpg)
![命令行实例](images/demo2.bmp)

 

### 三，特性

* 更新环境， 一般分为4个， 开发，测试，预发布，现网（如图1）
* 工具代码，可以方便的，从开发到测试，最终到上线实现代码流程化更新
* 这里只是提供php开发的脚本话工具源码，svn的四套环境，可以自己搭建
* 开发，可以很方便提起svn更新日志， 并合并处理成要更新的文件路径列表， 根据列表更新


> 备注：更新
        
   
### 四，环境依赖

>需要搭建svn
    
* php ，php开启shell_exec函数
    
    
### 五，开始使用

* 1，提取开发svn更新日志，合并成需要更新的列表文件，

例如：

```
conf/payconf.php
conf/comm.php
public/api/test.html
application/models/Home/Service/Midaspay.php
application/models/Home/Service/Wxgamepay.php
application/models/Home/Service/Wxmppay.php
application/modules/Api/controllers/Wxpay.php
```

> 注意路径只保留根目录下的路径， 前面删除

* 2，流程化提交列表给各个环境更新，使用updateTool工具更新

例如：更新测试部

进入测试部更新工具目录，把列表路径配置到file.txt文件，执行命令，就更新完成

         
* 3，代码文件目录路径 ：

```

updateTools.php
file.txt
log/

``` 

直接执行命令查看使用说明，执行命令如下（如图2）：

```
php updateTools.php

``` 



### 六，联系方式

* qq：251413215


