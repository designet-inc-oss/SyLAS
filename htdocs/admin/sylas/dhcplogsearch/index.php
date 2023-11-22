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
 * ��������DHCP����������
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.12 $
 * $Date: 2015/06/05 13:21:00 $
 **********************************************************/
include_once("lib/dglibcommon");
include_once("../initial");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * �ƥڡ����������
 ********************************************************/

define("TMPLFILE",         "dhcplogsearch.tmpl");
define("OPERATION",        "DHCP logsearch");
define("SELECT_GROUP_SQL", "SELECT loggroup.group_id,loggroup.group_name,loginfo.log_type FROM loggroup JOIN loginfo ON loggroup.log_id=loginfo.log_id WHERE log_type='dhcp';");


/*********************************************************
 * set_tag_data($post, &$tag)
 *
 * �����Υ��åȤ򤹤�
 *
 * [����]
 *       $post       �ϤäƤ�����
 *       $tag               ����
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function set_tag_data($post, &$tag)
{
    global $web_conf;

    /* ���� ���� */
    $javascript = <<<EOD
    function allSubmit(url, page) {
        document.search_condition.action = url;
        document.search_condition.page.value = page;
        document.search_condition.submit();
    }
EOD;

    /*���̤����Ѥ��륿���򥻥åȤ���*/
    /*<<TITLE>>,<<MESSAGE>>,<<SK>>,<<TOPIC>>,<<TAB>>*/
    set_tag_common($tag, $javascript);

    /* ���Υ��쥯�ȥܥå������� */
    if (isset($_POST["loggroup"]) === TRUE) {
        $selected_log =$_POST["loggroup"];
    } else {
        $selected_log = -1;
    }
    $ret = make_dhcp_log_option(SELECT_GROUP_SQL, $selected_log, $option);
    if ($ret === FALSE) {
        return FALSE;
    }
    $tag["<<LOG>>"] = $option;

    /* ip���ɥ쥹�Υƥ����ȥܥå��� */
    if (isset($_POST["ip"]) === TRUE) {
        $tag["<<IP>>"] = escape_html($_POST["ip"]);
    }

    /* mac���ɥ쥹�Υƥ����ȥܥå��� */
    if (isset($_POST["mac"]) === TRUE) {
        $tag["<<MAC>>"] = escape_html($_POST["mac"]);
    }

    /* ���󥿥ե������Υƥ����ȥܥå��� */
    if (isset($_POST["interface"]) === TRUE) {
        $tag["<<IF>>"] = escape_html($_POST["interface"]);
    }

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

    $tag["<<DEFLINE>>"] = $web_conf["sylas"]["displaylines"];
    if (isset($_POST["resultline"])) {
        $tag["<<DEFLINE>>"] = $_POST["resultline"];
    }

    return TRUE;
}

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
    $ip   = escape_html($post["ip"]);
    $mac    = escape_html($post["mac"]);
    $interface = escape_html($post["interface"]);
    $start      = $post["startdate"];
    $end        = $post["enddate"];
    $sesskey    = escape_html($post["sk"]);
    $resultline = $post["resultline"];

    $hidden = <<<EOD
<form method="post" name="search_condition">
  <input type="hidden" name="page">
  <input type="hidden" name="loggroup" value="$loggroup">
  <input type="hidden" name="ip" value="$ip">
  <input type="hidden" name="mac" value="$mac">
  <input type="hidden" name="interface" value="$interface">
  <input type="hidden" name="startdate" value="$start">
  <input type="hidden" name="enddate" value="$end">
  <input type="hidden" name="sk" value="$sesskey">
  <input type="hidden" name="resultline" value="$resultline">
  <input type="hidden" name="search_button" value="search">
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
 *       $result             �����������ξ���
 *       $page               �ڡ���
 *       $looptag            �롼�ץ���
 *       $sesskey            ���å���󥭡�
 *       $post               �ϤäƤ�����
 *
 * [�֤���]
 *       TRUE                ����
 *       FALSE               �۾�
 **********************************************************/
