<?php
return [
    'shutter_path' => '/usr/bin/shutter',
    'upload_url' => 'https://yourdomain.com/shutter?key=' . md5('mysecretkeygoeshere'),
    'success_cmd' => 'nohup chromium-browser %s 2>&1 > /dev/null'
];
