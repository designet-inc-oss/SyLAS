$(function(){
jQuery.datetimepicker.setLocale('ja');
$('.datetimepicker').datetimepicker(
{
    i18n:{
      ja:{
       months:[
        '1��','2��','3��','4��',
        '5��','6��','7��','8��',
        '9��','10��','11��','12��',
       ],
       dayOfWeekShort:[
        "��", "��", "��", "��", 
        "��", "��", "��",
       ],
       dayOfWeek:[
        "����", "����", "����", "����", 
        "����", "����", "����",
       ]
      }
    },
    format:'Y/m/d H:i:s',
    step:30
});
});

