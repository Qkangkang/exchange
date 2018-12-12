<?php

namespace App\Console\Commands;

use App\components\DbHelper;
use App\Http\Models\UserModel;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class RemainApplyCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remain_apply_count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每日重置每天剩余邀请次数';

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
     * @return $info
     *
     * @throws \Exception
     */
    public function handle()
    {
        Log::info('----开始重置每日剩余邀请次数----');
        echo date('Y-m-d H:i:s') . '----开始重置每日剩余邀请次数----', PHP_EOL;
        $info = User::updateApplyCount(config('minainfo.REMAIN_APPLY_COUNT'));
        Log::info('----重置每日剩余邀请次数成功----'.$info);
    }
}
