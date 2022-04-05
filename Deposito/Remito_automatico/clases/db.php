<?
#objetos de base de datos
#by pablo f. savino
#version 1.0
#creado 09-03-08
#
#clase para el manejo de querys e informacion de funcionamiento del sitio.





class db
{

	var $esta_conectado=0;				#true o false en la coneccion
	var $query_a_ejecutar = array();	#prepara el arrays de querys para ejecutarlos en una transaccion.
	var $idx_query=0;					#controla el array de query_a_ejecutar.
	var $cnt_registros=0;				#cantidad de registros afectados.
	
	var $msg='';						#variable para mensajes
	var $errno=0;
	
	#Configuracion para 2 dbs
	var $servernum=1;
	var $host= array(
					1=>array('srv'=>'localhost',
							 'user'=>'root',
							 'pass'=>'nolase',
							 'db'=>'reparacioneshogar'),
					2=>array(
							 'srv'=>'181.30.3.226',
							 'user'=>'root',
							 'pass'=>'nooknn',
							 'db'=>'proyecto_full_data')		 
					);

	####################################################################################################################
	#funcion que devuelve la variable de mensages
	####################################################################################################################
	function mensaje()
	{
		return $this->msg;
	}

	function errnumero()
	{
		return $this->errno;
	}
	
	#Cambia de server
	function cambia_server($id=1)
	{
		$this->db_onoff('');
		$this->servernum=$id;
		}
		
	####################################################################################################################
	#funcion para coneccion y desconeccion de la db
	####################################################################################################################
	function db_onoff($tipo='on')
	{
		/*$host="181.30.3.226";
		$user="fulldata"; 
		$pass="fulldata";*/
		
		
		#vemos si estamos conectados
		if($tipo=='on')
		{
			#revisamos si antes nos conectamos
			if($this->esta_conectado==1){return true;}
		}
		
		
		switch($tipo)
			{
				case 'on':
				
					if (!$this->esta_conectado = mysql_connect($this->host[$this->servernum]['srv'], 
																$this->host[$this->servernum]['user'], 
																$this->host[$this->servernum]['pass'])){
						$this->msg=mysql_errno();						
						return false;
					}
				
					if (!$this->esta_conectado = mysql_select_db($this->host[$this->servernum]['db'])){
						$this->msg=mysql_error();													
						return false;
					}
					break;
				default:
					@mysql_close();	
					$this->esta_conectado=0;
			}
			
		return true;		
	}
	
	####################################################################################################################
	#funcion para insertar,actualizar
		#tabla = nombre de tabla.
		#datos = datos a insertar por orden segun la estructura de la tabla.
	####################################################################################################################
	function creaquery($tabla='',$tipo='', $datos = array())
	{
		if($tabla==''){return false;}	
		
		#determinamos si la cantidad de datos es igual a la estructura de la tabla
		#solo valido esto cuando es un insert.
		switch($tipo)
		{
			case 'insert':
				#si $datos no es un array entonces nos vamos
				if(!is_array($datos)){return false;}
				#buscamos la definicion de estructura de la tabla
				if(!$this->tbl_estructura($tabla,$ddl))
				{					
					return false;
				}
				
				if(count($datos)!=@mysql_num_rows($ddl))
				{
					#cierro conexion a la base de datos					
					if(!$this->db_onoff('')){return false;}										
					return false;
				}
				
				#preparamos el query
				$query="insert into $tabla values (";			
				break;
			case 'update':			
				$query="update $tabla set ";
				#desarmamos el array para sacar los valores de los campos que se asignan.
				foreach($datos as $key=>$d)
				{
					$query.=$d;
				}				
				break;
			case 'delete':
				break;
			case 'select':
				break;
			default:
				return false;
		}	


		#armamos la sentencia sql
		#valor = es el retorno del dato ya preparado.
		$fetch_campo_idx=0;
		if($tipo=='insert')
		{
				foreach($datos as $key=>$d)
				{				
					if(!$this->tipo_campo(mysql_result($ddl,$fetch_campo_idx,1),$d,$valor))
					{					
						if(!$this->db_onoff('')){}
						$this->inicializar();				
						return false;
						break;
					}
					
					$query.=$valor.",";
					$fetch_campo_idx++;
				}
		}
		
		#liberamos los recursos
		if(!@mysql_free_result($ddl)){}
		
		#cerramos la coneccion porque en la funcion de ejecutar el query la abrira
		if(!$this->db_onoff('')){}

		#vemos si fue insert para sacarle la ultima coma y agregar el )
		if($tipo=='insert')
		{
			$query=substr($query,0,(strlen($query)-1)).')';
		}
		
		#asignamos el query armado al array para ejecutarlo en la transaccion.
		$this->query_a_ejecutar[$this->idx_query]=$query;		
		
		$this->idx_query++;	
		
		return true;
	}
	
