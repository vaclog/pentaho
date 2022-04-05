<?
	#Funciones del sitio
	
	class funciones_sitio extends pdodb
	{	
		#Variables
		var $div_paginado='';
		var $registros_totales=0;
		var $pagina_actual=0;
		var $porpagina_calcula=10;
		var $limite_reg='';
		
		private $path_html='html/';
		private $path_php='app/';
		
		
		#	Menu principal
		function menu_principal($seleccionado='')
		{
			$menu='<nav id="navleft"><h2>Contenido</h2><ul>';
			$sql="select nombre,url from vs_menu_home order by nombre asc";
			if(parent::exec_querysimple($sql))
			{
				while($d=parent::get_Datos())
				{
					if($seleccionado==$d['url'])
					{
						$sel='rel="seleccionado"';
						$menu.=sprintf('<li %s>%s</li>',$sel,$d['nombre']);
						}else{
							$sel='';
							$menu.=sprintf('<li %s><a href="/%s">%s</a></li>',$sel,$d['url'],$d['nombre']);
							}
					
					}
				}
			
			$menu.='</ul><hr></nav>';
			parent::set_Free();	
			parent::fin();	
			return $menu;			
			}
		
		#Comprueba si existe el id de la pregunta
        function if_id_pregunta($id)
        {
            if(empty($id) or !is_numeric($id)) return 0;
                        
            $sql=sprintf("select fx_if_id_pregunta(%d)",$id);
            if(parent::exec_OneRow($sql))
            {
                $i=parent::get_OneRow();                
                return $i;
                }else{
                    return 0;
                    }
            }
		
		#Comprueba si existe el id del articulo
		function if_id_articulo($id)
		{
			if(empty($id) or !is_numeric($id)) return 0;
						
			$sql=sprintf("select fx_if_articulo(%d)",$id);
			if(parent::exec_OneRow($sql))
			{
				$i=parent::get_OneRow();				
				return $i;
				}else{
					return 0;
					}
			}		
			
		#Comprueba si existe la categoria segun la url amigable
		function if_categoria_url($nom_cat)
		{
			#Vamos al server 2			
			$sql=sprintf("select fx_if_categoria_url('%s')",$nom_cat);
			if(parent::exec_OneRow($sql))
			{
				return parent::get_OneRow();
				parent::fin();				
				}else{
					return false;
					}			
			}
				
		#Comprueba si existe la ciudad segun url amigable
		function if_id_ciudad_url($nombre)
		{
			if(empty($nombre)) return 0;
			
			global $server;
			#Le paso la categoria 32 que es automotores
			$sql=sprintf("select fx_retorna_id_ciudad_segun_url('%s')",$nombre);
			if($server->query_rapido($sql,$r))
			{
				$i=@mysql_result($r,0,0);
				@mysql_free_result($r);
				return $i;
				}else{
					return 0;
					}
			}		
				
		
		#Busca el id categria segun url amigable
		function get_Idcategoria($url)
		{
			$sql=sprintf("select fx_get_idcat_url('%s')",$url);
			if(parent::exec_OneRow($sql))
			{
				return parent::get_OneRow();
				parent::fin();				
				}else{
					return 0;
					}
			}
		
		#Devuelve el nombre de la categoria
		function get_CatNombre($url)
		{
			$sql=sprintf("select fx_get_cat_nombre_from_url('%s')",$url);
			if(parent::exec_OneRow($sql))
			{
				return parent::get_OneRow();
				parent::fin();				
				}else{
					return '';
					}
			}
						
		#Comprueba y devuelve el id de sub categoria segun url amigable.
		function if_id_cat_url($url)
		{
			if(empty($nombre)) return 0;
			
			global $server;
			#Le paso la categoria 32 que es automotores
			$sql=sprintf("select fx_retorna_id_sub_cat_segun_nombre('%s',%d)",$nombre,$idcat);
			if($server->query_rapido($sql,$r))
			{
				$i=@mysql_result($r,0,0);
				@mysql_free_result($r);
				return $i;
				}else{
					return 0;
					}			
			}
			
				
		#Funcion que verifica si existe la pagina
		function if_pagina($archivo)
		{		
			if(!file_exists($this->path_html.$archivo.'.html'))
			{				
				return false;
				}else{
					if(!file_exists($this->path_php.$archivo.'.php'))
					{						
						return false;
						}
					}
					
				return true;			
			}
		
		#Retorna el path de paginas app/php
		function path_php()
		{
			return $this->path_php;
			}
			
		#Retorna path html
		function path_html()
		{
			return $this->path_html;
			}
		
		#Verifica si existe el MX
		function check_mx($dominio='')
		{
			if(empty($dominio)) return false;
			if(getmxrr($dominio,$mx_records, $mx_weight))
			{
				if(count($mx_records)<=0 or empty($mx_records)){return false;}else{return true;}
				}else{
					return false;
					}
			}
				
		#Detecta tipo de codigicacion
		function deco($string)
		{
			if (mb_detect_encoding($string, 'UTF-8', true) === FALSE)
			{ 
				$string = utf8_encode($string); 
			  }
			  return $string;			  
			}	
				
		#	Retorna nombre de mes
		function nom_mes($m)
		{
			$mes=array(1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Setiembre','Octubre','Noviembre','Diciembre');
			return $mes[$m];
			}	

		#	Validar numero de telefono
		function ValidarTelefono($sNumber)
		{
			$sPattern = "/^
						(?:                                 # Area Code
							(?:                            
								\(                          # Open Parentheses
								(?=\d{3}\))                 # Lookahead.  Only if we have 3 digits and a closing parentheses
							)?
							(\d{3})                         # 3 Digit area code
							(?:
								(?<=\(\d{3})                # Closing Parentheses.  Lookbehind.
								\)                          # Only if we have an open parentheses and 3 digits
							)?
							[\s.\/-]?                       # Optional Space Delimeter
						)?
						(\d{3})                             # 3 Digits
						[\s\.\/-]?                          # Optional Space Delimeter
						(\d{4})\s?                          # 4 Digits and an Optional following Space
						(?:                                 # Extension
							(?:                             # Lets look for some variation of 'extension'
								(?:
									(?:e|x|ex|ext)\.?       # First, abbreviations, with an optional following period
								|
									extension               # Now just the whole word
								)
								\s?                         # Optionsal Following Space
							)
							(?=\d+)                         # This is the Lookahead.  Only accept that previous section IF it's followed by some digits.
							(\d+)                           # Now grab the actual digits (the lookahead doesn't grab them)
						)?                                  # The Extension is Optional
						$/x";                               // /x modifier allows the expanded and commented regex

			if (preg_match($sPattern, $sNumber, $aMatches))
			{
				return true;
				#echo 'Matched ' . $sNumber . "\n";
				#print_r($aMatches);
			} else {
				return false;
				#echo 'Failed ' . $sNumber . "\n";
			}
		}
		
	}
?>