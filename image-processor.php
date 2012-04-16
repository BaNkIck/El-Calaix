<?php

/**
 * Classe per a realitzar operacions bàsiques amb imatges
 *
 * @author Aaron Navarro Heras
 * @version 0.1.1
 */
class ImageProcessor{
	
	private $options = array(
		"alphaBlending"             => false,
		"backgroundColor"           => "#ffffff", // hex color code
		"jpegQuality"               => 100,       // 0-100
		"resizingMethod"            => "fit",     // fit, fill
		"resizingVerticalAlign"     => "middle",  // top, middle, bottom
		"resizingHorizontalAlign"   => "center",  // left, center, right
		"adjustToContentDimensions" => true,
	);
	
	private $im;
	private $filepath;
	private $filetype;
	private $width;
	private $height;
	
	public function __construct($filepath, $options = null){
		
		$this->setOptions($options);
		
		if (!$this->readFile($filepath)){
			return false;
		}
		
	}
	
	public function setOptions($options = null){
		
		if (is_array($this->options)){
			
			$default_options = $this->options;
			$this->options = new ImageProcessorOptions();
			
			foreach($default_options as $key => $value){
				$this->options->$key = $value;
			}
			
		}
		
		if ($options !== null && (is_array($options) || is_object($options))){
			foreach ($options as $key => $value){
				$this->options->$key = $value;
			}
		}
		
	}
	
	protected function readFile($filepath){
		
		$external = false;
		
		if (
			strpos($filepath, "http://") !== false
			|| strpos($filepath, "https://") !== false
			|| strpos($filepath, "ftp://") !== false
		){
			$external = true;
		}
		
		if (empty($filepath)){
			//return new ImageProcessorError(1);
			return false;
		}
		
		if (!$external){
			if (!file_exists($filepath)){
				//return new ImageProcessorError(2);
				return false;
			}
			
			if (!is_readable($filepath)){
				//return new ImageProcessorError(3);
				return false;
			}
		}
		
		$filetype = strtolower(substr($filepath,strrpos($filepath, ".")+1));
		$filetype = $filetype == "jpg" ? "jpeg" : $filetype;
		
		eval('$this->im = imagecreatefrom'.$filetype.'($filepath);');
		
		imagealphablending($this->im, (bool)$this->options->alphaBlending);
		
		$dimensions = getimagesize($filepath);
		
		$this->filepath = $filepath;
		$this->filetype = $filetype;
		$this->width    = $dimensions[0];
		$this->height   = $dimensions[1];
		
		return true;
		
	}
	
	public static function _($src_path, $options, $width, $height, $dst_path = null){
		$im = new ImageProcessor($src_path, $options);
		$im->resize($width, $height);
		
		if ($dst_path === null){
			$im->display();
		} else {
			//$im->save($dst_path);
		}
		
		return $im;
		
	}
	
