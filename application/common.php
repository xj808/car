<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
// 单图片上传
/**
 * @param  图片字段
 * @param  要保存的路径
 * @return 图片保存后的路径
 */
 function upload($image,$path){
    // 获取表单上传文件 例如上传了001.jpg
    $file = request()->file($image);
    // 移动到框架应用根目录/uploads/ 目录下
    $info = $file->move('./uploads/'.$path);
    if($info){
        // 成功上传后 获取上传信息
        // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
        return  './uploads/'.$path.$info->getSaveName();
    }else{
        // 上传失败获取错误信息
        return  $file->getError();
    }
}
/**
 * @return 用于JWTtoken 的key值
 */
function createKey(){
  return $key="Jx3T4w5%djLp1t#";
}

/*
 * 密码加密方式
 * @param string $ps 要加密的字符串
 * @return string 加密后的字符串
 * @author zhaizhaohui
 */
function getEncrypt($ps){
    return sha1(sha1('zm'.$ps));
}
/**
 * 密码比对
 * @param string $ps 要比较的密码
 * @param string $db_ps 数据库保存的已经加密过的密码
 * @return boolean 密码相同，返回true
 * @author zhaizhaohui
 */
function comparePassword($ps,$db_ps){
    return getEncrypt($ps) == $db_ps;
}
//数组转换字符串
/**
 * @param  数组
 * @param  选择的字段
 * @return 字符串
 */
function arrayStr($data,$key){
    $arr=array_column($data,$key);
    $str=implode(',',$arr);
    return $str;
}
/**
 *  无限极分类
 * @param  数组
 * @param  父级id
 * @return 树形数组
 */
  function getChild($data,$pid){
      $arr = array();
      foreach ($data as $key =>$v) {
          if ($v['pid']==$pid) {
              $son = getchild($data,$v['id']);
              if ($son){
                  $v["son"] = $son;
              }
             $arr[] = $v; 
          }
         
      }
     return $arr;
  };