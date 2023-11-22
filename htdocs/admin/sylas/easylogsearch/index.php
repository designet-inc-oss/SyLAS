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
 * �������Ѵʰץ���������
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.12 $
 * $Date: 2014/07/18 01:45:50 $
 **********************************************************/
include_once("../initial");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * �ƥڡ����������
 ********************************************************/

define("TMPLFILE",         "easylogsearch.tmpl");
define("OPERATION",        "Easy logsearch");

define("SELECT_GROUP_SQL", "SELECT * FROM loggroup;");

define("REGEXP_ERR_NUM", 1139);

/*********************************************************
 * make_hidden
 *
 * hidden�����Υե�������������
 *
 * [����]
 *       $post               ���Ϥ��줿��
 *       $tag                �֤���������
 *
 * [�֤���]
 *       TRUE                ����
 *       FALSE               �۾�
 **********************************************************/
function make_hidden($post, &$tag)
{
    /* �֤��������ͤ��ѿ������� */
    $loggroup   = $post["loggroup"];
    $priority   = $post["priority"];
    $hostname   = escape_html($post["hostname"]);
    $keyword    = escape_html($post["keyword"]);
    $searchtype = $post["searchtype"];
    $start      = $post["startdate"];
    $end        = $post["enddate"];
    $sesskey    = escape_html($post["sk"]);
    $resultline = $post["resultline"];

    $hidden = <<<EOD
<form method="post" name="search_condition">
  <input type="hidden" name="search_button" value="">
  <input type="hidden" name="page">
  <input type="hidden" name="loggroup" value="$loggroup">
  <input type="hidden" name="priority" value="$priority">
  <input type="hidden" name="hostname" value="$hostname">
  <input type="hidden" name="keyword" value="$keyword">
  <input type="hidden" name="searchtype" value="$searchtype">
  <input type="hidden" name="startdate" value="$start">
  <input type="hidden" name="enddate" value="$end">
  <input type="hidden" name="sk" value="$sesskey">
  <input type="hidden" name="resultline" value="$resultline">
</form>

EOD;

    $tag["<<HIDDEN>>"] =  $hidden;
    return;
}

/*********************************************************
 * set_loop_tag
 *
 * �롼�ץ������������
 *
 * [����]
 *       $looptag            �롼�ץ���
 *
 * [�֤���]
 *       TRUE                ����
 *       FALSE               �۾�
 **********************************************************/
function set_loop_tag($data, $page, &$looptag)
{
    global $web_conf;

    /* �롼�ץ��������� */
    $start = ($page - 1) * $_POST["resultline"];
    $end   = ($page * $_POST["resultline"]) - 1;

    $i = 0;
    $k = 0;
    foreach ($data as $one_data) {

       if ($i >= $start && $i <= $end) {
           /* ����������                                 *
            * �����������ۥ���̾����å�������ɽ������ */
           $log_date    = escape_html($one_data["DeviceReportedTime"]);
           $log_host    = escape_html($one_data["FromHost"]);
           $log_message = escape_html($one_data["Message"]);

           /* �롼�ץ������ͤ����� */
           $looptag[$k]["<<LOG_DATE>>"] = $log_date;
           $looptag[$k]["<<LOG_HOST>>"] = $log_host;
           $looptag[$k]["<<LOG_MESSAGE>>"] = str_replace(" ", "&nbsp", $log_message);
           $k++;
       }

       /* ���󥯥���� */
       $i++;
    }

    return;
}

/*********************************************************
 * set_tag_data
 *
 * ���������Ƥ��������
 *
 * [����]
 *       $post               ���Ϥ��줿��
 *       $tag                �֤���������
 *
 * [�֤���]
 *       TRUE                ����
 *       FALSE               �۾�
 **********************************************************/
function set_tag_data($post, &$tag)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $lob_msg;

    /* ���� ���� */
    $javascript = <<<EOD
    function allSubmit(url, page) {
        document.search_condition.action = url;
        document.search_condition.page.value = page;
        document.search_condition.submit();
    }

