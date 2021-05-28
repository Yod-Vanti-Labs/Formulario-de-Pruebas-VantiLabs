<?php

//obtener datos del formulario
 
$nombrev = $_POST["nombre"];

$cadena34 = $_POST["email"]; 

$cadena_formateada = trim($cadena34);

$cadena_formateada2 = rtrim($cadena34, ".");

$cadena33=str_replace(",",".",$cadena_formateada2);

$cadena34=str_replace(" ","",$cadena33);

$emailv = $cadena34;

$telefonov = $_POST["telefono"];

$mensajev = $_POST["mensaje"];

$clientesv = $_POST["id_clientes"]; 

$campanav = $_POST["id_campana"]; 

$redireccion = $_POST["redireccion"];

$keygoogle = $_POST["keypriv"];

$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify'; 
$recaptcha_secret = $keygoogle; 
$recaptcha_response = $_POST['recaptcha_response']; 
$recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response); 
$recaptcha = json_decode($recaptcha); 

if($recaptcha->score >= 0.7){
    // OK. ERES HUMANO, EJECUTA ESTE CÓDIGO



// require_once('libs/smartsheet_api.php');
 
// $test = new smartsheet_connect('x29yhb1xp6qu5gq8qir7273wcp', '7018904896726916', true);
$test = new smartsheet_connect('4cQ3cCfs9qO5pVGsARDKTHoRqmaKgV8btX2Cg', '7659158154569604', true);

 
$row_data = array('Nombre' => $nombrev,  "Email"=>$emailv,'Teléfono'=>$telefonov, 'Empresa/Perfil'=>'', 'Mensaje'=>$mensajev, 'id_clientes'=> $clientesv, 'id campaña'=>$campanav);
$file_path = "/tmp/test.txt";

/* 
 * Example Usage: Adding a Row
 */

$row_id = $test->insert_row($row_data);

if($row_id !== false){
	
	echo "Row Added: {$row_id} <br />";
	echo "Gracias... Procesando..<br />";	
	
	echo '<script type="text/javascript">';
	echo 'setTimeout(function () {window.location.href= "'.$redireccion.'";}, 500);';
	echo '</script>';

	echo $redireccion;
	
} else{
	
	echo "An Error Occurred!";
	
}

}else{
    // KO. ERES ROBOT, EJECUTA ESTE CÓDIGO
}

class smartsheet_connect{
	
	private $api_key = '';
	private $base_url = 'https://api.smartsheet.com/2.0/';
	private $columns = false;
	private $sheet_id = false;
	
	/* 
	 * Set to true to print an html error message.
	 * This should include response from Smartsheet when present 
	*/
	
	private $debug = false;
	
	/* 
	 * When true, compare the uploaded file with a base path.
	 * If the file path does not start with the base path, 
	 * the upload will not go through.
	 */
	
	private $check_upload_path = true;
	private $base_upload_path = '/tmp';
	
	
	function __construct($api_key, $sheet_id, $debug=false){
		
		if(empty($api_key)){
			echo 'Invalid API Key';
			exit;
		}
		
		if(empty($sheet_id)){
			echo 'Invalid Sheet ID';
			exit;
		}
		
		if($debug !== false){
			$this->debug = true;
		}
		
		$this->sheet_id = $sheet_id;
		$this->api_key = $api_key;		
		
	}	
	
	
	/*
	 * Get the entire sheet, as a result array 
	 */
		
	function get_sheet(){		
		return $this->get_api_result("sheets/{$this->sheet_id}");				
	}
	
	
	/*
	 * Get the columns of the sheet
	 */
	 
	private function get_column_data(){
		
		$result = $this->get_api_result("sheets/{$this->sheet_id}");	
		
		if($result['status'] != 1){
			return $result;
		}
		
		$columns = $result['data']->columns;
		
		return $columns;
		
	}
	
	/* 
	 * Set the columns variable, so that it can be used again without another api call.
	 */
	 
