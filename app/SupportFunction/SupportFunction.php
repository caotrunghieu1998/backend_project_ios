<?php

namespace App\SupportFunction;

class SupportFunction
{
   //Get URL Sever
   public static function get_url_sever()
   {
       $server_name = $_SERVER['SERVER_NAME'];

       if (!in_array($_SERVER['SERVER_PORT'], [80, 443])) {
           $port = ":$_SERVER[SERVER_PORT]";
       } else {
           $port = '';
       }

       if (!empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')) {
           $scheme = 'https';
       } else {
           $scheme = 'http';
       }
       return $scheme . '://' . $server_name . $port;
   }

   // Get datetime Viet Nam Now
   public static function getDatetimeVietNamNow()
   {
       // Get date
       date_default_timezone_set('Asia/Ho_Chi_Minh');
       return date('Y/m/d H:i:s', time());
   }
}
