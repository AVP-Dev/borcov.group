<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class AdminController extends Controller
{
    public function actionSetPassword(string $password): int
    {
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

    public function actionCheckPassword(string $password): int
    {
        $db = Yii::$app->db;
        echo "DB DSN: {$db->dsn}\n";

        $envPass = getenv('ADMIN_PASSWORD');
        echo "ADMIN_PASSWORD env: [" . ($envPass !== false ? $envPass : 'NOT SET') . "]\n";
        echo "ADMIN_PASSWORD length: " . ($envPass !== false ? strlen($envPass) : 0) . "\n";
        echo "ADMIN_PASSWORD hex: " . ($envPass !== false ? bin2hex($envPass) : 'N/A') . "\n";

        $user = User::findByUsername('admin');

        if ($user === null) {
            echo "ERROR: Admin user not found in database.\n";
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $hash = $user->password_hash;
        echo "Stored hash: {$hash}\n";
        echo "Hash length: " . strlen($hash) . " chars\n";

        echo "Testing password (" . strlen($password) . " chars): [{$password}]\n";
        echo "Hex: " . bin2hex($password) . "\n";

        if ($user->validatePassword($password)) {
            echo "RESULT: PASSWORD MATCHES!\n";
            return ExitCode::OK;
        }

        echo "RESULT: Password does NOT match.\n";

        echo "\nTrying with trimmed password:\n";
        $trimmed = trim($password);
        if ($trimmed !== $password) {
            echo "Trimmed (" . strlen($trimmed) . " chars): [{$trimmed}]\n";
            if ($user->validatePassword($trimmed)) {
                echo "RESULT: TRIMMED PASSWORD MATCHES! (original had whitespace)\n";
                return ExitCode::OK;
            }
        } else {
            echo "(no difference with trim)\n";
        }

        return ExitCode::UNSPECIFIED_ERROR;
    }
}
