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
 * @property Konto $finanzformularKontoType
 * @property-read \App\Models\Legacy\Konto $konto
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereCustomerRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereEmpfBic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereEmpfIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereEmpfName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereGvcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereKontoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction wherePrimanota($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereSaldo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereValuta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KontoTransaction whereZweck($value)
 * @mixin \Eloquent
 */
class KontoTransaction extends Model
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
        return $this->belongsTo(Konto::class, 'konto_id');
    }

    public function getLabels()
    {
        $labels = [];
        $attributes = $this->getFillable(); // $attributes = self::getFillable();

        foreach($attributes as $attribute)
        {
            $labels[$attribute] = 'label.konto.transaction.'.$attribute;
        }

        return $labels;
    }
}
