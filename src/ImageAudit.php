<?php

use Qcloud\Cos\Client;

class ImageAudit
{
    public $cosClient;

    public function __construct($secretId, $secretKey, $region)
    {
        $this->cosClient = new Client([
            'region' => $region,
            'schema' => 'https',
            'credentials' => [
                'secretId' => $secretId,
                'secretKey' => $secretKey
            ]
        ]);
    }

    /**
     * Notes: 图片校验
     * User: 戎飞
     * Date: 2023/6/30
     * Time: 16:42
     * @param $urls
     * @return bool
     */
    public function audit ($urls, $address, $bucket, $bizType)
    {
        //图片违规/问题标识：1-图片链接或三方检测出现问题，2-图片违规，3-正常
        $isViolation = 3;
        $inputs = array_map(function ($url) use ($address){
            return ["Url" => $address . $url];
        }, $urls);

        //图片校验
        $result = $this->cosClient->detectImages([
            'Bucket' => $bucket,
            'Inputs' => $inputs,
            'Conf' => ['BizType' => $bizType]
        ])->toArray();

        //判断图片是否合规
        foreach ($result['JobsDetail'] as $value){
            if (isset($value['Code']) || array_sum(array_column($value, 'Code')) > 0){
                $isViolation = 1;
                break;
            }
            $HitFlags = array_column($value, 'HitFlag');
            if (array_search(1, $HitFlags) || array_search(2, $HitFlags)){
                $isViolation = 2;
                break;
            }
        }
        return $isViolation;
    }
}