	public function set_columns(){
	
		$columns = $this->get_column_data($this->sheet_id);
		
		if(is_array($columns) && isset($columns['status'])){
			
			
			if($this->debug){				
				echo "set_columns: {$columns['response_code']} -> {{$columns['error']}<br />";				
			}
			
			return $columns;		
		}
		
		$this->columns = $columns;
		
		return true;
		
	}
	
	 /* 
	 * Smartsheet uses a numeric column id when working with a column or row.
	 * Find the ID based on the title/name of the column.
	 * Note that multiple columns with the same title here could cause problems.
	 */
	
	public function get_column_id_by_title($name){
		
		if($this->columns === false){
			
			$result = $this->set_columns();
			
			if($result !== true){
				
				if($this->debug){					
					echo "get_column_id_by_title: Error getting columns<br />";					
				}
				
				return false;
			}
		}
		
		if(!is_array($this->columns) || count($this->columns) <= 0){
			return false;
		}
		
		foreach($this->columns as $column){
			
			if($column->title == $name){
				return $column->id;
			}
			
		}
		
		return false;		
		
	}
	
	/* 
	 * Insert a row into your sheet.
	 * var $row_data = associative array with the Title as the key. ex: array('My Column Title' => 'My Value');
	 * var $strict when true, field validation should occur
	 */
	
	public function insert_row($row_data, $strict = false){
		
		if($this->columns === false){
			
			$result = $this->set_columns();
			
			if($result !== true){
				
				if($this->debug){					
					echo "insert_row: Error getting columns<br />";					
				}
				
				return false;
			}
		}
		
		
		if(count($row_data) <=0){
			
			if($this->debug){					
				echo "insert_row: Invalid Row Data<br />";					
			}
				
			return false;
			
		}
		
		$can_continue = true;
		
		$row_info = array();
		
		foreach($row_data as $title => $value){
			
			$column_id = $this->get_column_id_by_title($title);
			
			if($column_id === false){
				$can_continue = false;
				
				if($this->debug){					
					echo "insert_row: Invalid Column({$title})<br />";					
				}
				
			}
			
			$row_info[] = array('columnId'=>$column_id, 'value'=>$value, 'strict'=>$strict);
			
			
		}
		
		
		if(!$can_continue){
			
			return false;
			
		}
		
		$insert_data = array('toBottom' => true, 'cells' => $row_info);
		
		$result = $this->get_api_result("sheets/{$this->sheet_id}/rows", $insert_data);
		
		if(is_array($result) && $result['status'] == 1){
			
			$row_id = $result['data']->result->id;
			return $row_id;			
		} else{
			
			if($this->debug){					
				echo "insert_row: Error Inserting Row<br />";					
			}
			
			return false;
			
		}
		
	}
	
	/* 
	 * Attach a file to a row. Useful in combination with insert_row
	 * var $row_id = the ID of the Row to attach a file to
	 * var $file_path = the path of the file on the server
	 */
	public function attach_file($row_id, $file_path){
		
		
		if(empty($row_id)){
			
			if($this->debug){					
				echo "attach_file: Error Missing Row ID<br />";					
			}
			
			return false;
		}
		
		
		$file_path = realpath($file_path);
		
		if(!file_exists($file_path)){
									
			if($this->debug){					
				echo "attach_file: Error Getting File<br />";					
			}
			
			return false;
		}
		
		$path_info = pathinfo($file_path);
		
		if(!is_array($path_info) || ($this->check_upload_path && !$this->starts_with($path_info['dirname'], $this->base_upload_path))){
			
			if($this->debug){					
				echo "attach_file: Error Getting File Info {$path_info['dirname']}<br />";					
			}
			
			return false;
			
		}
		
		$file_name = $path_info['basename'];
		
		$finfo = finfo_open(FILEINFO_MIME_TYPE);		
		$mime_type = finfo_file($finfo, $file_path);
		finfo_close($finfo);
		
		if(empty($mime_type)){
			
			if($this->debug){					
				echo "attach_file: Error Getting Mime Type {$mime_type}<br />";					
			}
			
			return false;
		}
		
		$file_size = filesize($file_path);
		
		if($file_size === false || $file_size <= 0){
			
			if($this->debug){					
				echo "attach_file: Error Getting File Size {$file_size}<br />";					
			}
			
			return false;
			
		}		
		
		$file_data = array('file_path'=>$file_path, 'file_size'=>$file_size, 'mime_type'=>$mime_type, 'file_name'=>$file_name);
		
		$result = $this->get_api_result("sheets/{$this->sheet_id}/rows/{$row_id}/attachments", false, $file_data);
		
		if(is_array($result) && $result['status'] == 1){
			return true;
		} else{
			
			if($this->debug){					
				echo "attach_file: Error Attaching File<br />";
			}
			
			return false;
			
		}
		
		
	}
	
	
	/*
	 * Makes a call to smartsheet api. Returns a result array with data or error message/code
	 * var $url = url of the api call. so, 'sheets/{$this->sheet_id}'
	 * var $data = if an array or object, passed as a data object
	 * var $file = if an array, passes file to api. Must include 'file_name', 'mime_type', 'file_size', and 'file_path' as part of the array
	 */ 
	