function set_loop_tag($result, $page, &$looptag, $sesskey, $post, &$k)
{
    global $web_conf;

    /* �롼�ץ��������� */
    $start = ($page - 1) * $_POST["resultline"];
    $end   = ($page * $_POST["resultline"]) - 1;

    $i = 0;
    $k = 0;
    foreach ($result as $one_result) {

       if ($i >= $start && $i <= $end) {
           /* ����������                                 *
            * ����������IP���ɥ쥹��MAC���ɥ쥹�����󥿥ե�����,
             �ܺ٥ܥ����ɽ������ */
           $log_date    = escape_html($one_result["DRT"]);
           $log_r_date    = escape_html($one_result["r_DRT"]);
           $log_ip    = escape_html($one_result["ip"]);
           $log_mac = escape_html($one_result["mac"]);
           $log_interface = escape_html($one_result["interface"]);

           /*DACPACK��������������դ�explode��ʬ�䤹��*/
           $half_date = explode(" ", $log_date);

           $e_year = substr($half_date[0], 0, 4);
           $mon = substr($half_date[0], 5, 2);
           $e_mon = preg_replace("/^0/", "", $mon);
           $day = substr($half_date[0], 8, 2);
           $e_day = preg_replace("/^0/", "", $day);
           $hour = substr($half_date[1], 0, 2);
           $e_hour = preg_replace("/^0/", "", $hour);

           $min = substr($half_date[1], 3, 2);
           $e_min = preg_replace("/^0/", "", $min);
           $sec = substr($half_date[1], 6, 2);
           $e_sec = preg_replace("/^0/", "", $sec);

           /*DACPACK��������������դ�explode��ʬ�䤹��*/
           $half_date = explode(" ", $log_r_date);

           $s_year = substr($half_date[0], 0, 4);
           $mon = substr($half_date[0], 5, 2);
           $s_mon = preg_replace("/^0/", "", $mon);
           $day = substr($half_date[0], 8, 2);
           $s_day = preg_replace("/^0/", "", $day);
           $hour = substr($half_date[1], 0, 2);
           $s_hour = preg_replace("/^0/", "", $hour);

           $min = substr($half_date[1], 3, 2);
           $s_min = preg_replace("/^0/", "", $min);
           $sec = substr($half_date[1], 6, 2);
           $s_sec = preg_replace("/^0/", "", $sec);
           
           $more = "<button type=\"submit\" name=\"more\" value=\"";

           $more .= $k;
           $more .= "\">�ܺ�</button>";

           /* �롼�ץ������ͤ����� */
           $looptag[$k]["<<LOG_DATE>>"] = $log_date;
           $looptag[$k]["<<LOG_IP>>"] = $log_ip;
           $looptag[$k]["<<LOG_MAC>>"] = $log_mac;
           $looptag[$k]["<<LOG_IF>>"] = $log_interface;
           $looptag[$k]["<<MORE>>"] = $more;
           $looptag[$k]["<<E_SESS>>"] = $sesskey;
           $looptag[$k]["<<E_MAC>>"] = $log_mac;
           $looptag[$k]["<<E_LOG>>"] = $post["loggroup"];
           $looptag[$k]["<<S_D>>"] = $post["startdate"];
           $looptag[$k]["<<E_D>>"] = $post["enddate"];
           $looptag[$k]["<<RL>>"] = $web_conf["sylas"]["displaylines"];

           $k++;
       }

       /* ���󥯥���� */
       $i++;
    }

    return;
}

/* ��������� */
$tag["<<TITLE>>"]               = "";
$tag["<<JAVASCRIPT>>"]          = "";
$tag["<<SK>>"]                  = "";
$tag["<<TOPIC>>"]               = "";
$tag["<<MESSAGE>>"]             = "";
$tag["<<TAB>>"]                 = "";
$tag["<<LOG>>"]                 = "";
$tag["<<IP>>"]                  = "";
$tag["<<MAC>>"]                 = "";
$tag["<<IF>>"]                  = "";
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
$tag["<<SEARCH_COUNT>>"]        = 0;/*��..��*/
$tag["<<COMMENT_START>>"]       = "<!--";
$tag["<<COMMENT_END>>"]         = "-->";
$tag["<<PRE>>"]                 = "";
$tag["<<NEXT>>"]                = "";
$tag["<<HIDDEN>>"]              = "";
$page = 0;

/*********************************************************
 * exec_dhcp_search
 *
 * �����ͥ����å��򤷡����˹��פ��������������������
 *
 * [����]
 *       $post               �ϤäƤ�����
 *       $data               MySQL��������������ξ���
 *       $result             DHCPACK�Υե����ޥåȤ˹�ä����ξ���
 *
 * [�֤���]
 *       0               ����
 *       1               ���ϥ����å�����
 *       2               mysql����
 **********************************************************/
