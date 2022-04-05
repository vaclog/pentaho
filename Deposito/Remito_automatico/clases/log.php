<?
    #Clase para la generacion de un log diario


    class Log
    {
        #Variables de la clase

        private $log_file="";
        var $defaultpath='logs/';


        function __construct($path='')
        {

            #Contructor de la clase, crea los archivos de log en el path
            #pasado en el parametro, si se omite toma el path por default.

            if ($path<>'')
            {
                //Verifico si el path lo pasaron con / al final
                $pos=strpos($path, '/');
                if($pos===false)
                {
                    //Agrego la barra
                    $path=$path.'/';
                }

                #Verifico si existe el path, solo la carpeta porque el archivos se crea luego
                $check=realpath($path);
                if($check!==false AND is_dir($check))
                {      
                    //El path no existe , tomo el default              
                    $this->defaultpath=$path;
                }
            }
            
            //Creo el archivo como nombre la fecha del dia
            $this->log_file=date('Y-m-d').".txt";            

        }


        #Guarda en el log
        function Escribe($proceso,$msgerror)
		{			
			try
			{	
                $usuario='Usuario ['.get_current_user().']';

				$fechahora=date('d/m/Y h:i:s');							
				$f=@fopen($this->defaultpath.$this->log_file,'a+');				
				@fwrite($f,$fechahora."\t".$usuario.' Proceso ['.$proceso.'] --> '.$msgerror);
				@fwrite($f,"\r");
				@fclose($f);
					
				}catch(Exception $e){
					@mail('pasavino@gmail.com','PDO::LOG','Error a abrir el archivo de log');
					}			
			}
    }

?>