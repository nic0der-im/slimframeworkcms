<?php

namespace App\Models\Caja;
use Illuminate\Database\Eloquent\Model;

use App\Models\Transacciones\Comprobante;
use App\Models\Transacciones\ComprobanteDetalle;
use App\Models\Transacciones\FacturaRecibo;

class Cuenta extends Model
{
	protected $table = 'tra_cuentacorriente';
	protected $fillable = [
		'nombre_cuenta',
		'tipo_cuenta', //1:CLIENTE 2:PROVEEDOR
		'estado',
		'id_provedor_cliente',
		'id_carpeta',
		'id_vehiculo',
		'id_sucursal',
	];

	public function cliente() {
		return $this->hasOne('App\Models\Negocio\Cliente', 'id', 'id_provedor_cliente');
	}

	public function proveedor() {
		return $this->hasOne('App\Models\Transacciones\Proveedor', 'id', 'id_provedor_cliente');
	}

	public function carpeta() {
		return $this->hasOne('App\Models\Negocio\Carpeta', 'id_cuentacorriente', 'id');
	}

	public function vehiculo() {
		return $this->hasOne('App\Models\Vehiculos\Vehiculo', 'id', 'id_vehiculo');
	}

	public function sucursal() {
		return $this->hasOne('App\Models\Transacciones\Sucursal', 'id', 'id_sucursal');
	}

  protected $appends = ['total','pagado'];

  public function getTotalAttribute(){
		$total = Comprobante::selectRaw('distinct tra_comprobantes.id')->where('tra_comprobantes.cuentacorriente',$this->id)
			->join('tra_comprobante_detalles','tra_comprobantes.id','tra_comprobante_detalles.id_comprobante')
			->leftJoin('tra_factura_recibo','tra_comprobante_detalles.id','tra_factura_recibo.id_factura')
			->whereNotNull('tra_factura_recibo.id_factura')
			->whereIn('tra_comprobantes.estado',[0,1])
			->whereIn('id_tipo_comprobante',[
				1,//	FACTURAS A
				4,//	RECIBOS A
				6,//	FACTURAS B
				9,//	RECIBOS B
				11,//	FACTURAS C
				15,//	RECIBOS C
				99//	RECIBO X
			])
			->sum('total');
    return $total;
  }

  public function getPagadoAttribute(){
		$pagado = Comprobante::selectRaw('distinct tra_comprobantes.id')->where('cuentacorriente',$this->id)
			->join('tra_comprobante_detalles','tra_comprobantes.id','tra_comprobante_detalles.id_comprobante')
			->leftJoin('tra_factura_recibo','tra_comprobante_detalles.id','tra_factura_recibo.id_recibo')
			->whereNotNull('tra_factura_recibo.id_recibo')
			->where('tra_comprobantes.estado',1)
			->where('id_tipo_comprobante',99)
			->sum('total');
    return $pagado;
  }
}
