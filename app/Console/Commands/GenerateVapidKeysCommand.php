<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeysCommand extends Command
{
    protected $signature   = 'webpush:vapid
                              {--force : Ghi đè nếu key đã tồn tại trong .env}';

    protected $description = 'Tạo cặp VAPID keys mới và ghi vào .env';

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->error('.env file không tồn tại.');
            return self::FAILURE;
        }

        $envContent = file_get_contents($envPath);
        $hasKeys    = str_contains($envContent, 'VAPID_PUBLIC_KEY=')
                   && str_contains($envContent, 'VAPID_PRIVATE_KEY=');

        if ($hasKeys && !$this->option('force')) {
            // Check if they are already populated (not empty)
            preg_match('/VAPID_PUBLIC_KEY=(.+)/', $envContent, $pubMatch);
            preg_match('/VAPID_PRIVATE_KEY=(.+)/', $envContent, $privMatch);

            $pubFilled  = !empty(trim($pubMatch[1] ?? ''));
            $privFilled = !empty(trim($privMatch[1] ?? ''));

            if ($pubFilled && $privFilled) {
                $this->warn('VAPID keys đã tồn tại. Dùng --force để ghi đè.');
                return self::SUCCESS;
            }
        }

        $keys = VAPID::createVapidKeys();
        $pub  = $keys['publicKey'];
        $priv = $keys['privateKey'];

        $envContent = $this->setEnvValue($envContent, 'VAPID_PUBLIC_KEY',  $pub);
        $envContent = $this->setEnvValue($envContent, 'VAPID_PRIVATE_KEY', $priv);

        if (!str_contains($envContent, 'VAPID_SUBJECT=')) {
            $envContent .= "\nVAPID_SUBJECT=mailto:" . config('mail.from.address', 'admin@example.com') . "\n";
        }

        file_put_contents($envPath, $envContent);

        $this->info('VAPID keys đã được tạo và lưu vào .env:');
        $this->line("  VAPID_PUBLIC_KEY={$pub}");
        $this->line("  VAPID_PRIVATE_KEY={$priv}");
        $this->newLine();
        $this->comment('Thêm VAPID_PUBLIC_KEY vào .env.example (không ghi VAPID_PRIVATE_KEY).');

        return self::SUCCESS;
    }

    private function setEnvValue(string $content, string $key, string $value): string
    {
        if (str_contains($content, "{$key}=")) {
            return preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        }

        return $content . "\n{$key}={$value}";
    }
}
