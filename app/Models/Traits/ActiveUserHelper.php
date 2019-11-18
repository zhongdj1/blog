<?php


namespace App\Models\Traits;


use App\Models\Reply;
use App\Models\Topic;
use Carbon\Carbon;
use Illuminate\Support\Arr;

trait ActiveUserHelper
{
    // 存放用户数据
    protected $users = [];

    // 配置信息
    protected $topic_weight;
    protected $reply_weight;
    protected $pass_days; // 多少天内发表过内容
    protected $user_number;

    // 缓存相关配置
    protected $cache_key;
    protected $cache_expire_in_seconds;

    public function __construct()
    {
        $this->topic_weight = config('active-user.topic_weight');
        $this->reply_weight = config('active-user.reply_weight');
        $this->pass_days = config('active-user.pass_days');
        $this->user_number = config('active-user.user_number');
        $this->cache_key = config('active-user.cache_key');
        $this->cache_expire_in_seconds = config('active-user.cache_expire_in_seconds');
    }

    public function getActiveUsers()
    {
        return \Cache::remember($this->cache_key, $this->cache_expire_in_seconds, function () {
            return $this->calculateActiveUsers();
        });
    }

    public function calculateAndCacheActiveUsers()
    {
        $active_users = $this->calculateActiveUsers();
        $this->cacheActiveUsers($active_users);
    }

    private function calculateActiveUsers()
    {
        $this->calculateTopicScore();
        $this->calculateReplyScore();

        // 按照得分排序
        $users = Arr::sort($this->users, function ($user) {
            return $user['score'];
        });

        $users = array_slice($users, 0, $this->user_number, true);

        $active_users = collect();

        foreach ($users as $user_id => $user) {
            $user = $this->find($user_id);

            if ($user) {
                $active_users->push($user);
            }
        }

        return $active_users;
    }

    private function calculateTopicScore()
    {
        // 查找pass_days时间范围内的有发表过话题的用户与其发表话题的数量
        $topic_users = Topic::query()->selectRaw('user_id, count(*) as topic_count')
            ->where('created_at', '>=', Carbon::now()->subDays($this->pass_days))
            ->groupBy('user_id')
            ->get();

        // 根据话题数量计算得分
        foreach ($topic_users as $value) {
            $this->users[$value->user_id['score']] = $value->topic_count * $this->topic_weight;
        }
    }

    private function calculateReplyScore()
    {
        $reply_users = Reply::query()->selectRaw('user_id, count(*) as reply_count')
            ->where('created_at', '>=', Carbon::now()->subDays($this->pass_days))
            ->groupBy('user_id')
            ->get();

        foreach ($reply_users as $value) {
            $reply_score = $value->reply_count * $this->reply_weight;
            if (isset($this->users[$value->user_id])) {
                $this->users[$value->user_id]['score'] += $reply_score;
            } else {
                $this->users[$value->user_id]['score'] = $reply_score;
            }
        }
    }

    // 缓存活跃用户
    private function cacheActiveUsers($active_users)
    {
        \Cache::put($this->cache_key, $active_users, $this->cache_expire_in_seconds);
    }
}
