#!/bin/bash
#if you use different tool than mktemp then pls find TMPDIR below and change option
MKTEMPTOOL="mktemp"
LIBRARYPATH="application/libraries"
command -v ${MKTEMPTOOL} >/dev/null 2>&1 || { echo >&2 "${MKTEMPTOOL}  is required but it's not installed.  Some distributions have tempfile. If so pls edit this file and change line containing MKTEMPTOOL def. Aborting."; exit 1; }
command -v tar >/dev/null 2>&1 || { echo >&2 "tar is required but it's not installed.  Aborting."; exit 1; }
command -v wget >/dev/null 2>&1 || { echo >&2 "wget is required but it's not installed.  Aborting."; exit 1; }
WELCOMEMSG="Script will create additional folders and downloads additional software. After script is finished please run composer (https://getcomposer.org/) on composer.json - it will install Doctrine 2.4.x and Zend-ACL"
echo -e ${WELCOMEMSG}
install(){
   TMPDIR=`${MKTEMPTOOL} -d`
   LOGOS="logos2"
   if [ ! -d "${LOGOS}" ]
   then
     echo "${LOGOS} directory doesnt exist....creating"
     mkdir ${LOGOS}
     echo "done"
   fi
  

  
}
install
echo -e "Done!!!"
echo -e "Now go to application/config"
echo -e "copy belowe config files and customize them:
================="
echo -e "config-default.php -> config.php"
echo -e "config_rr-default.php -> config_rr.php" 
echo -e "database-default.php -> database.php" 
echo -e "email-default.php -> email.php"
echo -e "memcached-default.php -> memcached.php"
echo -e "=============="
exit 0

