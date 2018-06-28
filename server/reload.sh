#!/bin/bash

# 平滑重启
# USR1信号将导致以下步骤的发生：停止接受新的连接，等待当前连接停止，重新载入配置文件，重新打开日志文件，重启服务器，从而实现相对平滑的不关机的更改。

pid=`pidof master_live`
echo $pid
kill -USR1 $pid

