<?

    #Script principal lanzado por la tarea programada en el servidor windows

    #Dependencias
    #
    #       Microsoft ODBC Driver 11 for SQL Server
    #       https://www.microsoft.com/es-ar/download/details.aspx?id=36434
    #       x32 o x64 segun sea el server


    //Nombre del escript, SELF
    $minombre=basename(__FILE__);
    $carpeta_querys="querys";               #Querys de MSSQL
    $carpeta_querys_mysql="querys_mysql";   #Carpeta donde se guardan los querys para mysql
	$carpeta_excel="salida_excel";          #Carpeta donde se guardan los excel luego hay un move a la carpeta del drive (esta en el lanzador)


    $archivomysql='mysql_query_'.date('Y-m-d_Hi').'.sql';


    //Cargo la clase de MSSQL - Server ODBC
    include("clases/mssql.php");

    $server=new mssql(2);
    if($server->mastererror || $server->mastererrormysql)
    {
        #Fin, error general, no se continua
        echo "Error general, no se continua con el script";
        exit(1);
    }

	
	#Indico que servidores estoy usando
	#$server->Escribe($minombre,"No fué posible iniciar la transaccion en MySQL, operación abortada");

    #Se inicia el query para traer los datos de MSSQL
    #El query viene desde el archivo 1_secuencia.sql

    if(!file_exists($carpeta_querys.'/1_sp.sql'))
    {
        echo "No existe el archivo del query";
        exit(1);
    }

    $sql=file_get_contents($carpeta_querys.'/1_sp.sql');

    //Ejecuta el SP en la base de MSSQL
    if(!$server->exec_sp($sql))
    {
        echo "Error al ejecutar la consulta";
        exit(1);
    }    
    

    if(!file_exists($carpeta_querys.'/2_select.sql'))
    {
        echo "No existe el archivo del query";
        exit(1);
    }
    
    $sql=file_get_contents($carpeta_querys.'/2_select.sql');
    if(!$server->exec_querysimple($sql))
    {
        echo "Error al ejecutar la consulta";
        exit(1);
    }
   

    #Preparo el archivo de lotes para mysql    
    if($f=@fopen($carpeta_querys_mysql.'/'.$archivomysql,'a+'))
    {       
        $CntRegistros=0;
        $server->Escribe($minombre,"Se crea el archivo de querys $archivomysql"); 
        $server->Escribe($minombre,"Se inician los insert a mysql"); 
        $server->getTimeSql(0);
        $ErrorInsert=false;
        $Tareas=false;   //Indico si hay algo que hacer

        if(!$server->StartTrasaction())
        {
            $server->Escribe($minombre,"No fué posible iniciar la transaccion en MySQL, operación abortada");
            $ErrorInsert=true;			
            exit("**********************ERROR***********************");
        }else{
            $server->Escribe($minombre,"Se inicia la transacción en MYSQL -> Begin Transaction");

            while($r=$server->get_Datos())
            {               

                $sql=sprintf("insert into cron_temp values(0,%d,%d,%s,%s,%s,%s,%s,%d,%d,%s,%d,%s,%s,%d,%d,%d,%d,%d,curdate(),curtime(),1);",
                                $r[0],$r[1],
                                $server->Quote($r[2]),
                                $server->Quote($r[3]),
                                $server->Quote($r[4]),
                                $server->Quote($r[5]),
                                $server->Quote($r[6]),
                                $r[7],
                                $r[8],
                                $server->Quote($r[9]),$r[10], 
                                $server->Quote($r[11]),
                                $server->Quote($r[12]),
                                $r[13],$r[14],$r[15],$r[16],$r[17]);               
                
				
				#echo $sql."\n\r";
				
                #Inserto en MySQL
                if(!$server->MySQLInsert($sql))
                {
                    $ErrorInsert=true;
                    $server->Escribe($minombre,"Error al insertar en mysql el query [ $sql ]"); 
                    $server->getTimeSql(1);
                    $server->Escribe($minombre,$server->tiempoHumanoSql);
                    break;
                }else{
                    @fwrite($f,$sql);
                    @fwrite($f,"\r");
                    $server->Escribe($minombre,$sql);
                    $CntRegistros+=1;                    
                    unset($sql); 
                }               
                           
            }
            
            @fclose($f);

        }        
        
        if($ErrorInsert)
        {
            $server->RollBack();
            $server->Escribe($minombre,"ROLLBACK");
            $server->Escribe($minombre,"Se insertaron $CntRegistros registro/s , operación detenida por un fallo.");
			
        }else{  
            $server->CommitTranMySQL();  
            $tiempo=$server->getTimeSql(1);                    
            $server->Escribe($minombre,"COMMIT DE TRANSACCION MYSQL");
            $server->Escribe($minombre,"Se insertaron todos los registros [ $CntRegistros ]");
            $server->Escribe($minombre,$server->tiempoHumanoSql);
				
            //Si todo salio bien, ejecuto el SP en mysql
            if(!file_exists($carpeta_querys.'/3_SPMySQL.sql'))
            {
                echo "No existe el archivo del query , paso 3";
                exit(1);
            }
            
            $sql=file_get_contents($carpeta_querys.'/3_SPMySQL.sql');
            $transaccion=date('YmdHi');
            $sql=sprintf($sql,$transaccion);
			//---------------------------------------------------------------------------------------------
			#echo "corte";
			#exit(1);
			//---------------------------------------------------------------------------------------------
            if(!$server->exec_spMy($sql))
            {
                echo "Error al ejecutar la consulta paso 3";
                exit(1);
            }else{
				#exit("hecho");
				
                #Creo el archivo de excel
                if(!require_once('clases/PHPExcel.php'))
                {
                    $server->Escribe($minombre,"No se pudo cargar el php para crear los excels");
                    exit(1);
                }

                if(!require_once('clases/PHPExcel/Writer/CSV.php'))
                {
                    $server->Escribe($minombre,"No se pudo cargar el php para crear los csv");
                    exit(1);
                }

				
				#Tomo de MSSQL de la tabla de parametros_clientes los nombres de archivos excel que se van a usar
				#....por el momento traigo todos, dado que son pocos clientes.
				
				$arrArchivos=array();
				$sql="select LogEntId,ArchivoExcel from parametros_clientes where Estado=1";
				$server->Escribe($minombre,"Generando consulta $sql");
				
				if(!$server->exec_querysimple($sql))
				{
					$server->Escribe($minombre,"No se pudo cargar los nombres de archivos excel de la tabla parametros_clientes");
					exit(1);
				}else{
					while($rj=$server->get_Datos())
					{
						$arrArchivos[$rj[0]]=[$rj[1]];
					}
				}
				
				#Agrupo los clientes y numeros de remitos por el numero de transaccion realizado
				#
				#....Traigo el IdCliente y numero de remito para hacer el query por cada cliente, tener presente que el IdCliente es el de MySql
				#............!!!!!!! CUIDADO !!!!!!.............
				#	El query utiliza una vista nueva (vs_export_excel_v2)
								
				
				$sql=sprintf("select COLA,IdCliente,clientevkm from vs_export_excel_v2 where transaccion='%s' group by COLA,IdCliente",$transaccion);
                $server->Escribe($minombre,"Generando consulta $sql");
				
				#Cargo los datos en array dado que la conexion a MySql no me permite ejecutar mas queris en la misma instancia de conexion.
				$arrClientes=array();
								
				if($server->MySQL_Query($sql))
				{
					while($cc=$server->get_MyDatos())					
					{
						$valores=$cc[0]."@".$cc[1]."@".$cc[2]; //COLA,IdCliente,clientevkm
						array_push($arrClientes,$valores);						
						$valores='';
					}
				}else{
					$server->Escribe($minombre,"Error al ejecutar la consulta $sql");
					exit(1);
				}
                               
                
				foreach ($arrClientes as $Valores)
				{
					$Datos=explode("@",$Valores);
					#echo $Datos[0].'  '.$Datos[1].'  '.$Datos[2];					
					
					#Verifico si existe la carpeta para el cliente
					$VerDir='';
					$SufijoArchivo='';
					$VerDir=$carpeta_excel.'/'.$arrArchivos[$Datos[2]][0];
					$SufijoArchivo=$arrArchivos[$Datos[2]][0];
					
					if(!is_dir($VerDir))
					{
						//Si no existe lo creo
						if(!mkdir($VerDir, 0777))
						{
							$server->Escribe($minombre,"Error al crear la carpeta $VerDir");
							exit(1);
						}else{
							$server->Escribe($minombre,"Creando la carpeta $VerDir");
						}
					}else{
						$server->Escribe($minombre,"La carpeta $VerDir ya existe!");
					}
					
					#--------------------------------------------------------------------------------------------------------------------------------------
					
						#Ejecuto el query para traer los datos de cada remito
						$sql=sprintf("select * from vs_export_excel_v2 where COLA='%s' and IdCliente=%d",$Datos[0],$Datos[1]);
						$server->Escribe($minombre,"Generando consulta $sql");

						if($server->MySQL_Query($sql))
						{                        
							$server->Escribe($minombre,"La consulta devolvio mysql_num_rows() registros");
							$archivocreado=false;
							//$archivo_excel=$carpeta_excel.'/RT-ELCA-';
							$archivo_CSV=$VerDir;


							$i=1;  //Inidcador para el excel, indica a partir de que ROW comenzar dado q la 1 es el titulo
							$remitocontrol='';                                

							#$ArrayCSV=array();                                
							
							while($r=$server->get_MyDatos())
							{								

								#Creo el nombre de archivo a partir de la columna COL-A
								if(!$archivocreado)
								{
									#Declaro la instancia
									$objPHPExcel = new PHPExcel();
									#$objPHPExcel = new PHPExcel_Writer_CSV($objPHPExcel);
									
									$objPHPExcel->getProperties()->setCreator("VLOG")
												->setLastModifiedBy("VLOG")
												->setTitle("Remitos")
												->setSubject("Remitos automaticos")
												->setDescription("Generación de remitos para el cliente")
												->setKeywords("VLOG")
												->setCategory("Remitos");

									//$archivo_excel.=$r[0].'.csv';   //Preparo el nombre del archivo excel
									$archivo_CSV.='/'.$SufijoArchivo.'-'.$r[0].'.csv';      
									$archivocreado=true;         
									
									#Creo el TXT camuflado
									if(!$DatosCSV=@fopen($archivo_CSV,"a+"))
									{
										$server->Escribe($minombre,"Error al crear el archivo $DatosCSV");
										exit(1);
									}
								}                                    
							   
								#Toma los datos concretamente
								$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, $r[0]);
								$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, $r[1]);
								$objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, $r[2]);
								$objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, $r[3]);  
								$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, $r[4]);
								$objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.$i, $r[5]);
								$objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, $r[6]);
								$objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.$i, $r[7]);
								$objPHPExcel->setActiveSheetIndex(0)->setCellValue('I'.$i, $r[8]);
								$i++;
								
								$rcol='';
								$rcol=trim($r[0]).';'.trim($r[1]).';'.trim($r[2]).';'.trim($r[3]).';'.trim($r[4]).';'.trim($r[5]).';'.trim($r[6]).';'.trim($r[7]).';'.trim($r[8]).PHP_EOL;
								
								#Grabo en el CSV camuflado
								@fputs($DatosCSV,$rcol);

							}

							#Cierro archivo camuflado
							@fclose($archivo_CSV);

							#Escribe el archivo excel
							$objWriter = new PHPExcel_Writer_CSV($objPHPExcel);
							//$objWriter->save($archivo_excel);
							unset($objPHPExcel);
							$i=1;
							$archivocreado=false;                                

						}else{
							$server->Escribe($minombre,"No se pudo hacer el query para crear los archivos excel");
							exit(1);
						} //if query 
					
					#--------------------------------------------------------------------------------------------------------------------------------------
					
				}     

            } //Query SP

        }   //Error       

    }else{
        $server->Escribe($minombre,"Error al crea el archivo de querys $archivomysql"); 
        echo "Error al crear el archivo mysql $archivomysql";
        exit(1);

    }	//Archivo de SQL	
				
    #Finalizo la conexion, mato el objeto PDO
    $server->fin();

?>