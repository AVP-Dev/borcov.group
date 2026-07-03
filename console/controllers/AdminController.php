<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class AdminController extends Controller
{
    public function actionSetPassword(string $password = ''): int
    {
        if ($password === '') {
            $password = getenv('ADMIN_PASSWORD');
        }
        if (empty($password)) {
            echo "ERROR: No password provided via CLI argument or ADMIN_PASSWORD env.\n";
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $user = User::findByUsername('admin');

        if ($user === null) {
            $user = new User();
            $user->username = 'admin';
            $user->email = 'admin@example.com';
            $user->status = User::STATUS_ACTIVE;
        }

        $user->setPassword($password);
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();

        if ($user->save()) {
            echo "Admin user password set successfully.\n";
            return ExitCode::OK;
        }

        echo "Failed to save admin user:\n";
        foreach ($user->errors as $attr => $errors) {
            echo "  - $attr: " . implode('; ', $errors) . "\n";
        }
        return ExitCode::UNSPECIFIED_ERROR;
    }

}
