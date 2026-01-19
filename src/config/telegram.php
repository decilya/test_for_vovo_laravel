<?php

return [
    'enabled' => env('TELEGRAM_ENABLED', false),
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'security_chat_id' => env('TELEGRAM_SECURITY_CHAT_ID'),
    'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
];