	####################################################################################################################
	#funcion que realiza el query ya armado
	####################################################################################################################
	function ejecutar_query($transaccion=1)
	{					
		if(!$this->db_onoff('on')){return false;}
		if($transaccion==1)
		{
			#se solicita transaccion
			$queryt='set autocommit=0';
			if (!mysql_query($queryt)){
				$this->msg=mysql_error();
				if(!$this->db_onoff('')){}
				$this->inicializar();
				return false;
			}
				
			$queryt='begin';
			if (!$resultados=mysql_query($queryt)){
				$this->msg=mysql_error();
				if(!$this->db_onoff('')){}
				$this->inicializar();
				return false;
			}		
					
		}

		#ejecutamnos el array de los querys preparados.
		try
		{		
			foreach($this->query_a_ejecutar as $k=>$ejecuta)
			{				
				if(!@mysql_query($ejecuta))
				{					
					$this->err=mysql_error();
					$this->errno=mysql_errno();
					if(!@mysql_query('rollback')){}
					throw new exception($err);
				}
			}		
		}catch(exception $e){
			 $this->msg=$e->getmessage();
			 if(!$this->db_onoff('')){}
			 $this->inicializar();
			 return false;
		}
		
		
		#ejecutamos el commit
		if($transaccion==1)
		{
			#se solicita fin de transaccion
			if(!@mysql_query('commit'))
			{
				if(!$this->db_onoff('')){}
				$this->inicializar();
				return false;
			}			
		}
		
		if(!$this->db_onoff('')){}
		$this->inicializar();
		return true;
	}
	
	####################################################################################################################
	#funcion que ejecuta querys rapidos devolviendo el rs
	####################################################################################################################
	function query_rapido($query='',&$recordset)
	{
	
		if($query==''){ return false;}		
		if(!$this->db_onoff('on')){return false;}
		
		#ejecutamnos el query.
		try
		{					
			if(!$recordset=@mysql_query($query))
			{				
				throw new exception(@mysql_error());
			}else{
				$this->cnt_registros=@mysql_num_rows($recordset);
			}			
			
			return true;
		}catch(exception $e){
			 $this->msg=$e->getmessage();			 
			 $this->cnt_registros=0;
			 if(!$this->db_onoff('')){}					 
			 return false;
		}
	}
	
	####################################################################################################################
	#funcion que verifica el tipo de dato para la tabla.
	####################################################################################################################
	function tipo_campo($campotipo,$dato,&$valor)
	{
		$tipocampo_array=explode('(',$campotipo);

		switch($tipocampo_array[0])
		{
			case 'int':
				$valor=$dato;
				break;
			case 'tinyint':
				$valor=$dato;
				break;
			case 'double':
				$valor=$dato;
				break;				
			case 'varchar':
				$valor="'$dato'";
				break;
			case 'char':
				$valor="'$dato'";
				break;
			case 'date':
				if($dato=='curdate()')
				{
				 	$valor='curdate()';
				}else{
					$valor="'".$this->fecha_mysql($dato)."'";
				}				
				break;
			case 'time':
				if($dato=='')
				{
				 	$valor='00:00:00';
				}else{
					$valor="'".$dato."'";
				}				
				break;
			case 'datetime':
				if($dato=='')
				{
				 	$valor='now()';
				}else{
					$valor="'".$dato."'";
				}				
				break;	
				
			default:
				return false;
		}
		return true;
	}
	
	
	####################################################################################################################
	#funcion que busca y devuelve la estructura de la tabla
	####################################################################################################################
	function tbl_estructura($tabla,&$ddl=array())
	{
		#vemos si estamos conectados a la base de datos
		if(!$this->db_onoff('on')){ $this->msg=mysql_error();}
		$query="describe $tabla";
		if(!$ddl=@mysql_query($query))
		{
			return false;
		}else{				
			return true;
		}
	}	


