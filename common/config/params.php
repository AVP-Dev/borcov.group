<?php

declare(strict_types=1);

return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,

    // DeepSeek API
    'deepseekApiKey' => getenv('DEEPSEEK_API_KEY') ?: '',

    // Pipeline
    'pipeline.volume.min' => 10,
    'pipeline.volume.min_source_count' => 3,
    'pipeline.dedup.similarity_threshold' => 0.6,

    // Ad generation config
    'adGeneration' => require __DIR__ . '/ad_generation.php',
];
