<?php

namespace App\Console\Commands;

use App\Models\Legacy\ChatMessage;
use App\Models\Legacy\Expenses;
use forms\projekte\auslagen\AuslagenHandler2;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LegacyMigrateEncryption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:migrate-encryption';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates chat encryption from legacy ssl to laravel integrated';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!isset($_ENV['CHAT_PRIVATE_KEY'], $_ENV['CHAT_PUBLIC_KEY'], $_ENV['IBAN_SECRET_KEY'])){
            $this->error('Please set chat private key and public key / IBAN_SECRET_KEY');
            return self::FAILURE;
        }
        DB::transaction(function () {
            $messages = ChatMessage::all();
            $count = 0;
            $messages->each(function ($message) use ($count) {
                if(!str_starts_with($message->text, '$lara$')){
                    $count += 1;
                    $text = ltrim($message->text, '$enc$');
                    $laraEncText = \Crypt::encryptString($text);
                    $message->text = $laraEncText;
                    $message->save();
                }
            });
            $this->info("Migrated $count chat messages from legacy encryption to laravel integrated");

            $count = 0;
            Expenses::all()->each(function ($expense) use ($count) {
                $cryptIban = $expense->zahlung_iban;
                $iban = AuslagenHandler2::legacyDecryptStr($cryptIban);
                $expense->zahlung_iban = \Crypt::encryptString($iban);
                $expense->save();
                $count += 1;
            });

            $this->info("Migrated $count IBANs from legacy encryption to laravel integrated");
        });

        $this->info("You can now delete / comment CHAT_PRIVATE_KEY, CHAT_PUBLIC_KEY in your environment file");

        return self::SUCCESS;
    }
}
