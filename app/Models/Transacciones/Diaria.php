<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

use App\Models\Transacciones\Transaccion;
use App\Models\Transacciones\Movimiento;

class Diaria extends Model
{
	protected $table = 'tra_diarias';
	protected $fillable = [
		'id_sucursal',
		'fecha_inicio',
		'fecha_cierre',
		'saldo_anterior',
		'total_ingreso',
		'total_egreso',
		'saldo',
		'id_usuario',
		'estado',
	];

	public function sucursal(){
		return $this->hasOne('App\Models\Transacciones\Sucursal','id','id_sucursal');
	}

  protected $appends = ['ingresos','egresos'];

  public function getIngresosAttribute(){
  	if($this->fecha_cierre == null){
	  	$ingresos = Movimiento::where('estado',1)
				->where('updated_at','>=',$this->fecha_inicio)
				->where('tipo_ioe',0)
				->where('id_sucursal',$this->id_sucursal)
				->whereNotNull('fecha_cobro')
				->sum('monto');
  	} else {
  		$ingresos = $this->total_ingreso;
  	}
    return $ingresos;
  }

  public function getEgresosAttribute(){
  	if($this->fecha_cierre == null){
	  	$egresos  = Movimiento::where('estado',1)
				->where('updated_at','>=',$this->fecha_inicio)
				->where('tipo_ioe',1)
				->where('id_sucursal',$this->id_sucursal)
				->whereNotNull('fecha_cobro')
				->sum('monto');
  	} else {
  		$egresos = $this->total_egreso;
  	}
    return $egresos;
  }
}