	####################################################################################################################
	#funcion que convierte fecha a mysql
	####################################################################################################################
	function fecha_mysql($fecha){ 
		ereg( "([0-9]{1,2})/([0-9]{1,2})/([0-9]{2,4})", $fecha, $mifecha); 
		$fechaajustada=$mifecha[3]."-".$mifecha[2]."-".$mifecha[1]; 
		return $fechaajustada; 
	} 

	function fecha_normal($fecha){ 
		ereg( "([0-9]{2,4})-([0-9]{1,2})-([0-9]{1,2})", $fecha, $mifecha); 
		$lafecha=$mifecha[3]."/".$mifecha[2]."/".$mifecha[1]; 
		return $lafecha; 
	} 

	####################################################################################################################
	#funcion que inicializa array y variables de control
	####################################################################################################################
	function inicializar()
	{
		for($x=0;$x<count($this->query_a_ejecutar);$x++)
		{
			$this->query_a_ejecutar[$x]='';
		}
		
		$this->idx_query=0;
		return;
	}

	####################################################################################################################
	#funcion para formatear numero
	####################################################################################################################	
	function formatea_numero($numero)
	{
		return (number_format($numero,2,',','.'));
	}
	
	
	
	//funcion para generar un combo	
	function crea_combo($tipo,$sql,$nombre,$seleccionado,$parametros)
	{			
		$nodatos=true;
		switch($tipo)
		{		
			case 1:
				if(!$this->query_rapido($sql,$rec))
				{
					$combotemp.='<option value="-1">e r r o r</option>';
				}else{
					$combotemp='<select name="'.$nombre.'" '.$parametros.'>';
									
					while($d=mysql_fetch_array($rec))
					{
						if($seleccionado==$d[0])
						{
							$combotemp.='<option value="'.$d[0].'" selected>'.$d[1].'</option>';
						}else{
							$combotemp.='<option value="'.$d[0].'">'.$d[1].'</option>';
						}		
						$nodatos=false;			
					}
					
					if($nodatos)
					{
						$combotemp.='<option value="-1">no hay datos</option>';
					}				
					
					if(!@mysql_free_result($rec)){}		
					if(!$this->db_onoff('')){}
					$combotemp.='</select>';					
				}
				break;
			case 2:
				$combotemp='<select name="'.$nombre.'" '.$parametros.'>';
				for($x=1;$x<=7;$x++)
				{
					if($seleccionado==$x)
					{
						$combotemp.='<option value="'.$x.'" selected>'.$this->dia_de_la_semana($x).'</option>';
					}else{
						$combotemp.='<option value="'.$x.'">'.$this->dia_de_la_semana($x).'</option>';
					}
				}
				break;
			case 3:
				$combotemp='<select name="'.$nombre.'" '.$parametros.'>';
				$dat=explode(",",$sql);
				for($x=$dat[0];$x<=$dat[1];$x++)
				{					
					if($x<10){$n='0'.$x;}else{$n=$x;}
					if($seleccionado==$x)
					{
						$combotemp.='<option value="'.$n.'" selected>'.$n.'</option>';
					}else{
						$combotemp.='<option value="'.$n.'">'.$n.'</option>';
					}
				}
				break;
				
			default:
				$combotemp.='<option value="-1">no hay datos</option>';
		}
		
		$combotemp.='</select>';
		return $combotemp;
		
	}
	
	#devuelve dias.
	function dia_de_la_semana($dias=0)
	{
		$diasemana = array (1=>'lunes','martes','miercoles','jueves','viernes','sabado','domingo');							
		return $diasemana[$dias];
	}
	
	#	prevenir sqlinjection
	function revisar_valores($value)
	{
		if(!$this->db_onoff('on')){return false;}			
		// stripslashes
		if (get_magic_quotes_gpc())
		{
		  	$value =stripslashes($value);
		  }
		// si no es un numero
		if (!is_numeric($value))
		{
		  	$value = "'" . mysql_real_escape_string($value) . "'";
		  }
		//return strtoupper($value);
		return $value;
		} 			
	
}
?>
