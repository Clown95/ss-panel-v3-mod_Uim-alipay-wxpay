<?php


namespace App\Command;

use App\Models\User;
use App\Models\Ann;
use App\Services\Config;
use App\Services\Mail;
use App\Utils\Telegram;
use App\Utils\Tools;
use App\Services\Analytics;

class DailyMail
{
    public static function sendDailyMail()
    {
        $users = User::all();
        $logs = Ann::orderBy('id', 'desc')->get();
        $text1="";
        
        foreach ($logs as $log) {
            if (strpos($log->content, "Links")===false) {
                $text1=$text1.$log->content."<br><br>";
            }
        }
        
        $lastday_total = 0;
        
        foreach ($users as $user) {
            $lastday = (($user->u+$user->d)-$user->last_day_t)/1024/1024;
            $lastday_total += (($user->u+$user->d)-$user->last_day_t);
            
            if ($user->sendDailyMail==1) {
                echo "Send daily mail to user: ".$user->id;
                $subject = Config::get('appName')."-每日流量报告以及公告";
                $to = $user->email;
                $text = "下面是系统中目前的公告:<br><br>".$text1."<br><br>晚安！";
                
                try {
                    Mail::send($to, $subject, 'news/daily-traffic-report.tpl', [
                        "user" => $user,"text" => $text,"lastday"=>$lastday
                    ], [
                    ]);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
                $text="";
            }
        }
        
        $sts = new Analytics();
        
        Telegram::Send("呀嘿~报告下今天的使用情况！".
        PHP_EOL.
        "有".$sts->getTodayCheckinUser()."位用户签到啦".PHP_EOL.
        "今天用了".Tools::flowAutoShow($lastday_total)."的流量~".PHP_EOL.
        "早睡早起身体好！晚安喵~"
        );
    }


    public static function reall()
    {
        $users = User::all();
        foreach ($users as $user) {
            $user->last_day_t=($user->u+$user->d);
            $user->save();
        }
    }
}