	private function get_api_result($url, $data=false, $file=false){
		
		$result = $this->get_empty_result();
		
		$headers = array("Authorization: Bearer {$this->api_key}");
		$options = array(CURLOPT_RETURNTRANSFER=>true);
		
		$curl = curl_init($this->base_url.$url);
		

		
		if(is_array($data) || is_object($data)){
			$data = json_encode($data);
			$headers[] = "Content-Type: application/json";
			$headers[] = "Content-Length: ".strlen($data);
			$options[CURLOPT_CUSTOMREQUEST] = 'POST';
			$options[CURLOPT_POSTFIELDS] = $data;
		} else if(is_array($file) && count($file) > 0){
			
			$file['file_name'] = str_replace(array(' ', '"', "'"), '_', $file['file_name']);
			
			$headers[] = "Content-Type: {$file['mime_type']}";
			$headers[] = 'Content-Disposition: attachment; filename="'.$file['file_name'].'"';
			$headers[] = "Content-Length: {$file['file_size']}";
			$options[CURLOPT_CUSTOMREQUEST] = 'POST';
			$options[CURLOPT_POSTFIELDS] = file_get_contents($file['file_path']);
		}
		
		
		
		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt_array($curl, $options);
		
		
		$smartsheet_data = curl_exec($curl);		
		$result['response_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		
		if(!empty($smartsheet_data) && $this->is_json_object($smartsheet_data)){
			
			$smartsheet_data = json_decode($smartsheet_data);
			$result['data'] = $smartsheet_data;			
			
		} else{
						
			$result['error'] = 'Invalid Curl Response';
			$result['status'] = -2;
			return $result;			
		}
		
		
		if(isset($smartsheet_data->errorCode) && !empty($smartsheet_data->errorCode)){
			
			$result['error_code'] = $smartsheet_data->errorCode;
			$result['error'] = $smartsheet_data->message;
			$result['status'] = -3;
			
			if($this->debug){					
				echo "get_api_result({$url}): {$smartsheet_data->message}({$smartsheet_data->errorCode})<br />";

			}
			
			
			return $result;
		}
		
		
		$result['status'] = 1;
		return $result;
		
	}
	
	/*
	 * Checks if value is a json object
	 */
	 
	public function is_json_object($value){
	
	if(empty($value) || !is_string($value)){
		return false;
	}
	
	$temp = json_decode($value, true);
	
	if(!is_array($temp)){
		return false;
	} else{
		return true;
	}
	
	
	}
	
	/* 
	 * The base result array, used when making an API call.
	 */
	 
	private function get_empty_result(){
		
		return array('status' => -1, 'data' => '', 'error'=>'', 'error_code'=> '', 'response_code'=>-1);
		
		
	}
	
	/* 
	 * Checks if a word(haystack) starts with a word/phrase(needle)
	 */
	 
	private function starts_with($haystack, $needle){
		$length = strlen($needle);
    
		if ($length == 0) {
			return true;
		}

		return (substr($haystack, 0, $length) === $needle);
		
	}
	
}






?>
