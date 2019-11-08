<?php


namespace App\Handlers;


use Illuminate\Support\Str;

class ImageUploadHandler
{
    //只允许以下后缀名的图片文件上传
    protected $allowed_ext = ['png', 'jpg', 'gif', 'jpeg'];

    public function save($file, $folder, $file_prefix)
    {
        // 构建存储的文件夹规则，例如：uploads/images/avatars/201910/12/
        // 文件夹切割能让查找效率更高
        $folder_name = "uploads/images/$folder/" . date('Ym/d', time());

        // 文件具体存储的物理路径，public_path()获取的是public文件夹的物理路径。
        $upload_path = public_path() . '/' . $folder_name;

        // 获取文件的后缀名
        $ext = strtolower($file->getClientOriginalExtension()) ?: 'png';

        //拼接文件名
        $filename = $file_prefix . '_' . time() . '_' . Str::random(10) . '.' . $ext;

        //如果不是图片则终止操作
        if (!in_array($ext, $this->allowed_ext)) {
            return false;
        }

        // 将图片移动到我们的目标存储路径中
        $file->move($upload_path, $filename);

        return [
            'path' => config('app.url') . "/$folder_name/$filename"
        ];
    }
}
