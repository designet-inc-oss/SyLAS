<?php
/*
 * postLDAPadmin
 *
 * Copyright (C) 2006,2007 DesigNET, INC.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */
/***********************************************************
 * �롼���Խ�����
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.1 $
 * $Date: 2014/07/07 08:10:53 $
 **********************************************************/
include_once("../initial");
include_once("lib/dglibldap");
include_once("lib/dglibpostldapadmin");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * �ƥڡ����������
 ********************************************************/

define("TMPLFILE",  "rule_mod.tmpl");
define("OPERATION",  "Rule mod");

/*********************************************************
 * rule_mod()
 *
 * �롼����Խ�����
 *
 * [����]
 *      $post          ���Ϥ��줿��
 *      $lockfile      ��å��ե�����Υѥ�
 *
 * [�֤���]
 *      0              ����
 *      1              �������ܤ��ʤ����� 
 *      2              �������ܤ��뼺��
 ********************************************************/

function rule_mod($post, $lockfile)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;

    /* ��å��ե�������� */ 
    /* ��å��ե����뤬���뤫���� */
    while (file_exists("$lockfile") === TRUE) {
        /* ��å��ե����뤢��ʤ�ʤ��ʤ�ޤ��Ԥ� */
        /* 1���Ԥ� */
        sleep(1);
    }
    /* ��å��ե������� */
    $mkfile = touch("$lockfile");
    if ($mkfile === FALSE) {
        /* ��å��ե���������˼��� */
        /* ���顼���� */
        $err_msg = sprintf($msgarr['28032'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28032'][LOG_MSG]);
        return 2;
    }
    /* ��å��ե������������ */
    /* �������� */
    /* rsyslog�ե������open���� */
    $modfile = $web_conf["sylas"]["rsyslogconfdir"] . $post["file"];
    $modnum = $post["number"];
    /* �Խ�����ե����뤬���뤫���� */
    if (!file_exists($modfile)) {
        /* �Խ�����ե����뤬�ʤ���Х��顼 */
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28043'][SCREEN_MSG], $modfile);
        $log_msg = sprintf($msgarr['28043'][LOG_MSG], $modfile);
        return 1;
    }
    $fh = fopen($modfile, "w");
    if ($fh === FALSE) {
        /* �ե����륪���ץ�˼��� */
        /* ���顼���� */
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28034'][SCREEN_MSG], $modfile);
        $log_msg = sprintf($msgarr['28034'][LOG_MSG], $modfile);
        return 1;
    }
    /* rsyslog�ե�����˽񤭹������Ƥ�������� */
    $contents = in_contents($_POST, $modnum);
    /* rsyslog�ե�����˽񤭹��� */
    $ret = fwrite($fh, $contents);
    /* �񤭹��ߥ��顼���� */
    if ($ret === FALSE) {
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28034'][SCREEN_MSG], $modfile);
        $log_msg = sprintf($msgarr['28034'][LOG_MSG], $modfile);
        return 1;
    }
    /* �񤭹�������������rsyslog�Ƶ�ư */
    $cmd = ($web_conf["sylas"]["rsyslogrestartcmd"]);
    $output = "";
    exec($cmd, $output, $ret);
    /* ��λ�����ɤ�0�Ǥʤ���кƵ�ư���� */
    if ($ret != 0) {
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28035'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28035'][LOG_MSG]);
        return 1;
    }
    /* ��å��ե�����ä� */
    $delfile = unlink("$lockfile");
    if ($delfile === FALSE){
        /* ��å��ե��������˼��� */
        /* ���顼���� */
        $err_msg = sprintf($msgarr['28036'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28036'][LOG_MSG]);
        return 2;
    }
    /* �������̤����� */
    $log_msg = sprintf($msgarr['28044'][LOG_MSG], $modfile);
    return 0;
}
/***********************************************************
 * �������
 **********************************************************/

/* ��������� */
$tag["<<TITLE>>"]      = "";
$tag["<<JAVASCRIPT>>"] = "";
$tag["<<SK>>"]         = "";
$tag["<<TOPIC>>"]      = "";
$tag["<<MESSAGE>>"]    = "";
$tag["<<TAB>>"]        = "";
$tag["<<MENU>>"]       = "";
$tag["<<IPADDRESS>>"]  = "";
$tag["<<KEYWORD>>"]    = "";
$tag["<<MAILTO>>"]     = "";
$tag["<<SUBJECT>>"]    = "";
$tag["<<FACILITY_OPTION>>"]    = "";
$tag["<<BODY>>"]       = "";
$tag["<<FILE>>"]       = "";
$tag["<<NUMBER>>"]     = "";

/* ����ե����롢���ִ����ե������ɹ������å��������å� */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main����
 **********************************************************/
/* �����������줿�� */
if (isset($_POST["mod"])){

    $lockfile = $web_conf["sylas"]["rsyslogconfdir"] . LOCK;
    
    /* �����ͥ����å� */
    $ret = check_rule($_POST);
    /*�����ͥ��顼�ξ��*/
    if ($ret  === FALSE) {
        /* ���顼��å�������ɽ�� */
        result_log(OPERATION . ":NG:" . $log_msg);
    } else {
        /* �����ͤ���������� */
        /* ��Ͽ���� */
        $ret = rule_mod($_POST, $lockfile);
        switch ($ret) {
        case 1:
            /* �������ܤ��ʤ����顼 */
            result_log(OPERATION . ":NG:" . $log_msg);
            break;
        case 2:
            /* �������ܤ��륨�顼 */
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit (1);
        case 0:
            /* ���� */
            /* �������̤����� */
            result_log(OPERATION . ":OK:" . $log_msg);
            dgp_location("index.php");
            exit (0);
        }
    }
}
/* ����󥻥�ܥ��󤬲����줿�� */
if (isset($_POST["cancel"])) {
    dgp_location("index.php");
    exit;
}
/***********************************************************
 * ɽ������
 **********************************************************/

/* ����� */
$post = array();
/* �ե����뤫���ͤ���ɽ�� */
if (isset($_POST["radio"]) === TRUE) {
    $filenum = substr($_POST["radio"], 0, -5);
    $arrayfile = array();
    $flag = 0;
    /* �ϤäƤ����ե�����̾������������ */
    $arrayfile[] = $_POST["radio"];
    $dir = $web_conf["sylas"]["rsyslogconfdir"];
    /* �ե��������Ȥ�������� */
    $filearray = get_array_file($arrayfile, $dir, $flag);
    if ($flag == 1) {
        $tag["<<MESSAGE>>"] = $err_msg;
        result_log(OPERATION . ":NG:" . $log_msg);
        syserr_display();
        exit(1);
    }
    /* ������IP���ɥ쥹������Х��åȤ��� */
    if (isset($filearray[0][IP_SET])) {
        $tag["<<IPADDRESS>>"] = escape_html($filearray[0][IP_SET]);
    }
    /* ������ɤȰ����ɬ�� */
    $keyword = str_replace("\\'", "'", $filearray[0][KEYWORD_SET]);
    $keyword = str_replace("\\\\", "\\", $keyword);
    $tag["<<KEYWORD>>"]   = escape_html($keyword);
    $tag["<<MAILTO>>"]    = escape_html($filearray[0][MAILTO_SET]);
    /* �ե�����ƥ��Ƚ����٤��ͤ�����Х��åȤ��� */
    /* �ʤ���С֤��٤ơפˤʤ�褦���򤤤�� */
    if (isset($filearray[0][FACILITY_SET])) {
        $post["facility"] = $filearray[0][FACILITY_SET];
    } else {
        $post["facility"] = "";
    }
    if (isset($filearray[0][DEGREE_SET])) {
        $post["degree"] = $filearray[0][DEGREE_SET];
    } else {
        $post["degree"] = "";
    }
    /* ��̾����ʸ����Х��åȤ��� */
    if (isset($filearray[0][SUBJECT_SET . "$filenum"])) {
        $subject = $filearray[0][SUBJECT_SET . "$filenum"];
        $subject = str_replace("\\%", "%", $subject);
        $subject = str_replace("\\\"", "\"", $subject);
        $tag["<<SUBJECT>>"] = escape_html($subject);
    }
    if (isset($filearray[0][BODY_SET . "$filenum"])) {
        $body = ($filearray[0][BODY_SET . "$filenum"]);
        $body = str_replace("\\%", "%", $body);
        $body = str_replace("\\\"", "\"", $body);
        $body = escape_html($body);
        $body = str_replace("\\r\\n", "\n", $body);
        $tag["<<BODY>>"] = $body;
    }
    /* hidden�ǥե�����̾�ȥե������ֹ���Ϥ� */
    $tag["<<FILE>>"] = $_POST["radio"];
    $tag["<<NUMBER>>"] = $filenum;
} else {
    /* ��ɽ�� */
    $post = $_POST;
    $tag["<<IPADDRESS>>"] = escape_html($post["ipaddress"]);
    $tag["<<KEYWORD>>"]   = escape_html($post["keyword"]);
    $tag["<<MAILTO>>"]    = escape_html($post["mailto"]);
    $tag["<<SUBJECT>>"]   = escape_html($post["subject"]);
    $tag["<<BODY>>"]      = escape_html($post["body"]);
    $tag["<<FILE>>"]      = $post["file"];
    $tag["<<NUMBER>>"]   = $post["number"];
}

/* ���� ���� */
set_tag_common($tag);

/* ���쥯�ȥܥå������� */
$facilityoption = "";
make_select($facility_arr, $facilityoption,  $post["facility"]);
$tag["<<FACILITY_OPTION>>"] = $facilityoption;

$degreeoption = "";
make_select($degree_arr, $degreeoption,  $post["degree"]);
$tag["<<DEGREE_OPTION>>"] = $degreeoption;

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

exit(0);
?>
