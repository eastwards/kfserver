<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id) {
        // 向当前client_id发送数据 
        //Gateway::sendToClient($client_id, "Hello $client_id\n");
        // 向所有人发送
        //Gateway::sendToAll("$client_id login\n");
        //error_log(print_r($_SERVER,1),3,'server1.log');
    }
    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message) {
        // 向所有人发送 
        //Gateway::sendToAll("$client_id said $message");
        /*
        error_log(print_r(json_decode($message,1),1),3,'123.log');
        Array(
                [type] => init
                [data] => Array
                    (
                        [id] => 1489388740036
                        [web_id] => 123
                    )

            )
        */

        $msg = json_decode($message,1);
        error_log(print_r($msg,1),3,'server2.log');
        switch ($msg['type']) {
          case 'init':
            //$_SESSION['kf_id']  = '123';
            $_SESSION['id']     = $msg['data']['id'];
            $_SESSION['kf_id']  = $msg['data']['web_id'];

            $kf_client_id = current( Gateway::getClientIdByUid($msg['data']['web_id']) );
            $data = [
                'message_type'=>'online', 
                'id' => $msg['data']['id'],
            ];

            Gateway::sendToClient($kf_client_id, json_encode($data));

            $kf = [
                'username'  => '客服123',
                'id'        => $msg['data']['web_id'],
                'avator'    => 'http://tp1.sinaimg.cn/5619439268/180/40030060651/1'
            ];
            Gateway::bindUid($client_id, $msg['data']['id']);
            Gateway::sendToCurrentClient(json_encode(['message_type'=>'init','kefu_info'=>$kf]));
            // error_log(print_r($msg,1),3,'init.log');
            // error_log(print_r($client_id,1),3,'init_client_id.log');
            break;
          case 'kefu_init':
            Gateway::bindUid($client_id, $msg['data']['web_id']);
            Gateway::sendToCurrentClient(json_encode(['message_type'=>'init']));
            // error_log(print_r($msg,1),3,'kefu_init.log');
            // error_log(print_r($client_id,1),3,'kefu_init_client_id.log');
            break;
          case 'sendMessage':
            $kf_id    = $msg['data']['to']['id'];
            $content  = htmlspecialchars( $msg['data']['mine']['content'] );
            $username = $msg['data']['mine']['username'];
            $avatar   = $msg['data']['mine']['avatar'];
            $mine_id  = $msg['data']['mine']['id'];

            $_SESSION['username']     = $msg['data']['mine']['username'];
            $_SESSION['avatar']     = $msg['data']['mine']['avatar'];

            $data = [
                'message_type'=>'chatMessage', 
                'data'=>[
                    'content'   => $content,
                    'type'      => '',
                    'id'        => $mine_id, 
                    'name'      => $username.$mine_id,
                    'username'  => $username.$mine_id,
                    'timestamp' => time() * 1000,
                    'avatar'    => $avatar,
                ]
            ];
            $kf_client_id = current( Gateway::getClientIdByUid($kf_id) );            

            if ( $kf_client_id ) {
                Gateway::sendToClient($kf_client_id, json_encode($data));
            }else{
                //add history 未上线，待推送

                // $data = [
                //     'message_type'=>'chatMessage', 
                //     'data'=>[
                //         'content'   => '对方已下线',
                //         'type'      => 'system',
                //         'id'        => $mine_id,
                //         'name'      => $username.$mine_id,
                //         'username'  => $username.$mine_id,
                //         'timestamp' => time() * 1000,
                //         'avatar'    => $avatar,
                //     ]
                // ];
                // //推送信息给当前用户提示对方已下线
                // Gateway::sendToCurrentClient(json_encode($data));
            }
            // error_log(print_r($msg,1),3,'sendMessage.log');
            // error_log(print_r($client_id,1),3,'sendMessage_client_id.log');
            // error_log(print_r($kf_client_id,1),3,'sendMessage_kf_client_id.log');
            break;
          case 'kefuSendMessage':
            $kh_id    = $msg['data']['to']['id'];
            $content  = htmlspecialchars( $msg['data']['mine']['content'] );
            $username = $msg['data']['mine']['username'];
            $avatar   = $msg['data']['mine']['avatar'];
            $mine_id  = $msg['data']['mine']['id'];

            $data = [
                'message_type'=>'chatMessage', 
                'data'=>[
                    'content'   => $content,
                    'type'      => 'kefu',
                    'id'        => $mine_id,
                    'name'      => $username,
                    'username'  => $username,
                    'timestamp' => time() * 1000,
                    'avatar'    => $avatar,
                ]
            ];
            $kh_client_id = current( Gateway::getClientIdByUid($kh_id) );
            if ( $kh_client_id ) {
                Gateway::sendToClient($kh_client_id, json_encode($data));
            }else{
                //add history 未上线，待推送

                // $data = [
                //     'message_type'=>'chatMessage', 
                //     'data'=>[
                //         'content'   => '对方已下线',
                //         'type'      => 'system',
                //         'id'        => $mine_id,
                //         'name'      => $username,
                //         'username'  => $username,
                //         'timestamp' => time() * 1000,
                //         'avatar'    => $avatar,
                //     ]
                // ];
                // //推送信息给当前用户提示对方已下线
                // Gateway::sendToCurrentClient(json_encode($data));
            }

            // error_log(print_r($msg,1),3,'kefuSendMessage.log');
            // error_log(print_r($client_id,1),3,'kefuSendMessage_client_id.log');
            // error_log(print_r($kh_client_id,1),3,'kefuSendMessage_kh_client_id.log');
            break;
          case 'chatMessage':
            
            break;
        }

   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id) {
       // 向所有人发送 
       //GateWay::sendToAll("$client_id logout");
       // error_log(print_r($_SERVER,1),3,'server3.log');

        $id         = $_SESSION['id'];
        $kf_id      = $_SESSION['kf_id'];

        if ( empty($kf_id) ) return ;

        $kf_client_id = current( Gateway::getClientIdByUid($kf_id) );
        $data = [
            'message_type'=>'logout', 
            'id' => $id,
        ];

        Gateway::sendToClient($kf_client_id, json_encode($data));

   }
}
