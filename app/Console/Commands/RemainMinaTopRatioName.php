<?php

namespace App\Console\Commands;

use App\Models\MinaInfo;
use App\Services\MinaInfoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RemainMinaTopRatioName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remain_mina_top_ration_name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新用户最高比例';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        Log::info('----开始检查用户比例----');
        echo date('Y-m-d H:i:s') . '----开始检查用户比例----', PHP_EOL;
        $mina_ids = MinaInfo::select(['id','top_age_ratio_name','top_sex_ratio_name','top_mobile_ratio_name','updated_at'])->where('audit_type',1)->get()->toArray();
        $change_num = 0;
        foreach ($mina_ids as $k => $v){
            $info = MinaInfoService::topMinaType($v['id'],1,1,1);
            $updateData = [];
            $change = false;
            if(!empty($info['sex']) && ($info['sex'][0]['ratio_name'] != $v['top_sex_ratio_name'])){
                $updateData['top_sex_ratio_name'] = $info['sex'][0]['ratio_name'];
                $change = true;
            }
            if(!empty($info['age']) && ($info['age'][0]['ratio_name'] != $v['top_age_ratio_name'])){
                $updateData['top_age_ratio_name'] = $info['age'][0]['ratio_name'];
                $change = true;
            }
            if(!empty($info['mobile']) && ($info['mobile'][0]['ratio_name'] != $v['top_mobile_ratio_name'])){
                $updateData['top_mobile_ratio_name'] = $info['mobile'][0]['ratio_name'];
                $change = true;
            }
            if($change){
                $updateData['updated_at'] = $v['updated_at'];
                $minaInfo = new MinaInfo();
                $minaInfo->where('id',$v['id'])->update($updateData);
                $change_num += 1;
            }
        }
        Log::info('----检查用户比例成功，修改了'.$change_num.'条数据----');
        echo date('Y-m-d H:i:s') . '----检查用户比例成功，更新了'.$change_num.'条数据----', PHP_EOL;
    }
}
