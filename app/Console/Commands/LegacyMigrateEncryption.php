<?php

namespace App\Console\Commands;

use App\Models\Legacy\ChatMessage;
use App\Models\Legacy\Expenses;
use forms\chat\ChatHandler;
use forms\projekte\auslagen\AuslagenHandler2;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
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
        if (! isset($_ENV['CHAT_PRIVATE_KEY'], $_ENV['CHAT_PUBLIC_KEY'], $_ENV['IBAN_SECRET_KEY'])) {
            $this->error('Please set chat private key and public key / IBAN_SECRET_KEY');

            return self::FAILURE;
        }
        DB::transaction(function () {

            $messages = ChatMessage::all();
            $count = 0;
            $messages->each(function ($message) use (&$count) {
                $text = $message->text;
                $this->info('Migrate Chat-Id:'.$message->id);
                if (! empty($text)) {
                    if (str_starts_with($message->text, '$enc$')) {
                        $text = substr($text, strlen('$enc$'));
                        $text = ChatHandler::legacyDecryptMessage($text, config('app.chat.private_key'));
                    } elseif ($message->type == -1) {
                        // $text = ChatHandler::legacyDecryptMessage($text, config('app.chat.private_key'));
                    }
                }
                $message->text = \Crypt::encryptString($text);
                $message->save();
            });
            $this->info('Migrated chat messages from legacy encryption to laravel integrated');

            $count = 0;
            Expenses::all()->each(function ($expense) use (&$count) {
                /** @var Model $expense */
                $cryptIban = $expense->get('zahlung-iban');
                $iban = AuslagenHandler2::legacyDecryptStr($cryptIban ?? '');
                $expense->update(['zahlung-iban' => \Crypt::encryptString($iban)]);
                $expense->etag = \Str::random(32);
                $expense->save();
                $count++;
            });

            $this->info("Migrated $count IBANs from legacy encryption to laravel integrated");

        });

        $this->info('You can now delete / comment CHAT_PRIVATE_KEY, CHAT_PUBLIC_KEY in your environment file');

        return self::SUCCESS;
    }
}
