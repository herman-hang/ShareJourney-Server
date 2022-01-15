<?php
/**
 * FileName: 滑动验证码
 * Description: 处理滑动验证码业务逻辑
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-15 11:37
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


use Fastknife\Exception\ParamException;
use Fastknife\Service\BlockPuzzleCaptchaService;
use Fastknife\Service\ClickWordCaptchaService;
use think\exception\HttpResponseException;
use think\facade\Validate;
use think\Response;

class CaptchaController
{
    /**
     * 获取验证码
     */
    public function get()
    {
        try {
            $service = $this->getCaptchaService();
            $data = $service->get();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success($data);
    }

    /**
     * 一次验证
     */
    public function check()
    {
        $data = request()->post();
        try {
            $this->validate($data);
            $service = $this->getCaptchaService();
            $service->check($data['token'], $data['pointJson']);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success([]);
    }

    /**
     * 二次验证
     */
    public function verification()
    {
        $data = request()->post();
        try {
            $this->validate($data);
            $service = $this->getCaptchaService();
            $service->verification($data['token'], $data['pointJson']);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success([]);
    }

    /**
     * 获取验证码相关服务
     * @return BlockPuzzleCaptchaService|ClickWordCaptchaService
     */
    protected function getCaptchaService()
    {
        $captchaType = request()->post('captchaType', null);
        $config = config('captcha');
        switch ($captchaType) {
            case "clickWord":
                $service = new ClickWordCaptchaService($config);
                break;
            case "blockPuzzle":
                $service = new BlockPuzzleCaptchaService($config);
                break;
            default:
                throw new ParamException('captchaType参数不正确！');
        }
        return $service;
    }

    /**
     * 验证器
     * @param $data
     */
    protected function validate($data)
    {
        $rules = [
            'token' => ['require'],
            'pointJson' => ['require']
        ];
        $validate = Validate::rule($rules)->failException(true);
        $validate->check($data);
    }

    /**
     * 返回成功信息
     * @param $data
     */
    protected function success($data)
    {
        $response = [
            'error' => false,
            'repCode' => '0000',
            'repData' => $data,
            'repMsg' => null,
            'success' => true,
        ];
        throw new HttpResponseException(Response::create($response, 'json'));
    }

    /**
     * 返回失败信息
     * @param $msg
     */
    protected function error($msg)
    {
        $response = [
            'error' => true,
            'repCode' => '6111',
            'repData' => null,
            'repMsg' => $msg,
            'success' => false,
        ];
        throw new HttpResponseException(Response::create($response, 'json'));
    }
}