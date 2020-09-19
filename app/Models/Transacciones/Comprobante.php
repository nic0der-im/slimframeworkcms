<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Capsule\Manager as DB;

use App\Models\Transacciones\Comprobante;
use App\Models\Transacciones\ComprobanteDetalle;
use App\Models\Transacciones\FacturaRecibo;
/**
* Cabecera del comprobante, factura, recibo. Indicando el total
*
*/
class Comprobante extends Model
{
	protected $table = 'tra_comprobantes';
	protected $fillable = [
		'cae',
		'cae_vto',
		'ptovta',
		'cbtedesde',
		'cbtehasta',
		'numero',
		'id_proveedor',
		'id_cliente',
		'id_tipo_comprobante',
		'id_tipo_transaccion',
		'id_tipo_responsable',
		'facturar',
		'facturar_total',
		'id_tipo_documento',
		'documento_numero',
		'razon_social',
		'domicilio',
		'email',
		'fecha',
		'fecha_vto',
		'observaciones',
		'total',
		'bonificacion',
		'impuesto',
		'nogravado',
		'exento',
		'estado',
		'id_usuario',
		'id_sucursal',
		'tipo', //1 Factura 2 Remito	
		'produccion',
		'reproceso',
		'cuentacorriente',
		'periodo'
	];

	public function detalles() {
		return $this->hasMany('App\Models\Transacciones\ComprobanteDetalle', 'id_comprobante', 'id');
	}

	public function errores() {
		return $this->hasMany('App\Models\Transacciones\ComprobanteError', 'id_comprobante', 'id');
	}

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function cliente() {
		return $this->hasOne('App\Models\Negocio\Cliente', 'id', 'id_cliente');
	}

	public function tipoComprobante() {
		return $this->hasOne('App\Models\Transacciones\TipoComprobante', 'id', 'id_tipo_comprobante');
	}

	public function tipoResponsable() {
		return $this->hasOne('App\Models\Transacciones\TipoResponsable', 'id', 'id_tipo_responsable');
	}

	public function tipoDocumento() {
		return $this->hasOne('App\Models\Transacciones\TipoDocumento', 'id', 'id_tipo_documento');
	}

	public function tipoTransaccion()
	{
		return $this->hasOne('App\Models\Transacciones\TiposTransaccion','id','id_tipo_transaccion');
	}

	public function sucursal() {
		return $this->hasOne('App\Models\Transacciones\Sucursal', 'id', 'id_sucursal');
	}

  protected $appends = ['pagado','nota_credito'];

	public function getPagadoAttribute(){
		$detalles = ComprobanteDetalle::where('estado',1)->where('id_comprobante',$this['id'])->pluck('id')->toArray();
		$recibos = FacturaRecibo::where('estado',1)->whereIn('id_factura',$detalles)->pluck('id_recibo')->toArray();
		$totalPagado = ComprobanteDetalle::whereIn('id',$recibos)
			->where('estado',1)
			->whereHas('comprobante',function($q){
				$q->where('id_tipo_comprobante',99);
			})
			->sum('subtotal');
    return $totalPagado;  
  }

  public function getNotaCreditoAttribute(){
		$detalles = ComprobanteDetalle::where('estado',1)->where('id_comprobante',$this['id'])->pluck('id')->toArray();
		$recibos = FacturaRecibo::where('estado',1)->whereIn('id_factura',$detalles)->pluck('id_recibo')->toArray();
		$totalPagado = ComprobanteDetalle::whereIn('id',$recibos)
			->where('estado',1)
			->whereHas('comprobante',function($q){
				$q->whereIn('id_tipo_comprobante',[3,8,13]);
			})
			->sum('subtotal');
    return $totalPagado;  
  }

  /*
  public function getFacturasAttribute(){
  	$id_tipo_comprobante = $this['id_tipo_comprobante'];
  	$detalles = ComprobanteDetalle::where('estado',1)->where('id_comprobante',$this['id'])->pluck('id')->toArray();
		return Comprobante::whereIn('id',function($subQuery) use ($detalles,$id_tipo_comprobante){
			$subQuery = $subQuery->select('id_comprobante')->from('tra_comprobante_detalles');
			if($id_tipo_comprobante==99){
				
					$subQuery = $subQuery->join('tra_factura_recibo', 'tra_comprobante_detalles.id', '=', 'tra_factura_recibo.id_factura')
					->whereIn('tra_factura_recibo.id_recibo',$detalles);
			} else {
					$subQuery = $subQuery->join('tra_factura_recibo', 'tra_comprobante_detalles.id', '=', 'tra_factura_recibo.id_recibo')
					->whereIn('tra_factura_recibo.id_factura',$detalles);
				}

		})->where('estado',1)->get();
  }
  */

}