<?

	#Clase para el menejo de la base de datos mediante objeto PDO
	#error_reporting(0);

	class pdodb
	{
		#Variables de la clase
		var $msgERROR='';
		var $sucessPDO=false;		
				
		private $modositio='DEV';
		private $returnOneRow;
		private $oneRow;		
		private $numRows=0;
		private $dataPrepare;
		private $maxResultados=100;
		private $desdePagina=0;
		private $pagActual=0;
		private $cantRegistros=0;
		private $ultimaPagina=0;
		private $startTimeSql=0;
		private $endTimeSql=0;
		private $tiempoEjecucionSql=0;
		private $tiempoHumanoSql='';
		private $varpagina='Pag';
		private $varpaginatipo=0;			//0=GET, 1=POST
		private $_Db=1;						//Defino el server a usar, si esta definido se ignora lo que se pasa en el constructor,
											//si se deja en cero (0) inicia con lo que se pasa en el constructor
				
		#Log		
		private $log_file="pdolog.txt";
					
		#Variables de servidor de base de datos
		private $objPDO;		
		
		private $serverDSN= array(
					1=>array('srv'=>'mysql:host=localhost;',
							 'user'=>'remitos',
							 'pass'=>'laravel',
							 'db'=>'dbname=remitos'),
					2=>array(
							 'srv'=>'mysql:host=167.99.150.156;',
							 'user'=>'easyafrica',
							 'pass'=>'menchero2020',
							 'db'=>'dbname=easyrideafrica')		 
					);

		private $Http=array(			
			'DEV'=>array(
						"URL_SITIO"=>"http://192.168.0.253/easyrideafrica.com/dev2/",
						"BASE_TARGET"=>"http://192.168.0.253/easyrideafrica.com/dev2/"),
			'PRO'=>array(
						"URL_SITIO"=>"http://www.easyrideafrica.com/dev2/",
						"BASE_TARGET"=>"http://www.easyrideafrica.com/dev2/")
		);

		
		#Constructor de la clase
		function __construct($s=0)
		{	
			#Atencion, se crea el objeto indicando leer los seteos de mysql desde su archivo principal
			#esto es para prevenir SQLinjection emulado.
			try
			{
				#Compruebo de donde vengo para crear el log
				if($this->_Db>0)
				{
					$s=$this->_Db;
				}

				$this->Log("CONEXCION A USAR $s");
				$this->Log('Se inicializa clase objPDO');
				$this->objPDO = new PDO(
						$this->serverDSN[$s]['srv'].$this->serverDSN[$s]['db'],
						$this->serverDSN[$s]['user'],
						$this->serverDSN[$s]['pass'],
						array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
					);

					$this->objPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					
					#Se le indica que NO emule atributos que no esten en el archivo de configuracion
					$this->objPDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
					$this->sucessPDO=true;
					
				}catch(PDOException $e){
					$this->msgERROR=$e->getMessage();
					$this->sucessPDO=false;
					$this->Log($e->getMessage());
					}						
			}

		#Devuelve el modo del sitio, si desarrollo o produccion
		function HttpModo()
		{
			return array($this->Http[$this->modositio]['URL_SITIO'],$this->Http[$this->modositio]['BASE_TARGET']);
		}
		
		#Desconecta la base
		function fin()
		{
			$this->objPDO=null;
			}			
		
		
		#___________________________________________________________________EXE	
		
		#Funcion que solo devuelve una fila
		function exec_OneRow($sql)
		{
			try
			{				
				#Inicio tiempo
				$this->getTimeSql(0);
				
				#Limpio de algun query anterior			
				unset($this->oneRow);				
				
				$this->Log($sql);
				$this->oneRow=$this->objPDO->query($sql);
				$this->oneRow->execute();
				$this->returnOneRow=$this->oneRow->fetch(PDO::FETCH_BOTH);
				
				$this->Log('Ejecutando OneRow');
				$this->getTimeSql(1);
				$this->Log($this->tiempoHumanoSql);		
				$this->Log('OK');									
				$this->oneRow->closeCursor();
				return true;		
				//return $this->dataPrepare;
				}catch(PDOException $e){
					$this->msgERROR=$e->getMessage();
					$this->Log($e->getMessage());
					$this->getTimeSql(1);
					return false;				
					}			
			}
			
			
		#Ejecutar una consulta simple
		function exec_querysimple($sql,$paginar=0)
		{
			try
			{				
				#Inicio tiempo
				$this->getTimeSql(0);
				
				#Limpio de algun query anterior y cantidad de registros
				$this->numRows=0;
				unset($this->dataPrepare,$this->datosCursor);
				
				#Veo si hay que paginar los resultados
				if($paginar==1)
				{					
					$sql=$this->Paginador($sql);
					$this->Log($sql);
					}else{
						#Grabo en log lo que se intenta hacer
						$this->Log($sql);
						}
				
				$this->dataPrepare=$this->objPDO->query($sql);
				$this->dataPrepare->execute();
				$this->numRows=$this->dataPrepare->rowCount();	
				$this->Log('NumRows Affected: '.$this->numRows);
				$this->getTimeSql(1);
				$this->Log($this->tiempoHumanoSql);		
				$this->Log('OK');
									
				return true;			
				//return $this->dataPrepare;
				}catch(PDOException $e){
					$this->msgERROR=$e->getMessage();
					$this->Log($e->getMessage());
					$this->getTimeSql(1);
					return false;				
					}			
			}						
		
		#Transaccion por Store Procedires
		function exec_sp($sql)		
		{
			$this->getTimeSql(0);
			try
			{
				$this->Log('TRAN-SIMPLE:');
				$this->Log($sql);
				$this->objPDO->exec($sql);
				$this->getTimeSql(1);
				$this->Log($this->tiempoHumanoSql);								
				$this->Log('TRANSACCION-SIMPLE OK');
				return true;			
				}catch(PDOException $e){
					$this->Log('TRANSACCION-SIMPLE Error al ejecutar el store procedure');	
					$this->Log($e->getMessage());
					$this->objPDO->rollBack();
					$this->msgERROR=$e->getMessage();
					$this->Log($e->getMessage());
					$this->getTimeSql(1);
					return false;
					}
			}
			
		#Transacciones
		function exec_Transaccion_SARR($sql)
		{	
			#Inicio tiempo
			$this->getTimeSql(0);
				
			#Permite un array sql o un simple sql		
			try
			{		
				if($this->objPDO->beginTransaction())
				{					
					$this->Log('SE INICIA LA TRANSACCION');
					}
							
				if(is_array($sql))
				{
					#Transaccion con array
					$this->Log('TRAN-QUERY-INICIO: ');
					for($x=0;$x<=count($sql)-1;$x++)
					{						
						$this->Log($this->tiempoHumanoSql);												
						$this->Log($sql[$x]);
						$this->objPDO->exec($sql[$x]);						
						}
						$this->Log('TRAN-QUERY-FIN:');
					
					$this->getTimeSql(1);
					$this->Log($this->tiempoHumanoSql);						
					$this->Log('TRAN-OK');
					}else{
						#Transaccion sin array
						$this->Log('TRAN-NO-ARRAY:');
						$this->objPDO->exec($sql);
						$this->getTimeSql(1);
						$this->Log($this->tiempoHumanoSql);								
						$this->Log('OK');
						}
				
				$this->objPDO->commit();	
				$this->Log('Commit Hecho');					
				return true;
				}catch(PDOException $e){
					$this->Log('Rollback');	
					$this->objPDO->rollBack();
					$this->msgERROR=$e->getMessage();
					$this->Log($e->getMessage());
					$this->getTimeSql(1);
					return false;				
					}		
			}
				
		#Funcion para guardar un log
		private function Log($msgerror)
		{			
			try
			{	
				$fechahora=date('d/m/Y h:i:s');							
				$f=@fopen($this->log_file,'a+');				
				@fwrite($f,$fechahora."\t".$msgerror);
				@fwrite($f,"\r");
				@fclose($f);
					
				}catch(Exception $e){
					@mail('pasavino@gmail.com','PDO::LOG','Error a abrir el archivo de log');
					}			
			}
		
		#___________________________________________________________________GET
		
		#Retorna un Rs, indicando que columna o -1 para todas (array bidimencional)
		function get_Rs($col=-1)
		{
			if($col<0)
			{
				return $this->dataPrepare->fetchALL();
				}else{
					return $this->dataPrepare->fetchALL(PDO::FETCH_COLUMN, $col);
					}
			
			}
			
		#Ultimo insert id
		function get_UltimoIdInsertado()
		{
			return $this->dataPrepare->lastInsertId();
			}
			
		#Funcion que calcula el tiempo de ejecucion de una consulta
		private function getTimeSql($f=0)
		{
			return; //Puesto poque la linea 281 da warning
			if($f=0)
			{
				$this->startTimeSql=microtime(false);
				}else{
					$this->endTimeSql=microtime(false);
					$this->tiempoEjecucionSql=($this->endTimeSql - $this->startTimeSql);
					$hs = (int)($this->tiempoEjecucionSql/60/60);
					$ms = (int)($this->tiempoEjecucionSql/60)-$hs*60;
					$ss = (int)$this->tiempoEjecucionSql-$hs*60*60-$ms*60;
					$this->tiempoHumanoSql=sprintf("La consulta tardo %s Microsegundos",$this->tiempoEjecucionSql);
					}				
			}
		
		#Retorna los registros totales
		function get_regTotales()
		{
			return $this->cantRegistros;
			}
			
		#Retorna la cantidad de registros
		function get_numRows()
		{
			return $this->numRows;
			}
			
		#Fetch de los resultados
		function get_Datos()
		{
			return $this->dataPrepare->fetch(PDO::FETCH_BOTH);
			}
		
		#Devuelve solo 1 row
		function get_OneRow()
		{
			return $this->returnOneRow[0];
			}
			
		#Devuelve el tiempo de ejecucion de una consulta sql
		function get_TiempoEjecucionSql()
		{
			return $this->tiempoHumanoSql;
			}
		
		#Retorna el numero de resultados por pagina seteados
		function get_MaxResultadosPorPagina()
		{
			return $this->maxResultados;
			}
				
		#____________________________________________________________________SET
					
		#Liberamos recursos
		function set_Free()
		{
			$this->dataPrepare->closeCursor();
			}
				
		#Setea el timeout de php
		function set_TimeOut($a=0)
		{
			if($a==0)
			{
				@set_time_limit(0); 
				}else{
					@set_time_limit(50); 
					}			
			}
				
		#Setea el nombre de la variable de pagina
		function set_GetPostPagina($pn='Pag',$tipo=0)
		{
			//Indico como se llamara la variable get o post
			$this->varpagina=$pn;
			//Indico si vendra por post o get
			$this->varpaginatipo=$tipo;
			}
		
		
		
} //Fin Clase

?>