<?php

class Image {

	/**
	 *
	 * @param resource $img_name
	 * @param resource $img_temp
	 * @param numeric $max_width
	 * @param numeric $max_height
	 * @return resource
	 */
	public static function resize($img_name, $img_temp, $max_width, $max_height, $x = 0, $y = 0, $resiseJCroop = false) {
		$tipo = (explode(".", strtolower($img_name)));
		$extension = $tipo[1];
		$imgphp = static::imageCreate($img_temp, $extension);

		if ($imgphp) {
			/*
			 * Caso haja alguma regra sobre largura ou altura, ela deve ser 
			 * aplicada aqui, ignorando o restante da fun��o =)
			 */

			// $newwidth = 200;
			// $newheight = ($height / $width) * $newwidth;
			// Pega o tamanho da imagem e proporcao de resize
			$width = imagesx($imgphp);
			$height = imagesy($imgphp);
			$scale = min($max_width / $width, $max_height / $height);

			// Se a imagem � maior que o permitido, encolhe ela!
			if ($scale < 1) {
				if ($resiseJCroop) {
					$tmp_img = imagecreatetruecolor($max_width, $max_height);
					imagecopyresampled($tmp_img, $imgphp, 0, 0, $x, $y, $max_width, $max_height, $max_width, $max_height);
				} else {
					$new_width = floor($scale * $width);
					$new_height = floor($scale * $height);

					// Cria uma imagem temporaria
					$tmp_img = imagecreatetruecolor($new_width, $new_height);

					// Copia e resize a imagem velha na nova
					imagecopyresized($tmp_img, $imgphp, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				}
				imagedestroy($imgphp);
				$imgphp = $tmp_img;
			}
			return $imgphp;
		}
		return null;
	}

	/**
	 *
	 * @param resource $img
	 * @param string $filename
	 * @param int $quality
	 */
	public static function saveImg($img, $filename, $quality = 100) {
		if ($img == NULL) {
			throw new Exception('Erro ao processar a imagem: Não é uma imagem.');
		}
		$ext = static::getExtensionFromFileName($filename);
		switch ($ext) {
			case 'bmp':
				return imagewbmp($img, $filename);
			case 'gif':
				return imagegif($img, $filename);
			case 'jpeg':
			case 'jpg':
				return imagejpeg($img, $filename, $quality);
			case 'png':
				if ($quality < 10) {
					$quality = 10;
				}
				$qpng = (floor($quality / 10) - 10) * -1;
				return imagepng($img, $filename, $qpng);
		}
	}

	private static function createImge(string $filename, array|bool $imageInfo): GdImage {
		if ($imageInfo === false) {
			Debug::tail("Problema na imageinfo do arquivo: ".$filename);
			throw new Exception("Imagem indisponível");
		}
		$image_type = $imageInfo[2];
		if ($image_type == IMAGETYPE_JPEG) {
			return imagecreatefromjpeg($filename);
		}
		if ($image_type == IMAGETYPE_GIF) {
			return imagecreatefromgif($filename);
		}
		if ($image_type == IMAGETYPE_PNG) {
			return imagecreatefrompng($filename);
		}
		throw new Exception("Imagem indisponível");
	}

	private static function resizePng(string $filename, GdImage $img, $widthMax, $image_type): GdImage {
		$imgSize = getimagesize($filename);
		$actualWidth = $imgSize[0];
		$actualHeight = $imgSize[1];

		if ($image_type == IMAGETYPE_PNG) {
			if ($widthMax == 0) {
				$widthMax = $actualWidth;
				$newheight = $actualHeight;
			} else {
				$proporcao = $widthMax * 100 / $actualWidth;
				$newheight = ($proporcao * 0.01) * $actualHeight;
			}
			$thumb = imagecreatetruecolor($widthMax, $newheight);
			imagealphablending($thumb, false);
			imagesavealpha($thumb, true);

			$source = imagecreatefrompng($filename);
			imagealphablending($source, true);

			imagecopyresampled($thumb, $source, 0, 0, 0, 0, $widthMax, $newheight, $actualWidth, $actualHeight);

			$img = $thumb;
		}
		return $img;
	}


	private static function resizeImg(string $filename, GdImage $img, $widthMax, $image_type): GdImage {
		$imgSize = getimagesize($filename);
		$actualWidth = $imgSize[0];
		$actualHeight = $imgSize[1];
		if ($image_type != IMAGETYPE_PNG && $widthMax > 0 && ($actualWidth > $widthMax)) {
			$proporcao = $widthMax * 100 / $actualWidth;
			$newheight = ($proporcao * 0.01) * $actualHeight;
			$dst = imagecreatetruecolor($widthMax, $newheight);
			imagecopyresampled($dst, $img, 0, 0, 0, 0, $widthMax, $newheight, $actualWidth, $actualHeight);
			return $dst;
		}
		return self::resizePng($filename, $img, $widthMax, $image_type);
	}

	private static function headerImg(string $filename, $image_info) {
		$arq_tipo = $image_info['mime'];
		$ext = self::getExtensionFromFileName($filename);
		header('Content-Type: ' . $arq_tipo);
		header("Content-Disposition: inline; filename=imagem.$ext");
		header("Content-Description: Gerado por UEG");
	}

	private static function outputImg(GdImage $img, $image_type, $stop = true) {
		if ($image_type == IMAGETYPE_JPEG) {
			if (!imagejpeg($img, null)) {
				die('Falha');
			}
		} elseif ($image_type == IMAGETYPE_GIF) {
			imagegif($img);
		} elseif ($image_type == IMAGETYPE_PNG) {
			imagepng($img);
		}
		if ($img != null) {
			imagedestroy($img);
		}
		if ($stop) {
			die;
		}
	}

	public static function showImgBase64($filename, $widthMax = 0): string {
		if(!file_exists($filename)){
			return "data:image/png;base64,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";
		}
		$image_info = getimagesize($filename);
		$image_type = $image_info[2];
		$imgCreated = self::createImge($filename, $image_info);
		
		$img = self::resizeImg($filename, $imgCreated, $widthMax, $image_type);
		ob_start();

		self::outputImg($img, $image_type, false);

		$bin = ob_get_clean();
		$b64 = base64_encode($bin);
		return 'data:' . $image_info['mime'] . ';base64,' . $b64;
	}

	public static function showImg($filename, $widthMax = 0) {
		$image_info = getimagesize($filename);
		$image_type = $image_info[2];
		$imgCreated = self::createImge($filename, $image_info);

		$img = self::resizeImg($filename, $imgCreated, $widthMax, $image_type);

		self::headerImg($filename, $image_info);
		self::outputImg($img, $image_type);
	}

	public static function emptyDirImg($dir) {
		if (is_dir($dir)) {
			$diretorio = dir($dir);
			while (($arquivo = $diretorio->read()) !== false) {
				$path_comp = $dir . $arquivo;
				if ($arquivo != "." && $arquivo != "..") {
					unlink($path_comp);
				}
			}
			$diretorio->close();
		}
	}

	/**
	 * upload de imagens para morpheus
	 * @param type $dir
	 * @param type $maxkb
	 * @param type $rename
	 * @param type $qtdeDisp
	 * @return string
	 * @throws Exception
	 * @deprecated 
	 */
	public static function uploadArray($dir, $maxkb, $rename, $qtdeDisp) {
		//diretorio, maximo em kb para o up, novalargura, novaaltura, forcar o upload com os padrÃµes definidos.
		if (!is_dir($dir)) {
			mkdir("$dir", 0777);
		}
		$myfiles = $_FILES['fileUpload'];
		$f_nameArray = $myfiles['name'];

		for ($i = 0; $i < count($f_nameArray); $i++) {
			$f_name = $myfiles['name'][$i];
			$f_tmp = $myfiles['tmp_name'][$i];
			$f_type = $myfiles['type'][$i];
			$f_size = $myfiles['size'][$i];
			try {
				//Não permitir mais de 28 fotos de uma vez.
				if ($i >= $qtdeDisp) {
					throw new Exception("Nem todas as imagens foram carregadas. O número máximo de 28 imagens foi atingido. ");
				} else {
					static::validateImage($f_type, $f_size);
				}
			} catch (Exception $e) {
				$filename[$i] = $e;
				continue;
			}
			$f_name = Diretorios::semAcentos($f_name);

			$extension = static::getExtensionFromFileName($f_name);
			$tmp = static::imageCreate($f_tmp, $extension);

			//se deseja mudar o nome do arquivo, deve ser informado em rename.
			if ($rename != "") {
				$f_name = $rename++ . "." . $extension;
			}

			//local onde sera salvo e o nome do arquivo
			$filename[$i] = $dir . $f_name;
			//cria a imagem na pasta e depois remove as infos nÃ£o mais necessarias
			static::saveImg($tmp, $filename[$i]);
		}
		return $filename;
	}

	/**
	 * Retorna a extensão do nome do arquivo
	 * @param string $f_name
	 * @return string
	 */
	public static function getExtensionFromFileName($f_name) {
		$tipo = (explode(".", strtolower($f_name)));
		$extension = $tipo[count($tipo) - 1];
		return $extension;
	}

	/**
	 * Retorna as extensões mais comuns para uplodad na Ueg, com base no tipo do arquivo
	 * @param string $f_type
	 * @return string
	 */
	public static function getExtensionFromMimeType($f_type):string {
		$tipo = strtolower($f_type);
        
        switch ($tipo) {
            case "image/jpeg": 
                return 'jpg';
            case "image/png": 
                return 'png';    
            case "application/pdf": 
            case "application/octet-stream": 
                return 'pdf';    

            default:
                return '';
        }
	}
    
	public static function getError($f_error):string {
		if ($f_error == UPLOAD_ERR_OK) {
            return '';
        }
        switch ($f_error) {
            case UPLOAD_ERR_INI_SIZE: 
                return 'Excedeu tamanho permitido.';
            case UPLOAD_ERR_FORM_SIZE: 
                return 'Excedeu tamanho de arquivo do formulário.';    
            case UPLOAD_ERR_PARTIAL:
                return 'Arquivo truncado. Tente novamente.';
            case UPLOAD_ERR_NO_FILE: 
                return 'Nenhum arquivo enviado.';    
            case UPLOAD_ERR_NO_TMP_DIR: 
            case UPLOAD_ERR_CANT_WRITE: 
                return 'Erro de configuração no servidor.';    

            default:
                return 'Erro desconhecido.';
        }
	}

	/**
	 * @deprecated Constatado que funciona apenas no Morpheus parte antiga
	 * @param $dir
	 * @param $maxkb
	 * @param $newwidth
	 * @param $newheight
	 * @param $rename
	 * @param bool $clear
	 * @param int $x
	 * @param int $y
	 * @param int $ratio
	 * @param bool $resiseJCroop
	 * @return string
	 */
	public static function upload($dir, $maxkb, $newwidth, $newheight, $rename, $clear = false, $x = 0, $y = 0, $ratio = 0, $resiseJCroop = false) {
		if (!is_dir($dir)) {
			mkdir("$dir", 0777);
		}

		$files = (Request::getString('acao') == 'upload_imagem_resumo') ?
			'fileUpload2' : 'fileUpload';

		$f_name = isset($_FILES[$files]['name']) ? $_FILES[$files]['name'] : '';
		$f_tmp = isset($_FILES[$files]['tmp_name']) ? $_FILES[$files]['tmp_name'] : '';
		$f_type = isset($_FILES[$files]['type']) ? $_FILES[$files]['type'] : '';
		$f_size = isset($_FILES[$files]['size']) ? $_FILES[$files]['size'] : '';

		//tratamento para remoção de acentos, espaços, pontos e afins...
		$f_name = Diretorios::semAcentos($f_name);
		$extension = static::getExtensionFromFileName($f_name);
		$f_name = mb_substr($f_name, 0, -(strlen($extension) + 1));
		$f_name = Conversor::somenteAlfaNumerico($f_name);
		$f_name .= '.' . $extension;

		static::validateImage($f_type, $f_size);

		//limpando imagens antigas
		if ($clear != false) {
			static::emptyDirImg($dir);
		}

		//redimensionando a imagem
		if ($newwidth || $newheight) {
			$tmp = static::resize($f_name, $f_tmp, $newwidth, $newheight, $x, $y, $resiseJCroop);
		} else {
			$extension = static::getExtensionFromFileName($f_name);
			$tmp = static::imageCreate($f_tmp, $extension);
		}

		//se deseja mudar o nome do arquivo, deve ser informado em rename.
		if ($rename != "") {
			$nome = (explode(".", strtolower($f_name)));
			$f_name = $rename . "." . $nome[1];
		}

		//local onde sera salvo e o nome do arquivo
		$filename = $dir . $f_name;
		//cria a imagem na pasta e depois remove as infos nÃ£o mais necessarias
		static::saveImg($tmp, $filename);
		return $filename;
	}

	public static function imageCreate($fileName, $extension) {
		if ($extension == "jpg" || $extension == "jpeg") {
			$imgphp = imagecreatefromjpeg($fileName);
		} else if ($extension == "png") {
			$imgphp = imagecreatefrompng($fileName);
			$imgphp = static::addTransparent($fileName, $imgphp);
		} else {
			$imgphp = imagecreatefromgif($fileName);
			$imgphp = static::addTransparent($fileName, $imgphp);
		}
		return $imgphp;
	}

	private static function addTransparent($f_name, $img_temp) {
		if (!list($w, $h) = getimagesize($f_name)) {
			return "Unsupported picture type!";
		}
		$background = imagecolorallocate($img_temp, 255, 255, 255);
		// removing the black from the placeholder
		imagecolortransparent($img_temp, $background);

		// turning off alpha blending (to ensure alpha channel information
		// is preserved, rather than removed (blending with the rest of the
		// image in the form of black))
		imagealphablending($img_temp, false);

		// turning on alpha channel information saving (to ensure the full range
		// of transparency is preserved)
		imagesavealpha($img_temp, true);
		return $img_temp;
	}

	public static function validateImage($f_type, $f_size) {
		if ($f_size == 0) {
			throw new Exception("Nenhum arquivo foi selecionado para upload. Selecione uma ou mais imagens e clique em 'Salvar Imagem'.");
		}
		if ($f_type == "") {
			throw new Exception('Informe um arquivo para upload.');
		}

		if ($f_type != "image/jpeg" && $f_type != "image/png") {
			throw new Exception('O Arquivo enviado não é do tipo aceito para upload, ele deve ser do tipo imagem e de extensão: .jpeg ou .png');
		}
	}

//	//tratamento de caracteres para nome de arquivos
//	public static function transform($txt) {
//		$beta=array('
//			a,a,a,a,a,e,e,e,e,i,i,i,i,o,o,o,o,o,u,u,u,u,c,A,A,A,A,A,E,E,E,E,I,I,I,I,O,O,O,O,O,U,U,U,U,C,"_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_","_"
//		');
//		$alfa=array(
//			'á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü','ç','Á','À','Ã','Â','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Õ','Ô','Ö','Ú','Ù','Û','Ü','Ç',"\"","'","!","@","#","$","%","&","*","+","}","]","=","º","§","{","[","ª","?","/","°","<",">","\\","|",",",";",":","~","^","´","`"," "
//		);
//		$gama=str_replace($alfa,$beta,$txt);
//		$omega=strip_tags($gama);
//		$omega=trim($omega);
//		return($omega);
//	}
}
