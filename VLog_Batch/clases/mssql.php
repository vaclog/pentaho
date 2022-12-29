<?
    #Clase para MSSQL - PHP PDO (Microsoft ODBC Driver 11 for SQL Server)

    include('log.php');

    class mssql extends Log
    {        

        #Variables de la clase
        var $minombre='';
        var $conarray;
        var $mastererror=false;
        var $mastererrormysql=false;
        var $msgERROR='';
        var $sucessPDO=false;
        var $tiempoHumanoSql='';
        
        private $returnOneRow;
		private $oneRow;		
		private $numRows=0;
        
        private $dataPrepare;
        private $dataPrepareMySQL;

        private $cantRegistros=0;
        private $tiempoEjecucionSql=0;        
        private $startTimeSql=0;
		private $endTimeSql=0;		

        
        private $srvPDO;            //Objeto PDO para la conexion       
        private $myPDO;            //Objeto PDO para la conexion MySQL

        #Definicion para MSSQL
        private $serverDSN= array(
                                    1=>array("Server"=>"sqlsrv:Server=192.168.0.201;Database=Remito_QA","UID"=>"vaclog","PWD"=>"hola$$123"),
                                    2=>array("Server"=>"sqlsrv:Server=192.168.0.201;Database=Remito_PROD","UID"=>"vaclog","PWD"=>"hola$$123"));


        #Definicion para MySql
        private $serverMySQL= array(
                                    1=>array('srv'=>'mysql:host=agribiz-cluster.cluster-chiwaqowuag1.us-east-2.rds.amazonaws.com;','user'=>'remito_admin','pass'=>'Vaclog$123','db'=>'dbname=remito_test'),
                                    2=>array('srv'=>'mysql:host=agribiz-cluster.cluster-chiwaqowuag1.us-east-2.rds.amazonaws.com;','user'=>'remito_batch_depo','pass'=>'D3P0S1$321','db'=>'dbname=remito_qa'));
            
            
        #Constructor de la clase
        #modo 1=Desarrollo, 2=Produccion

        function __construct($modo=2)    
        {

            parent::__construct('');

            $this->minombre=pathinfo(__FILE__,PATHINFO_FILENAME);

            #Prueba de conexion           
            $this->Escribe($this->minombre,'Probando conexion a MSSQL');                        
			$this->Escribe($this->minombre,$this->serverDSN[$modo]['Server'], $this->serverDSN[$modo]['UID'], $this->serverDSN[$modo]['PWD']);

            #Establezco la conexion, si logro conectar continuo, caso contrario se para todo el script
            if($this->conectar($modo))
            {
                $this->Escribe($this->minombre,'Conexión establecida');                
            }else{                                
                $this->mastererror=true;
            }

            #Establezco la conexion al server 1
            if(!$this->MySQLConect($modo))
            {
                $this->mastererrormysql=true;
            }
        }


        #Constructor del objeto PDO para la base
        function conectar($modo)
        {
            try
            {
                $this->srvPDO=new PDO($this->serverDSN[$modo]['Server'], $this->serverDSN[$modo]['UID'], $this->serverDSN[$modo]['PWD']);
                $this->srvPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return true;
            }catch(PDOException $e){
                $this->Escribe($this->minombre,$e->getMessage());
                return false;
            }            

        }

        #Conexion a MySQL
        function MySQLConect($modo)
        {
            try
			{
                $this->Escribe($this->minombre,'Probando conexion a MySQL'); 
				$this->Escribe($this->minombre,$this->serverMySQL[$modo]['srv'].$this->serverMySQL[$modo]['db'],$this->serverMySQL[$modo]['user'],$this->serverMySQL[$modo]['pass']); 
				
				$this->myPDO = new PDO(
						$this->serverMySQL[$modo]['srv'].$this->serverMySQL[$modo]['db'],
						$this->serverMySQL[$modo]['user'],
						$this->serverMySQL[$modo]['pass'],
						array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
					);

					$this->myPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					
					#Se le indica que NO emule atributos que no esten en el archivo de configuracion
                    $this->myPDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);	
                    $this->Escribe($this->minombre,'Conexion a MySQL con éxito'); 				
                    return true;
                    
				}catch(PDOException $e){
                    $this->Escribe($this->minombre,$this->serverMySQL[$modo]['srv']); 
                    $this->Escribe($this->minombre,$e->getMessage()); 					
					return false;					
					}	
        }

        #MySQL query
        function MySQL_Query($sql)
		{
			try
			{				
				#Inicio tiempo
				$this->getTimeSql(0);			
				unset($this->dataPrepareMySQL,$this->datosCursorMySQL);
								
				$this->dataPrepareMySQL=$this->myPDO->query($sql);
				$this->dataPrepareMySQL->execute();
								
				$this->getTimeSql(1);
				$this->Escribe($this->minombre,$this->tiempoHumanoSql);						
				return true;			
				
				}catch(PDOException $e){
					$this->Escribe($this->minombre,$e->getMessage()); 
					$this->getTimeSql(1);
					return false;				
					}			
        }
            
        #Inserta en MySQL
        function MySQLInsert($sql)
        {
            try
			{								
				#Limpio de algun query anterior y cantidad de registros				
				unset($this->dataPrepareMySQL,$this->datosCursorMySQL);                                
				$this->dataPrepareMySQL=$this->myPDO->query($sql);                			                
				return true;			
				
				}catch(PDOException $e){
                    $this->Escribe($this->minombre,$e->getMessage()); 										
					return false;				
					}	
        }

        #Quote para mysql
        function Quote($dato)
        {
            return $this->myPDO->quote($dato);
        }
        
        #Inicia la transaccion de MySQL
        function StartTrasaction()
        {
            if($this->myPDO->beginTransaction())
			{					
				return true;
				}else{
					#echo $this->myPDO->errorInfo();
                    return false;
                }
        }

        #Commit de transaccion
        function CommitTranMySQL()
        {
            $this->myPDO->commit();
        }

        #Rollback de transaccion MySQL
        function RollBack()
        {
            $this->myPDO->rollBack();
        }

        #Desconecta la base, destruye el objeto
		function fin()
		{
            $this->srvPDO=null;
            $this->myPDO=null;
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
            
        #Toma datos de MySQL
        function get_MyDatos()
        {
            return $this->dataPrepareMySQL->fetch(PDO::FETCH_BOTH);
        }

        #Liberamos recursos
		function set_Free()
		{
			$this->dataPrepare->closeCursor();
			}

        
        #Ejecuta procedimientos almacenados en MSSQL
        function exec_sp($sql)		
        {            

            try
            {
                #Escribo en el log lo que se intenta hacer
                $this->Escribe($this->minombre,"Ejecuta SP de MSSQL");
                $this->getTimeSql(0);

                $this->srvPDO->exec($sql);
                $this->getTimeSql(1);

                $this->Escribe($this->minombre,$this->tiempoHumanoSql);								
                $this->Escribe($this->minombre,"SP ejecutado sin errores");

                return true;			
                }catch(PDOException $e){
                    $this->Escribe($this->minombre,$e->getMessage());	                                        
                    $this->msgERROR=$e->getMessage();                    
                    $this->getTimeSql(1);
                    return false;
                    }
            }

        #Ejecuta procedimientos almacenados en MySQL
        function exec_spMy($sql)		
		{
			$this->getTimeSql(0);
			try
			{
				$this->Escribe($this->minombre,"Ejecuta SP en MySql $sql");
				$this->myPDO->exec($sql);
				$this->getTimeSql(1);
                $this->Escribe($this->minombre,$this->tiempoHumanoSql);	
                $this->Escribe($this->minombre,"Ejecucion sin errores");
                return true;			
                
				}catch(PDOException $e){
					$this->Escribe($this->minombre,$e->getMessage());										
					$this->msgERROR=$e->getMessage();					
					$this->getTimeSql(1);
					return false;
					}
			}

        #Ejecutar una consulta
		function exec_querysimple($sql)
		{
			try
			{				
                #Escribo en el log lo que se intenta hacer
                $this->Escribe($this->minombre,$sql);
                
				#Inicio tiempo
				$this->getTimeSql(0);
				
				#Limpio de algun query anterior y cantidad de registros
				$this->numRows=0;
				unset($this->dataPrepare,$this->datosCursor);                                
                                
				$this->dataPrepare=$this->srvPDO->query($sql);
                $this->dataPrepare->execute();
                                
				#$this->Escribe($this->minombre,'NumRows Affected: '.$this->numRows);
				$this->getTimeSql(1);
				$this->Escribe($this->minombre,$this->tiempoHumanoSql);		
				$this->Escribe($this->minombre,'OK');
                
				return true;			
				
				}catch(PDOException $e){
					$this->msgERROR=$e->getMessage();
					$this->Escribe($this->minombre,$e->getMessage());
					$this->getTimeSql(1);
					return false;				
					}			
		}

        
        #Funcion que calcula el tiempo de ejecucion de una consulta
		function getTimeSql($f=0)
		{			
			if($f=0)
			{
				$this->startTimeSql=microtime(false);
			}else{
                $this->endTimeSql=microtime(false);
                @$this->tiempoEjecucionSql=($this->endTimeSql - $this->startTimeSql);
                $hs = (int)($this->tiempoEjecucionSql/60/60);
                $ms = (int)($this->tiempoEjecucionSql/60)-$hs*60;
                $ss = (int)($this->tiempoEjecucionSql-$hs*60*60-$ms*60);
                $this->tiempoHumanoSql=sprintf("La consulta/transacción tardo %s Microsegundos",$this->tiempoEjecucionSql);
			}
        }        

        #Normaliza algunos caracteres
        function normalizar($str)
        {
            $original=$str;
            #$salida=preg_replace("/[^a-zA-Z0-9]/","",$original);
            #$salida=addslashes($original);            
            $salida=utf8_decode($original);
            return $salida;
        }
    
    }    //Fin de la clase
?>