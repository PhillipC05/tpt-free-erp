<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:generate-vapid';

    protected $description = 'Generate VAPID keys for Web Push notifications';

    public function handle(): int
    {
        $keyPair = sodium_crypto_vapid_keypair();

        $publicKey = sodium_bin2base64(
            substr($keyPair, 0, SODIUM_CRYPTO_VAPID_PUBLICKEYBYTES),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
        );

        $privateKey = sodium_bin2base64(
            substr($keyPair, SODIUM_CRYPTO_VAPID_PUBLICKEYBYTES),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
        );

        $this->newLine();
        $this->info('VAPID Keys Generated:');
        $this->newLine();
        $this->table(['Key', 'Value'], [
            ['Public Key', $publicKey],
            ['Private Key', $privateKey],
        ]);
        $this->newLine();

        $this->warn('Add these to your .env:');
        $this->line("VAPID_PUBLIC_KEY={$publicKey}");
        $this->line("VAPID_PRIVATE_KEY={$privateKey}");

        return Command::SUCCESS;
    }
}