	public function resize($width = null, $height = null, $coords = null){
		
		// Calculem les dimensions de la nova imatge
		if ($width !== null && $height === null){
			$height = $this->height * $width / $this->width;
		}
		
		else if ($width === null && $height !== null){
			$width = $this->width * $height / $this->height;
		}
		
		else if ($width === null && $height === null){
			// Si no s'ha especificat cap de les dues
			// dimensions no redimensionem res
			return false;
			
			//$width = $this->width;
			//$height = $this->height;
		}
		
		// Les següents, són les coordenades i dimensions de la
		// imatge original a copiar.
		if (is_array($coords) && count($coords) == 4){
			$sx = $coords[0]; // Source X
			$sy = $coords[1]; // Source Y
			$sw = $coords[2]; // Source Width
			$sh = $coords[3]; // Source Height
		} else {
			// Agafem la imatge sencera
			$sx = 0; // Source X
			$sy = 0; // Source Y
			$sw = $this->width; // Source Width
			$sh = $this->height; // Source Height
		}
		
		// Primer, calculem les dimensions de destí
		// ajustant la imatge per l'amplada.
		$dw = $width; // Destination Width
		$dh = $this->height * $width / $this->width; // Destination Height
		
		// Si l'altura de la imatge resultant és més gran que el que volem
		// o el mètode de redimensió és "fill", recalculem les dimensions
		// de destí ajustant la imatge per l'alçada.
		if(
				($this->options->resizingMethod == "fill" && $dh < $height)
				|| ($this->options->resizingMethod == "fit" && $dh > $height)
		){
			$dw = $this->width * $height / $this->height;
			$dh = $height;
		}
		
		if (
				$this->options->adjustToContentDimensions == "content"
				&& $this->options->resizingMethod == "fit"
		){
			$width  = $dw;
			$height = $dh;
		}
		
		// Calculem les coordenades (posició) de la imatge
		// destí segons les opcions d'alineació definides.
		
		switch($this->options->resizingHorizontalAlign){
			case "left":            $dx = 0; break;
			default: case "center": $dx = ($width - $dw) / 2; break;
			case "right":           $dx = $width - $dw; break;
		}
		
		switch($this->options->resizingVerticalAlign){
			case "top":             $dy = 0; break;
			default: case "middle": $dy = ($height - $dh) / 2; break;
			case "bottom":          $dy = $height - $dh; break;
		}
		
		// Creem la nova imatge a partir de les dimensions calculades
		$im = imagecreatetruecolor($width, $height);
		
		// Pintem el fons de la imatge (o el deixem transparent)
		if ($this->options->backgroundColor == "transparent"){
			$transparent_color = $this->hexToImageColor($im, "#000000");
			imagecolortransparent($im, $transparent_color);
		}
		
		else {
			$background_color = $this->hexToImageColor($im, $this->options->backgroundColor);
			imagefill($im, 0, 0, $background_color);
		}
		
		imagecopyresampled($im, $this->im, $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh);
		
		$this->im = $im;
		
	}
	
	public function rotate($degrees = 0){
		$background_color = $this->hexToImageColor($this->im, $this->options->backgroundColor);
		$this->im = imagerotate($this->im, $degrees, $background_color);
	}
	
	public function display($format = null, $output_header = true, $exit = true){
		
		// Format
		if ($format === null){
			$format = $this->filetype;
		}
		
		if ($format == "jpg"){
			$format == "jpeg";
		}
		
		$format = strtolower($format);
		
		
		// Header
		if ($output_header){
			header("Content-type: image/".$format);
		}
		
		// Output image
		
		if ($this->options->backgroundColor == "transparent"){
			imagepng($this->im);
		}
		
		elseif ($format == "jpeg" || $format== "jpg"){
			imagejpeg($this->im, null, $this->options->jpegQuality);
		}
		
		else {
			eval('image'.$format.'($this->im);');
		}
		
		// Exit
		if ($exit){
			exit;
		}
		
	}
	
	public function save($filepath = null, $format = null){
		
		// Si no s'especifica una imatge de destí,
		// sobreescribim la original.
		if ($filepath === null){
			$filepath = $this->filepath;
		}
		
		if ($format === null){
			$format = $this->filetype;
		}
		
		if ($format == "jpg"){
			$format == "jpeg";
		}
		
		$format = strtolower($format);
		
		if ($this->options->backgroundColor == "transparent"){
			imagepng($this->im, $filepath);
		}
		
		elseif ($this->filetype == "jpeg"){
			imagejpeg($this->im, $filepath, $this->options->jpegQuality);
		}
		
		else {
			eval('image'.$this->filetype.'($this->im, $filepath);');
		}
		
	}
	
	protected function hexToImageColor($im, $hex_color){
		
		$hex_color = str_replace("#","",$hex_color);
		
		if (strlen($hex_color) == 3){
			$hex_color = preg_replace("/^(.)(.)(.)$/", "\\1\\1\\2\\2\\3\\3", $hex_color);
		}
		
		if (strlen($hex_color) != 6){
			$hex_color = "000000";
		}
		
		$red   = hexdec(substr($hex_color, 0, 2));
		$green = hexdec(substr($hex_color, 2, 2));
		$blue  = hexdec(substr($hex_color, 4, 2));
		return imagecolorallocate($im, $red, $green, $blue);
	}
	
}

class ImageProcessorOptions{
	
}

/*
class ImageProcessorError{
	
	private $errors = array(
		0 => "Unknown Error",
		1 => "No file specified",
		2 => "File not found",
		3 => "File not readable"
	);
	
	public $code;
	public $message;
	
	function __construct($code = 0){
		$this->code = $code;
		$this->message = $this->errors[$code];
		
		unset($this->errors);
	}
	
}
*/
