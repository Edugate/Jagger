#!/bin/bash
#if you use different tool than mktemp then pls find TMPDIR below and change option
MKTEMPTOOL="mktemp"

command -v ${MKTEMPTOOL} >/dev/null 2>&1 || { echo >&2 "${MKTEMPTOOL}  is required but it's not installed.  Some distributions have tempfile. If so pls edit this file and change line containing MKTEMPTOOL def. Aborting."; exit 1; }
command -v tar >/dev/null 2>&1 || { echo >&2 "tar is required but it's not installed.  Aborting."; exit 1; }
command -v wget >/dev/null 2>&1 || { echo >&2 "wget is required but it's not installed.  Aborting."; exit 1; }
WELCOMEMSG="Script will create additional folders and downloads additional software"
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
 
   LIBRARYPATH="application/libraries"
   DOCTRINE="Doctrine"
   if [ ! -d "${LIBRARYPATH}/${DOCTRINE}" ]
   then
      echo "Doctrine is not installed"
      echo "downloading...."
      wget http://www.doctrine-project.org/downloads/DoctrineORM-2.3.3-full.tar.gz -O ${TMPDIR}/DoctrineORM-2.3.3-full.tar.gz
      tar zxf ${TMPDIR}/DoctrineORM-2.3.3-full.tar.gz -C ${TMPDIR}/
      cp -r ${TMPDIR}/DoctrineORM-2.3.3/Doctrine ${LIBRARYPATH}/ 
      echo ${TMPDIR}
   else
      echo -e "${LIBRARYPATH}/${DOCTRINE} already exists"
   fi

   ZENDACL="Zend"
   if [ ! -d "${LIBRARYPATH}/${ZENDACL}" ]
   then
     wget http://packages.zendframework.com/releases/ZendFramework-1.12.0/ZendFramework-1.12.0.tar.gz -O ${TMPDIR}/ZendFramework-1.12.0.tar.gz
     tar zxf ${TMPDIR}/ZendFramework-1.12.0.tar.gz  -C ${TMPDIR}/
     SRCZENDLIB="${TMPDIR}/ZendFramework-1.12.0/library/Zend"
     mkdir ${LIBRARYPATH}/${ZENDACL}
     cp -r ${SRCZENDLIB}/Acl.php ${SRCZENDLIB}/Exception.php ${SRCZENDLIB}/Acl  ${LIBRARYPATH}/${ZENDACL}/
   else
     echo -e "${LIBRARYPATH}/${ZENDACL} already exists"
   fi

   GESHI="geshi"
   if [ ! -d "${LIBRARYPATH}/${GESHI}" ]
   then
     wget "http://downloads.sourceforge.net/project/geshi/geshi/GeSHi%201.0.8.11/GeSHi-1.0.8.11.tar.gz?r=http%3A%2F%2Fsourceforge.net%2Fprojects%2Fgeshi%2Ffiles%2Flatest%2Fdownload%3Fsource%3Dfiles&ts=1346371975&use_mirror=heanet" -O ${TMPDIR}/GeSHi-1.0.8.11.tar.gz
     tar zxf ${TMPDIR}/GeSHi-1.0.8.11.tar.gz -C ${LIBRARYPATH}/
   fi
  
  XMLSECLIB="xmlseclibs"
   if [ ! -d "${LIBRARYPATH}/${XMLSECLIB}" ]
   then
     wget http://xmlseclibs.googlecode.com/files/xmlseclibs-1.3.0.tar.gz -O ${TMPDIR}/xmlseclibs-1.3.0.tar.gz
     tar zxf  ${TMPDIR}/xmlseclibs-1.3.0.tar.gz -C ${TMPDIR}/
     mkdir "${LIBRARYPATH}/${XMLSECLIB}"
     cp ${TMPDIR}/${XMLSECLIB}/xmlseclibs.php ${LIBRARYPATH}/${XMLSECLIB}/
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

