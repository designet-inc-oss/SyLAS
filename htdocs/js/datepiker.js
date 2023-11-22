$(function(){
jQuery.datetimepicker.setLocale('ja');
$('.datetimepicker').datetimepicker(
{
    i18n:{
      ja:{
       months:[
        '1月','2月','3月','4月',
        '5月','6月','7月','8月',
        '9月','10月','11月','12月',
       ],
       dayOfWeekShort:[
        "日", "月", "火", "水", 
        "木", "金", "土",
       ],
       dayOfWeek:[
        "日曜", "月曜", "火曜", "水曜", 
        "木曜", "金曜", "土曜",
       ]
      }
    },
    format:'Y/m/d H:i:s',
    step:30
});
});