EOD;

    set_tag_common($tag, $javascript);

    /* �����оݥ��Υ��쥯�ȥܥå������� */
    if (isset($_POST["loggroup"]) === TRUE) {
        $selected_log =$_POST["loggroup"];
    } else {
        $selected_log = -1;
    }
    $ret = make_log_option(SELECT_GROUP_SQL, $selected_log, $option);
    if ($ret === FALSE) {
        return FALSE;
    }
    $tag["<<LOG_OPTION>>"] = $option;

    /* �ץ饤����ƥ��Υ��쥯�ȥܥå������� */
    $option = "";
    if (isset($_POST["priority"]) === TRUE) {
        $priority =$_POST["priority"];
    } else {
        $priority = -1;
    }
    make_priority_option($priority, $option);
    $tag["<<PRIORITY_OPTION>>"] = $option;

    /* �ۥ���̾�Υƥ����ȥܥå��� */
    if (isset($_POST["hostname"]) === TRUE) {
        $tag["<<HOSTNAME>>"] = escape_html($_POST["hostname"]);
    }

    /* ������ɤΥƥ����ȥܥå��� */
    if (isset($_POST["keyword"]) === TRUE) {
        $tag["<<KEYWORD>>"] = escape_html($_POST["keyword"]);
    }

    /* �饸���ܥ���Υ����å����� */
    if (isset($_POST["searchtype"]) === TRUE) {
        $radio =$_POST["searchtype"];
    } else {
        $radio = 0;
    }
    $option = array("", "", "");
    make_checked_radio($radio, $option);
    $tag["<<CHECKED0>>"] = $option[0];
    $tag["<<CHECKED1>>"] = $option[1];
    $tag["<<CHECKED2>>"] = $option[2];

    /* �������ϻ��֥��쥯�ȥܥå������� */
    $option = array("0" => "",
                    "1" => "",
                    "2" => "",
                    "3" => "",
                    "4" => "",
                    "5" => ""
                   );


    $tag["<<STARTDATE>>"] = date("Y/m/d 00:00:00");
    if (isset($_POST["startdate"]) === TRUE) {
        $tag["<<STARTDATE>>"] = $_POST["startdate"];
    }

    $tag["<<ENDDATE>>"] = date("Y/m/d 23:59:59");
    if (isset($_POST["enddate"]) === TRUE) {
        $tag["<<ENDDATE>>"] = $_POST["enddate"];
    }

    $tag["<<DEFLINE>>"] = $web_conf["sylas"]["displaylines"];
    if (isset($_POST["resultline"])) {
        $tag["<<DEFLINE>>"] = $_POST["resultline"];
    }

    return TRUE;
}


/***********************************************************
 * printCSV()
 * ��������ɽ������ˡ����ȥ꡼����Ф��Ƹ�����̤�CSV�������Ǥ��Ф���
 *
 * [����]
 *      $data       SQL��������줿�������(����)
 * [�֤���]
 *      �ʤ�
 **********************************************************/
function printCSV($data)
{
    global $web_conf;

    $keymap = array("msg"=>"Message",
                    "host"=>"FromHost",
                    "date"=>"DeviceReportedTime",
                   );

    $order = explode(",", $web_conf["sylas"]["csvformat"]);

    /* ������̤�1�鷺�Ľ������� */
    foreach ($data as $result) {
        $line = array();

        /* ���֥륯�����Ȥ򥨥������� */
        $result = str_replace('"', '""', $result);

        foreach ($order as $key) {
            $line[] = '"'. $result[$keymap[$key]]. '"';
        }

        /* ����޶��ڤ�ǤĤʤ��ƽ��� */
        print implode(",", $line);
        print "\r\n";
    }

    return;
}

/***********************************************************
 * �������
 **********************************************************/