function exec_dhcp_search($post, &$data, &$result)
{

    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;

    $ret = check_dhcp_search_condition($post);

    if ($ret === FALSE) {
        return(1);
    }

    /*MySQL��³*/
    $conn = MySQL_connect_server();
    if ($conn === FALSE) {
        return(2);  
    }

    /* ���������פ� "MYSQL" �ξ�� */
    if ($web_conf['sylas']['searchtype'] === MYSQL) {

        /*DHCP��������SQL���������*/
        $ret = make_dhcp_search_sql($conn, $post, $search_sql);
        if ($ret === 1) {
            /*MySQL_exec_query����*/
            mysqli_close($conn);
            return(1);
        }

        /*MySQL�����������*/
        $result = MySQL_exec_query($conn, $search_sql);
        $err_num = 0;
        if ($result === FALSE) {
            $err_num = mysqli_errno($conn);
            mysqli_close($conn);
            return(2);
        }

        /*MySQL����Ͽ���줿�������ơ��֥�ξ��������˳�Ǽ*/
        MySQL_get_data($result, $data);
        mysqli_close($conn);

    /* ���������פ� "elasticsearch" �ξ�� */
    } else if ($web_conf['sylas']['searchtype'] === ELASTICSEARCH) {

        /* �����롼�פ򸡺����� */
        $ret = get_loggroup($conn, $post['loggroup'], $groupdata);
        mysqli_close($conn);

        /* �����ƥ२�顼(MYSQL��³���顼) */
        if ($ret === 2) {
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            return(1);

        /* �����롼�פ�¸�ߤ����� */
        } else if ($ret === 0) {

            $gettype = "dhcp";
            /* elasticsearch������ǡ������� */
            $elastic_data = get_elasticdata($groupdata, $post, $gettype);

            /* �����оݤ�elasticsearch�����Ф���³�Ǥ��ʤ��ä���� */
            if ($elastic_data === FALSE) {
                $err_msg = sprintf($msgarr['50000'][SCREEN_MSG], LOG_NAME_DISP);
                $log_msg = sprintf($msgarr['50000'][LOG_MSG], LOG_NAME_LOG);
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit(1);
            }

            /* elasticserach���֤��ͤ�json�ǥ�����(Ϣ�����������) */
            $xmlarr = json_decode($elastic_data);
            /* json�ǥ����ɤ���ɬ�פ��ͤ������� */
            $data = extract_values($xmlarr);

            /* �������Ի� */
            if ($data === false) {
                return(1);
            }
        } else {
            /* ������ */
            result_log(OPERATION . ":NG:" . $log_msg);
            return(1);
        }
    }

    $result = array();
    $max = count($data);
    $i = 0;

    foreach($data as $value) {
        $matches = array();

        if (preg_match("/DHCPACK on (.*) to (.*) via (.*)/", $value["Message"], $matches)) {
            $result[$i]["ip"] = $matches[1];

            $needle = strpos($matches[2], "(");
            if ($needle !== FALSE) {
                $result[$i]["mac"] = substr($matches[2], 0, $needle - 1);
            } else {
                $result[$i]["mac"] = $matches[2];
            }

            $result[$i]["interface"] = $matches[3];
            $result[$i]["DRT"] = $value["DeviceReportedTime"];
            $result[$i]["r_DRT"] = $value["DeviceReportedTime"];
            $i++;

        } elseif (preg_match("/Reply NA: address (.*) to client with duid (.*) iaid = (.*) valid for (.*) seconds/", $value["Message"], $matches)) {
            $result[$i]["ip"] = $matches[1];
            $result[$i]["mac"] = $matches[2];
            $result[$i]["interface"] = "";
            $result[$i]["DRT"] = $value["DeviceReportedTime"];
            $result[$i]["r_DRT"] = $value["DeviceReportedTime"];
            $i++;
        }
    }
    return(0);
}

/****************************************
*             �������                  *
****************************************/

/* ����ե����롢���ִ����ե������ɹ������å��������å� */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

$looptag = array();

/****************************************
*                main                   *
****************************************/

/* �����ܥ��󤬲����줿�Ȥ� */
if (isset($_POST["search_button"])) {

    $post = $_POST;
    $ret = exec_dhcp_search($post, $data, $result);
    if ($ret == 1) {
        result_log(OPERATION . ":NG:" . $log_msg);
    } else if ($ret == 2) {
        result_log(OPERATION . ":NG:" . $log_msg);
        syserr_display();
        exit(1);
    } else {

         /* �ڡ��������ϤäƤ����Ȥ���POST���줿�ͤ���� *
          * �ϤäƤ��Ƥ��ʤ�����1�ڡ����� */
         if (isset($_POST["page"]) === TRUE) {
             $page = $_POST["page"];
         } else {
             $page = 1;
         }

        $data_count = count($result);
        $tag["<<COMMENT_START>>"]       = "";
        $tag["<<COMMENT_END>>"]         = "";
        $tag["<<SEARCH_COUNT>>"]        = $data_count;
        $err_msg = sprintf($msgarr['28022'][SCREEN_MSG], $data_count);

        /* ɽ������η��� */
        $all_page = get_page($result, $page, $tag);
        make_hidden($post, $tag);

        /* �롼�ץ����κ��� */
        set_loop_tag($result, $page, $looptag, $sesskey, $post, $i);

    }
} 

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

?>
