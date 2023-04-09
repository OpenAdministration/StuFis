<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
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
}
