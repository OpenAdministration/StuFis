<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\KontoTransaction
 *
 * @property integer $id
 * @property integer $konto_id
 * @property string $date
 * @property string $valuta
 * @property string $type
 * @property string $empf_iban
 * @property string $empf_bic
 * @property string $empf_name
 * @property float $primanota
 * @property float $value
 * @property float $saldo
 * @property string $zweck
 * @property string $comment
 * @property integer $gvcode
 * @property string $customer_ref
 * @property BankAccount $finanzformularKontoType
 * @property-read \App\Models\Legacy\BankAccount $konto
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereCustomerRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereEmpfBic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereEmpfIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereEmpfName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereGvcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereKontoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction wherePrimanota($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereSaldo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereValuta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction whereZweck($value)
 * @mixin \Eloquent
 */
class BankTransaction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'konto';

    /**
     * @var array
     */
    protected $fillable = ['date', 'valuta', 'type', 'empf_iban', 'empf_bic', 'empf_name', 'primanota', 'value', 'saldo', 'zweck', 'comment', 'gvcode', 'customer_ref'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function konto()
    {
        return $this->belongsTo(BankAccount::class, 'konto_id');
    }

    public function getLabels()
    {
        $labels = [];

        foreach($this->getFillable() as $attribute)
        {
            $labels[$attribute] = 'label.konto.transaction.'.$attribute;
        }

        return $labels;
    }
}