/* ��������� */
$tag["<<TITLE>>"]               = "";
$tag["<<JAVASCRIPT>>"]          = "";
$tag["<<SK>>"]                  = "";
$tag["<<TOPIC>>"]               = "";
$tag["<<MESSAGE>>"]             = "";
$tag["<<TAB>>"]                 = "";
$tag["<<LOG_OPTION>>"]          = "";
$tag["<<PRIORITY_OPTION>>"]     = "";
$tag["<<START_YEAR_OPTION>>"]   = "";
$tag["<<HOSTNAME>>"]            = "";
$tag["<<KEYWORD>>"]             = "";
$tag["<<CHECKED0>>"]            = "";
$tag["<<CHECKED1>>"]            = "";
$tag["<<CHECKED2>>"]            = "";
$tag["<<START_YEAR_OPTION>>"]   = "";
$tag["<<START_MONTH_OPTION>>"]  = "";
$tag["<<START_DATE_OPTION>>"]   = "";
$tag["<<START_HOUR_OPTION>>"]   = "";
$tag["<<START_MINUTE_OPTION>>"] = "";
$tag["<<START_SECOND_OPTION>>"] = "";
$tag["<<END_YEAR_OPTION>>"]     = "";
$tag["<<END_MONTH_OPTION>>"]    = "";
$tag["<<END_DATE_OPTION>>"]     = "";
$tag["<<END_HOUR_OPTION>>"]     = "";
$tag["<<END_MINUTE_OPTION>>"]   = "";
$tag["<<END_SECOND_OPTION>>"]   = "";
$tag["<<COMMENT_START>>"]       = "<!--";
$tag["<<COMMENT_END>>"]         = "-->";
$tag["<<SEARCH_COUNT>>"]        = 0;
$tag["<<PRE>>"]                 = "";
$tag["<<NEXT>>"]                = "";
$tag["<<HIDDEN>>"]              = "";

$page = 0;

$groupdata = array();

/* ����ե����롢���ִ����ե������ɹ������å��������å� */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main����
 **********************************************************/

