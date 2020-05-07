<?php
/**+----------------------------------------------------------------------
 * JamesPi Redis [php-redis]
 * +----------------------------------------------------------------------
 * RabbitMQ Common Service Configuration file
 * +----------------------------------------------------------------------
 * Copyright (c) 2020-2030 http://www.pijianzhong.com All rights reserved.
 * +----------------------------------------------------------------------
 * Author：PiJianZhong <jianzhongpi@163.com>
 * +----------------------------------------------------------------------
 */

namespace Jamespi\RabbitMQ\Common;


class Common
{
    /**
     * 返回信息方法
     * @param string $status
     * @param string $msg
     * @param array $data
     * @return object
     */
    public static function resultMsg(string $status, string $msg, array $data = []):string
    {
        if (empty($data)){
            $result = ['status'=>$status, 'msg'=>$msg];
        }else{
            $result = ['status'=>$status, 'msg'=>$msg, 'data'=>$data];
        }
        switch ($status){
            case 'success':
                $result = json_encode($result);
                break;
            case 'failed':
                $result['msg'] = 'Error:'.$msg;
                $result = json_encode($result);
                break;
            default:
                $result = json_encode($result);
                break;
        }

        return $result;
    }
}