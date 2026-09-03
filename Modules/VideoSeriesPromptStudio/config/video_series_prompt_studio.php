<?php

return [
    'name' => 'VideoSeriesPromptStudio',

    // "socialmediaexaminer.com/creating-a-video-series-why-your-business-needs-one" — dàn ý Content
    // Arc cho 5-10 tập đầu tiên.
    'content_arc' => [
        'default_episode_count' => 5,
        'min_episode_count' => 5,
        'max_episode_count' => 10,
    ],

    // Nhịp độ dựng 1 tập khác hẳn nhau giữa video ngắn dọc và video dài ngang — không xác định rõ
    // nền tảng, AI dễ trả về khung thời lượng lấp lửng không dùng được ngay để quay.
    'platform' => [
        'default' => 'short_form',
        'options' => [
            'short_form' => [
                'label' => 'Video ngắn dọc (TikTok/Reels/YouTube Shorts)',
                'duration_hint' => 'tổng thời lượng khoảng 30-60 giây/tập',
            ],
            'long_form' => [
                'label' => 'Video dài ngang (YouTube)',
                'duration_hint' => 'tổng thời lượng khoảng 8-15 phút/tập',
            ],
        ],
    ],
];
