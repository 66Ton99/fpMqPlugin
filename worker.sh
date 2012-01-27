#! /bin/bash

# Configuration
PRJPATH=`dirname $0`

cd $PRJPATH



if [[ -z "$1" && $1 = "start" ]]
then
	php	worker.php >> "$PRJPATH/log/worker.log"
fi

if [ $1 = "stop" ]
then
  php	worker.php --stop
fi

if [ $1 = "watchdog" ]
then
  pidfile="$PRJPATH/data/.worker.pid"
  if [ -f $pidfile ]
  then
		pidnum=$(cat $pidfile)
		if [ -z "`ps -p ${pidnum} | grep ${pidnum}`" ]
	  then
		    echo Detected dead PID  $pidnum. Reanimating.
		    php	worker.php >> "$PRJPATH/log/worker.log"
		fi
  fi
fi