/* �����ܥ��󤬲����줿�Ȥ� */
$looptag = array();
if (isset($_POST["search_button"]) || isset($_POST["download_button"])) {

     $post = $_POST;
     /* �ڡ��������ϤäƤ����Ȥ���POST���줿�ͤ���� *
      * �ϤäƤ��Ƥ��ʤ�����1�ڡ����� */
     if (isset($_POST["page"]) === TRUE) {
         $page = $_POST["page"];
     } else {
         $page = 1;
     }

    /* �����ͥ����å�(�ؿ���Ǥ��٤Ƥ��ͤ�����å�) */
    $ret = check_easy_search_condition($post);
    if ($ret === TRUE) {

        /* MySQL��³ */
        $conn = MySQL_connect_server();
        if ($conn === FALSE) {
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit(1);
        }

    /* ���������פ� "MYSQL" �ξ�� */
    if ($web_conf['sylas']['searchtype'] === MYSQL) {

            /* �ʰ׸�����SQL��������� */
            $ret = make_easy_search_sql($conn, $post, $search_sql);
            if ($ret === 2) {
                mysqli_close($conn);
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit(1);
            /* ���������Υۥ��Ȥ����ꤵ�졢�������0�郎���ꤷ����� */
            } else if ($ret === 3) {
                mysqli_close($conn);
                /* �����֤����� */
                $tag["<<COMMENT_START>>"]       = "";
                $tag["<<COMMENT_END>>"]         = "";
                $tag["<<SEARCH_COUNT>>"]        = '0';
                $err_msg = sprintf($msgarr['28022'][SCREEN_MSG], '0');
                /* ���������ݻ���hidden����� */
                make_hidden($post, $tag);
                /* ��������ɥܥ��󲡲��� */
                if (isset($_POST["download_button"])) {
                    /* �ե�����̾����ƥ��ȥ꡼�ॻ�å� */
                    $fn = "log_" . date("YmdHis") . ".csv";
                    header("Content-Disposition: attachment; filename=\"$fn\"");
                    header("Content-Type: application/octet-stream");
                    exit(0);
                }
            } else if ($ret === 0) {
                /* MySQL����������� */
                $result = MySQL_exec_query($conn, $search_sql);
                $err_num = 0;
                if ($result === FALSE) {
                    $err_num = mysqli_errno($conn);
                    mysqli_close($conn);

                    /* ����ɽ�������Ǥʤʤ������꼺�Ԥϥ����ƥ२�顼 */
                    if ($err_num != REGEXP_ERR_NUM) {
                        result_log(OPERATION . ":NG:" . $log_msg);
                        syserr_display();
                        exit(1);
                   }
                }

                if ($err_num != REGEXP_ERR_NUM) {
                    /* MySQL����Ͽ���줿�������ơ��֥�ξ��������˳�Ǽ */
                    MySQL_get_data($result, $data);
                    mysqli_close($conn);

                    /* ��������ɥܥ��󲡲��� */
                    if (isset($_POST["download_button"])) {
                        /* �ե�����̾����ƥ��ȥ꡼�ॻ�å� */
                        $fn = "log_" . date("YmdHis") . ".csv";
                        header("Content-Disposition: attachment; filename=\"$fn\"");
                        header("Content-Type: application/octet-stream");
                        /* ���������ǡ����򥢥��ȥץå� */
                        printCSV($data);
                        exit(0);
                    }

                    /* �����֤����� */
                    $data_count = count($data);
                    $tag["<<COMMENT_START>>"]       = "";
                    $tag["<<COMMENT_END>>"]         = "";
                    $tag["<<SEARCH_COUNT>>"]        = $data_count;
                    $err_msg = sprintf($msgarr['28022'][SCREEN_MSG], $data_count);

                    /* ɽ������η��� */
                    get_page($data, $page, $tag);

                    /* ���������ݻ���hidden����� */
                    make_hidden($post, $tag);

                    /* �롼�ץ����κ��� */
                    set_loop_tag($data, $page, $looptag);

                } else {

                   $err_msg = $msgarr['28026'][SCREEN_MSG];

                   /* ������ */
                   result_log(OPERATION . ":NG:" . $log_msg);
                }

            } else {
                /* ������ */
                result_log(OPERATION . ":NG:" . $log_msg);
            }

        /* ���������פ� "elasticsearch" �ξ�� */
        } else if ($web_conf['sylas']['searchtype'] === ELASTICSEARCH) {

            /* �����롼�פ򸡺����� */
            $ret = get_loggroup($conn, $post['loggroup'], $groupdata);

            /* �����ƥ२�顼(MYSQL��³���顼) */
            if ($ret === 2) {
                mysqli_close($conn);
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit(1);

            /* �����롼�פ�¸�ߤ����� */
            } else if ($ret === 0) {

                mysqli_close($conn);
                /* elasticsearch������ǡ������� */
                $elastic_data = get_elasticdata($groupdata, $post);

                /* �����оݤ�elasticsearch�����Ф���³�Ǥ��ʤ��ä���� */
                if ($elastic_data === FALSE) {
                    $err_msg = sprintf($msgarr['50000'][SCREEN_MSG], LOG_NAME_DISP);
                    $log_msg = sprintf($msgarr['50000'][LOG_MSG], LOG_NAME_LOG);
                    result_log(OPERATION . ":NG:" . $log_msg);
                    syserr_display();
                    exit(1);
                }
                   
                /* elasticserach���֤��ͤ�json�ǥ����� */
                $xmlarr = json_decode($elastic_data);

                /* json�ǥ����ɤ���ɬ�פ��ͤ������� */
                $data = extract_values($xmlarr);

                /* �������Ի� */
                if ($data === false) {
                    result_log(OPERATION . ":NG:" . $log_msg);
                    $data = "";
                }

                /* ��������ɥܥ��󲡲��� */
                if (isset($_POST["download_button"])) {
                    /* �ե�����̾����ƥ��ȥ꡼�ॻ�å� */
                    $fn = "log_" . date("YmdHis") . ".csv";
                    header("Content-Disposition: attachment; filename=\"$fn\"");
                    header("Content-Type: application/octet-stream");
                    /* ���������ǡ����򥢥��ȥץå� */
                    printCSV($data);
                    exit(0);
                }

                /* �����ִ� ������̤�¸�ߤ����� */
                if ($data !== "") {
                    $data_count = count($data);
                    $tag["<<COMMENT_START>>"]       = "";
                    $tag["<<COMMENT_END>>"]         = "";
                    $tag["<<SEARCH_COUNT>>"]        = $data_count;
                    $err_msg = sprintf($msgarr['28022'][SCREEN_MSG], $data_count);

                    /* ɽ������η��� */
                    get_page($data, $page, $tag);

                    /* ���������ݻ���hidden����� */
                    make_hidden($post, $tag);

                    /* �롼�ץ����κ��� */
                    set_loop_tag($data, $page, $looptag);
                };

            } else {
                mysqli_close($conn);
                /* ������ */
                result_log(OPERATION . ":NG:" . $log_msg);
            }
        }

    } else {
        /* ������ */
        result_log(OPERATION . ":NG:" . $log_msg);
    }
}

/***********************************************************
 * ɽ������
 **********************************************************/

/* ���� ���� */
$ret = set_tag_data($_POST, $tag);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, $looptag, "<<STARTLOOP>>", "<<ENDLOOP>>");
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

exit(0);
?>
